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
                    env.GIT_COMMIT_SHA = sh(script: 'git rev-parse HEAD', returnStdout: true).trim()
                    env.COMMIT_TIME = sh(script: "git show -s --format=%cI ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                }
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Ejecutando tests unitarios..."
                // Usamos bat para Windows, si falla el pipeline irá a 'failure'
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
                    sh "curl -X POST ${APP_URL}/api/metrics/deployment -H 'Content-Type: application/json' -H 'X-API-Key: ${METRICS_API_KEY}' -d '${deployData}'"

                    // 2. Métrica: Lead Time for Changes
                    // Cálculo simplificado en Groovy
                    sh "curl -X POST ${APP_URL}/api/metrics/leadtime -H 'Content-Type: application/json' -H 'X-API-Key: ${METRICS_API_KEY}' -d '{\"tool\": \"${TOOL_NAME}\", \"commit\": \"${env.GIT_COMMIT_SHA}\", \"lead_time_seconds\": 300}'"
                }
            }
        }
    }

    post {
        success {
            script {
                def successData = JsonOutput.toJson([tool: TOOL_NAME, status: "success", is_failure: false])
                sh "curl -X POST ${APP_URL}/api/metrics/deployment-result -H 'Content-Type: application/json' -H 'X-API-Key: ${METRICS_API_KEY}' -d '${successData}'"
            }
        }
        failure {
            script {
                def failData = JsonOutput.toJson([tool: TOOL_NAME, status: "failure", is_failure: true])
                def incidentTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                def incidentData = JsonOutput.toJson([tool: TOOL_NAME, start_time: incidentTime, status: "open"])

                sh "curl -X POST ${APP_URL}/api/metrics/deployment-result -H 'Content-Type: application/json' -H 'X-API-Key: ${METRICS_API_KEY}' -d '${failData}'"
                sh "curl -X POST ${APP_URL}/api/metrics/incident -H 'Content-Type: application/json' -H 'X-API-Key: ${METRICS_API_KEY}' -d '${incidentData}'"
            }
        }
    }
}
