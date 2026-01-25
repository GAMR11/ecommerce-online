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
                    env.GIT_COMMIT_SHA = bat(script: "git rev-parse HEAD", returnStdout: true).trim().split('\r\n').last()
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo "📦 Instalando dependencias de Composer..."
                // Instalamos vendor para que artisan test funcione
                bat 'composer install --no-interaction --prefer-dist'
                // Creamos el archivo .env si no existe y generamos llave
                bat 'copy .env.example .env /Y'
                bat 'php artisan key:generate'
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Ejecutando tests unitarios..."
                bat 'php artisan test'
            }
        }

        stage('Deploy & Track DORA') {
            steps {
                script {
                    echo "🚀 Desplegando en producción (Simulado)..."
                    sleep 3
                    def deployTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def deployData = JsonOutput.toJson([tool: TOOL_NAME, timestamp: deployTime, commit: env.GIT_COMMIT_SHA, status: "success"])
                    bat "curl -X POST ${APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${deployData.replace('"', '\\"')}\""

                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"{\\\"tool\\\": \\\"${TOOL_NAME}\\\", \\\"commit\\\": \\\"${env.GIT_COMMIT_SHA}\\\", \\\"lead_time_seconds\\\": 300}\""
                }
            }
        }
    }

    post {
        always {
            script {
                // Verificamos si Ngrok está activo antes de terminar
                echo "Verificando conexión con: ${APP_URL}"
            }
        }
        failure {
            script {
                def failData = JsonOutput.toJson([tool: TOOL_NAME, status: "failure", is_failure: true])
                def incidentTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                def incidentData = JsonOutput.toJson([tool: TOOL_NAME, start_time: incidentTime, status: "open"])

                bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${failData.replace('"', '\\"')}\""
                bat "curl -X POST ${APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${incidentData.replace('"', '\\"')}\""
            }
        }
    }
}
