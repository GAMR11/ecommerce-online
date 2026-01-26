import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
        // RUTA FORZADA BASADA EN TU COMAND "WHERE"
        PHP_BIN         = "C:\\laragon\\bin\\php\\php-8.2.30-nts-Win32-vs16-x64\\php.exe"
    }

    stages {
        stage('Checkout & Info') {
            steps {
                script {
                    echo "📦 Checking out code..."
                    // Validamos que la ruta existe
                    bat "@echo off & if exist \"${env.PHP_BIN}\" (echo ✅ PHP 8.2 encontrado) else (echo ❌ No se encuentra el archivo en la ruta especificada)"

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
                echo "📦 Installing dependencies with PHP 8.2..."
                script {
                    // Usamos PHP 8.2 para ejecutar composer
                    // Nota: Usamos --ignore-platform-reqs por si te falta alguna extensión activa en el php.ini de Laragon
                    bat "\"${env.PHP_BIN}\" \"C:\\ProgramData\\ComposerSetup\\bin\\composer.phar\" install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs"

                    bat "if not exist .env copy .env.example .env"
                    bat "\"${env.PHP_BIN}\" artisan key:generate"
                }
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Running tests..."
                bat "\"${env.PHP_BIN}\" artisan test"
            }
        }

        stage('Deploy to Railway') {
            steps {
                script {
                    echo "🚀 Deploying to Railway (Verification)..."
                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def maxAttempts = 15
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
                                echo "✅ Deploy verificado con éxito."
                            } else {
                                echo "⏳ Intento ${attempt}/${maxAttempts}: Esperando 200 (Recibido: ${response})..."
                                sleep(15)
                            }
                        } catch (e) {
                            sleep(15)
                        }
                    }
                    if (!deploySuccess) error("❌ Railway no respondió a tiempo.")
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
