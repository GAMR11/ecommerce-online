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
                    // Obtenemos el SHA del commit usando comandos de Windows
                    env.GIT_COMMIT_SHA = bat(script: "git rev-parse HEAD", returnStdout: true).trim().split('\r\n').last()
                }
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Ejecutando tests unitarios en Windows..."
                // Usamos 'bat' para ejecutar PHP en Windows
                bat 'php artisan test'
            }
        }

        stage('Deploy & Track DORA') {
            steps {
                script {
                    echo "🚀 Desplegando en producción (Simulado)..."
                    sleep 3
                    def deployTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    // 1. Métrica: Deployment Frequency
                    def deployData = JsonOutput.toJson([tool: TOOL_NAME, timestamp: deployTime, commit: env.GIT_COMMIT_SHA, status: "success"])
                    // Usamos bat y comillas dobles escapadas para el JSON en Windows
                    bat "curl -X POST ${APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${deployData.replace('"', '\\"')}\""

                    // 2. Métrica: Lead Time for Changes (Simplificado para pruebas)
                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"{\\\"tool\\\": \\\"${TOOL_NAME}\\\", \\\"commit\\\": \\\"${env.GIT_COMMIT_SHA}\\\", \\\"lead_time_seconds\\\": 300}\""
                }
            }
        }
    }

    post {
        success {
            script {
                def successData = JsonOutput.toJson([tool: TOOL_NAME, status: "success", is_failure: false])
                bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${successData.replace('"', '\\"')}\""
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
