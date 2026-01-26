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
                    echo "📦 Checking out code..."

                    // Capturamos el SHA
                    env.GIT_COMMIT_SHA = bat(script: "@echo off & git rev-parse HEAD", returnStdout: true).trim()

                    // CORRECCIÓN PARA WINDOWS: Usamos %% para escapar el símbolo de porcentaje
                    // Esto evita el error "invalid --pretty format"
                    def commitTimestamp = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()

                    // Si por alguna razón sigue fallando, usamos el tiempo actual como fallback para no detener el pipeline
                    long commitSeconds = commitTimestamp.isNumber() ? commitTimestamp.toLong() : (System.currentTimeMillis() / 1000)

                    env.COMMIT_TIME = new Date(commitSeconds * 1000).format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    // Tiempos del pipeline
                    env.PIPELINE_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    long startEpoch = (long) (System.currentTimeMillis() / 1000)
                    env.PIPELINE_START_EPOCH = startEpoch.toString()

                    echo "📝 Commit SHA: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️ Commit Time: ${env.COMMIT_TIME}"
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo "📦 Installing dependencies..."
                bat 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                bat 'copy .env.example .env /Y'
                bat 'php artisan key:generate'
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Running tests..."
                bat 'php artisan test'
            }
        }

        stage('Deploy to Railway') {
            steps {
                script {
                    echo "🚀 Deploying to Railway..."
                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def maxAttempts = 30
                    def attempt = 0
                    def deploySuccess = false

                    while (attempt < maxAttempts && !deploySuccess) {
                        attempt++
                        try {
                            // En Windows, curl dentro de bat necesita escapar los % del formato
                            def response = bat(
                                script: "@echo off & curl -s -o nul -w \"%%{http_code}\" ${APP_URL}/api/ping",
                                returnStdout: true
                            ).trim()

                            if (response.contains("200")) {
                                echo "✅ Railway deployment is live!"
                                deploySuccess = true
                                env.DEPLOY_END_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                                env.DEPLOY_END_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()
                            } else {
                                echo "⏳ Attempt ${attempt}/${maxAttempts} - HTTP ${response}..."
                                sleep(5)
                            }
                        } catch (Exception e) {
                            echo "⚠️ Connection failed, retrying..."
                            sleep(5)
                        }
                    }
                    if (!deploySuccess) error("❌ Deployment verification failed")
                }
            }
        }

        stage('Track DORA Metrics') {
            steps {
                script {
                    echo "📊 Recording DORA Metrics..."

                    // Obtenemos el timestamp del commit de nuevo con el escape corregido//
                    def commitTs = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                    long commitEpoch = commitTs.isNumber() ? commitTs.toLong() : 0
                    long deployEpoch = env.DEPLOY_END_EPOCH ? env.DEPLOY_END_EPOCH.toLong() : 0
                    long leadTimeSeconds = (deployEpoch > 0 && commitEpoch > 0) ? (deployEpoch - commitEpoch) : 0

                    def leadTimeData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        commit: env.GIT_COMMIT_SHA,
                        commit_time: env.COMMIT_TIME,
                        deploy_time: env.DEPLOY_END_TIME,
                        lead_time_seconds: leadTimeSeconds
                    ])

                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${leadTimeData.replace('"', '\\"')}\""
                    echo "✅ Metrics recorded."
                }
            }
        }
    }

    post {
        failure {
            script {
                echo "❌ Pipeline FAILED"
                def failureTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                def failData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    timestamp: failureTime,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
                    status: "failure",
                    is_failure: true
                ])
                bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${failData.replace('"', '\\"')}\""
            }
        }
        always {
            script {
                echo "🏁 Pipeline finished."
            }
        }
    }
}
