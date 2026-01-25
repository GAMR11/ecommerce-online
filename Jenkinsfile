pipeline {
    agent any

    environment {
        // Credenciales configuradas en Jenkins
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
    }

    stages {
        stage('Checkout & Info') {
            steps {
                // Jenkins hace checkout automático, pero obtenemos el SHA para las métricas
                script {
                    env.GIT_COMMIT_SHA = sh(script: 'git rev-parse HEAD', returnStdout: true).trim()
                    env.COMMIT_TIME = sh(script: "git show -s --format=%cI ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                }
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Ejecutando tests unitarios..."
                // Si este comando falla, el pipeline se detiene y va al bloque 'failure'
                sh 'php artisan test'
            }
        }

        stage('Deploy & Track DORA') {
            steps {
                script {
                    // 1. Simulación del despliegue (Igual que el 'sleep 3' de GitHub)
                    echo "🚀 Desplegando en producción (Simulado)..."
                    sleep 3

                    def deployTime = sh(script: 'date -u +%Y-%m-%dT%H:%M:%SZ', returnStdout: true).trim()

                    // 2. Métrica: Deployment Frequency
                    sh """
                        curl -X POST ${APP_URL}/api/metrics/deployment \
                        -H "Content-Type: application/json" \
                        -H "X-API-Key: ${METRICS_API_KEY}" \
                        -d '{"tool": "${TOOL_NAME}", "timestamp": "${deployTime}", "commit": "${env.GIT_COMMIT_SHA}", "status": "success"}'
                    """

                    // 3. Métrica: Lead Time for Changes
                    // El cálculo se hace igual que en el YAML
                    sh """
                        COMMIT_EPOCH=\$(date -d "${env.COMMIT_TIME}" +%s)
                        DEPLOY_EPOCH=\$(date -d "${deployTime}" +%s)
                        LEAD_TIME=\$((DEPLOY_EPOCH - COMMIT_EPOCH))

                        curl -X POST ${APP_URL}/api/metrics/leadtime \
                        -H "Content-Type: application/json" \
                        -H "X-API-Key: ${METRICS_API_KEY}" \
                        -d '{"tool": "${TOOL_NAME}", "commit": "${env.GIT_COMMIT_SHA}", "lead_time_seconds": '\$LEAD_TIME'}'
                    """
                }
            }
        }
    }

    post {
        success {
            // 4. Métrica: Change Failure Rate (Éxito)
            sh """
                curl -X POST ${APP_URL}/api/metrics/deployment-result \
                -H "Content-Type: application/json" \
                -H "X-API-Key: ${METRICS_API_KEY}" \
                -d '{"tool": "${TOOL_NAME}", "status": "success", "is_failure": false}'
            """
        }
        failure {
            // 4. Métrica: Change Failure Rate (Fallo)
            sh """
                curl -X POST ${APP_URL}/api/metrics/deployment-result \
                -H "Content-Type: application/json" \
                -H "X-API-Key: ${METRICS_API_KEY}" \
                -d '{"tool": "${TOOL_NAME}", "status": "failure", "is_failure": true}'
            """
            // 5. Métrica: MTTR (Crear incidente)
            sh """
                curl -X POST ${APP_URL}/api/metrics/incident \
                -H "Content-Type: application/json" \
                -H "X-API-Key: ${METRICS_API_KEY}" \
                -d '{"tool": "${TOOL_NAME}", "start_time": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ)"'", "status": "open"}'
            """
        }
    }
}
