import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
    }

    stages {
        stage('Checkout & Info') {
            steps {
                script {
                    // Obtenemos el SHA de forma robusta
                    env.GIT_COMMIT_SHA = bat(script: "git rev-parse HEAD", returnStdout: true).trim().split('\r\n').last()
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo "📦 Instalando dependencias..."
                bat 'composer install --no-interaction --prefer-dist'
                bat 'copy .env.example .env /Y'
                bat 'php artisan key:generate'
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Ejecutando tests..."
                // Si esto falla, saltará directamente al bloque 'post { failure }'
                bat 'php artisan test'
            }
        }

        stage('Deploy & Track DORA') {
            steps {
                script {
                    echo "🚀 Desplegando y registrando éxito..."
                    def deployTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    // 1. Registro de Despliegue Exitoso (Frecuencia de Despliegue)
                    def deployData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: deployTime,
                        commit: env.GIT_COMMIT_SHA,
                        status: "success"
                    ])
                    bat "curl -X POST ${APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${deployData.replace('"', '\\"')}\""

                    // 2. Registro de Lead Time (Tiempo de Entrega)
                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"{\\\"tool\\\": \\\"${TOOL_NAME}\\\", \\\"commit\\\": \\\"${env.GIT_COMMIT_SHA}\\\", \\\"lead_time_seconds\\\": 300}\""

                    // 3. Registro de Restauración (MTTR): Notificamos que si había un incidente previo, este despliegue lo cerró
                    def recoveryData = JsonOutput.toJson([tool: TOOL_NAME, status: "resolved", end_time: deployTime])
                    bat "curl -X POST ${APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${recoveryData.replace('"', '\\"')}\""
                }
            }
        }
    }

    post {
        failure {
            script {
                echo "❌ El pipeline falló. Registrando incidente..."
                def incidentTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                // 4. Registro de Fallo (Change Failure Rate)
                def failData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    commit: env.GIT_COMMIT_SHA,
                    status: "failure"
                ])
                bat "curl -X POST ${APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${failData.replace('"', '\\"')}\""

                // 5. Registro de Incidente Abierto (Time to Restore)
                def incidentData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    start_time: incidentTime,
                    status: "open",
                    description: "Pipeline failed in stage: ${env.STAGE_NAME}"
                ])
                bat "curl -X POST ${APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${incidentData.replace('"', '\\"')}\""
            }
        }
    }
}
