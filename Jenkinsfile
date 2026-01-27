import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
        PHP_BIN         = "C:\\laragon\\bin\\php\\php-8.2.30-nts-Win32-vs16-x64\\php.exe"
    }

    stages {
        stage('Checkout & Info') {
            steps {
                script {
                    echo "📦 Checking out code..."
                    bat "@echo off & if exist \"${env.PHP_BIN}\" (echo ✅ PHP 8.2 encontrado) else (echo ❌ No se encuentra el archivo en la ruta especificada)"

                    env.GIT_COMMIT_SHA = bat(script: "@echo off & git rev-parse HEAD", returnStdout: true).trim()
                    def commitTimestamp = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                    long commitSeconds = commitTimestamp.isNumber() ? commitTimestamp.toLong() : (System.currentTimeMillis() / 1000)
                    env.COMMIT_TIME = new Date(commitSeconds * 1000).format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    env.PIPELINE_START_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()

                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️  Commit Time: ${env.COMMIT_TIME}"
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo "📦 Installing dependencies with PHP 8.2..."
                script {
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

                    def maxAttempts = 30
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
                                if (attempt < maxAttempts) {
                                    sleep(5)
                                }
                            }
                        } catch (e) {
                            echo "⚠️ Error en intento ${attempt}: ${e.message}"
                            if (attempt < maxAttempts) {
                                sleep(5)
                            }
                        }
                    }

                    if (!deploySuccess) {
                        error("❌ Railway no respondió a tiempo.")
                    }
                }
            }
        }

        stage('Track DORA Metrics') {
            steps {
                script {
                    echo "📊 Recording DORA Metrics..."

                    // ==========================================
                    // MÉTRICA 1: DEPLOYMENT FREQUENCY
                    // ==========================================
                    echo "📊 Recording Deployment Frequency..."
                    def deploymentData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        branch: env.BRANCH_NAME ?: 'master',
                        commit: env.GIT_COMMIT_SHA,
                        status: "success"
                    ])

                    bat "curl -X POST ${APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${deploymentData.replace('"', '\\"')}\""
                    echo "✅ Deployment frequency recorded"

                    // ==========================================
                    // MÉTRICA 2: LEAD TIME FOR CHANGES
                    // ==========================================
                    echo "📊 Calculating Lead Time..."
                    long commitEpoch = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim().toLong()
                    long deployEpoch = env.DEPLOY_END_EPOCH.toLong()
                    long leadTimeSeconds = deployEpoch - commitEpoch

                    echo "⏱️  Lead Time: ${leadTimeSeconds} seconds (${leadTimeSeconds / 60} minutes)"

                    def leadTimeData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        commit: env.GIT_COMMIT_SHA,
                        commit_time: env.COMMIT_TIME,
                        deploy_time: env.DEPLOY_END_TIME,
                        lead_time_seconds: leadTimeSeconds
                    ])

                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${leadTimeData.replace('"', '\\"')}\""
                    echo "✅ Lead time recorded"

                    // ==========================================
                    // MÉTRICA 3: CHANGE FAILURE RATE (SUCCESS)
                    // ==========================================
                    echo "📊 Recording Deployment Success..."
                    def successData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        commit: env.GIT_COMMIT_SHA,
                        status: "success",
                        is_failure: false
                    ])

                    bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${successData.replace('"', '\\"')}\""
                    echo "✅ Deployment success recorded"

                    // ==========================================
                    // MÉTRICA 4: MTTR (AUTO-RESOLVE)
                    // ==========================================
                    echo "📊 Auto-resolving incidents..."
                    def resolveData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        resolution_time: env.DEPLOY_END_TIME
                    ])

                    try {
                        bat "curl -X POST ${APP_URL}/api/metrics/incident/resolve -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${resolveData.replace('"', '\\"')}\""
                        echo "✅ Incident resolution checked"
                    } catch (e) {
                        echo "ℹ️ No incidents to resolve"
                    }
                }
            }
        }

        stage('Metrics Summary') {
            steps {
                script {
                    echo "=========================================="
                    echo "📊 DORA METRICS - DEPLOYMENT SUMMARY"
                    echo "=========================================="
                    echo "✅ Deployment Status: SUCCESS"
                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo ""
                    echo "✅ Metrics Recorded:"
                    echo "  • Deployment Frequency ✓"
                    echo "  • Lead Time for Changes ✓"
                    echo "  • Change Failure Rate ✓"
                    echo "  • Mean Time to Recovery ✓"
                    echo "=========================================="
                }
            }
        }
    }

    post {
        success {
            script {
                echo "=========================================="
                echo "✅ JENKINS PIPELINE SUCCESSFUL"
                echo "=========================================="
                echo "Build: #${env.BUILD_NUMBER}"
                echo "Commit: ${env.GIT_COMMIT_SHA}"
                echo "All DORA metrics recorded successfully"
                echo "=========================================="
            }
        }

        failure {
            script {
                echo "❌ Pipeline FAILED - Recording failure metrics..."
                def failureTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                // Registro de fallo
                def failData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    timestamp: failureTime,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
                    status: "failure",
                    is_failure: true
                ])

                bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${failData.replace('"', '\\"')}\""

                // Crear incidente
                def incidentData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    incident_id: env.BUILD_ID,
                    start_time: failureTime,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
                    status: "open",
                    description: "Pipeline failed - Build #${env.BUILD_NUMBER}"
                ])

                bat "curl -X POST ${APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${incidentData.replace('"', '\\"')}\""

                echo "❌ Failure metrics recorded"
            }
        }
    }
}
