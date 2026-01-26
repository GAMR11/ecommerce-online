import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
        // AJUSTA ESTA RUTA A DONDE ESTÁ TU PHP 8.2
        PHP_BIN         = "C:\\php\\php.exe"
    }

    stages {
        stage('Checkout & Info') {
            steps {
                script {
                    echo "📦 Checking out code..."
                    // Verificamos qué versión de PHP ve Jenkins realmente
                    bat "@echo off & ${env.PHP_BIN} -v"

                    env.GIT_COMMIT_SHA = bat(script: "@echo off & git rev-parse HEAD", returnStdout: true).trim()
                    def commitTimestamp = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                    long commitSeconds = commitTimestamp.isNumber() ? commitTimestamp.toLong() : (System.currentTimeMillis() / 1000)
                    env.COMMIT_TIME = new Date(commitSeconds * 1000).format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    env.PIPELINE_START_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo "📦 Installing dependencies forcing PHP 8.2 path..."
                script {
                    // Ejecutamos Composer usando el ejecutable de PHP 8.2 específicamente
                    // Nota: Si 'composer' no funciona solo, usa la ruta completa al .phar o al .bat de composer
                    bat "${env.PHP_BIN} \"C:\\ProgramData\\ComposerSetup\\bin\\composer.phar\" install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs"

                    bat "copy .env.example .env /Y"
                    bat "${env.PHP_BIN} artisan key:generate"
                }
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Running tests..."
                // Usamos el PHP_BIN definido arriba
                bat "${env.PHP_BIN} artisan test"
            }
        }

        stage('Deploy to Railway') {
            steps {
                script {
                    echo "🚀 Deploying to Railway..."
                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def maxAttempts = 20
                    def deploySuccess = false
                    def attempt = 0

                    while (attempt < maxAttempts && !deploySuccess) {
                        attempt++
                        try {
                            def response = bat(script: "@echo off & curl -s -o nul -w \"%%{http_code}\" ${APP_URL}/api/ping", returnStdout: true).trim()
                            if (response.contains("200")) {
                                deploySuccess = true
                                env.DEPLOY_END_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                                env.DEPLOY_END_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()
                            } else {
                                echo "⏳ Attempt ${attempt}/${maxAttempts} - HTTP ${response}..."
                                sleep(10)
                            }
                        } catch (e) { sleep(10) }
                    }
                    if (!deploySuccess) error("❌ Deployment verification failed")
                }
            }
        }

        stage('Track DORA Metrics') {
            steps {
                script {
                    echo "📊 Recording DORA Metrics..."
                    long commitEpoch = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim().toLong()
                    long deployEpoch = env.DEPLOY_END_EPOCH.toLong()
                    long leadTimeSeconds = deployEpoch - commitEpoch

                    def leadTimeData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        commit: env.GIT_COMMIT_SHA,
                        commit_time: env.COMMIT_TIME,
                        deploy_time: env.DEPLOY_END_TIME,
                        lead_time_seconds: leadTimeSeconds
                    ])
                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${leadTimeData.replace('"', '\\"')}\""
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
    }
}
