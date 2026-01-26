import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
    }

    stages {
        // ============================================
        // STAGE 1: CHECKOUT & CAPTURE START TIME
        // ============================================
        stage('Checkout & Info') {
            steps {
                script {
                    echo "📦 Checking out code..."

                    // Capturar SHA del commit sin basura de la consola
                    env.GIT_COMMIT_SHA = bat(script: "@echo off & git rev-parse HEAD", returnStdout: true).trim()

                    // Corregido: %ci (minúscula) para mayor compatibilidad en Windows/Git
                    def commitTimeRaw = bat(script: "@echo off & git show -s --format=%ci ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                    env.COMMIT_TIME = commitTimeRaw

                    // Capturar timestamp de inicio
                    env.PIPELINE_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    // Corregido: Casting manual (long) para evitar el error de Script Security / toLong()
                    long startEpoch = (long) (System.currentTimeMillis() / 1000)
                    env.PIPELINE_START_EPOCH = startEpoch.toString()

                    echo "📝 Commit SHA: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️  Commit Time: ${env.COMMIT_TIME}"
                    echo "🕐 Pipeline Started: ${env.PIPELINE_START_TIME}"
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

                    long deployStart = (long) (System.currentTimeMillis() / 1000)
                    env.DEPLOY_START_EPOCH = deployStart.toString()
                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def maxAttempts = 30
                    def attempt = 0
                    def deploySuccess = false

                    while (attempt < maxAttempts && !deploySuccess) {
                        attempt++
                        try {
                            // Silenciamos curl para obtener solo el código HTTP
                            def response = bat(
                                script: "@echo off & curl -s -o nul -w \"%%{http_code}\" ${APP_URL}/api/ping",
                                returnStdout: true
                            ).trim()

                            if (response == '200') {
                                echo "✅ Railway deployment is live! (HTTP 200)"
                                deploySuccess = true
                                env.DEPLOY_END_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                                long deployEnd = (long) (System.currentTimeMillis() / 1000)
                                env.DEPLOY_END_EPOCH = deployEnd.toString()
                            } else {
                                echo "⏳ Attempt ${attempt}/${maxAttempts} - HTTP ${response} - Retrying..."
                                sleep(5)
                            }
                        } catch (Exception e) {
                            echo "⚠️ Connection failed - Retrying..."
                            sleep(5)
                        }
                    }

                    if (!deploySuccess) {
                        error("❌ Deployment verification failed after ${maxAttempts} attempts")
                    }
                }
            }
        }

        stage('Track DORA Metrics') {
            steps {
                script {
                    echo "📊 Recording DORA Metrics..."

                    // 1. DEPLOYMENT FREQUENCY
                    def deploymentData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        branch: env.BRANCH_NAME ?: 'master',
                        commit: env.GIT_COMMIT_SHA,
                        status: "success"
                    ])
                    bat "curl -X POST ${APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${deploymentData.replace('"', '\\"')}\""

                    // 2. LEAD TIME FOR CHANGES
                    long commitEpoch = bat(script: "@echo off & git show -s --format=%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim().toLong()
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

                    // 3. CHANGE FAILURE RATE (Success record)
                    def successData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        commit: env.GIT_COMMIT_SHA,
                        status: "success",
                        is_failure: false
                    ])
                    bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${successData.replace('"', '\\"')}\""

                    // 4. MTTR (Resolve Incidents)
                    def resolveData = JsonOutput.toJson([tool: TOOL_NAME, resolution_time: env.DEPLOY_END_TIME])
                    bat "curl -X POST ${APP_URL}/api/metrics/incident/resolve -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${resolveData.replace('"', '\\"')}\""
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
                    commit: env.GIT_COMMIT_SHA ?: 'N/A',
                    status: "failure",
                    is_failure: true
                ])
                bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${failData.replace('"', '\\"')}\""

                def incidentData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    incident_id: env.BUILD_ID,
                    start_time: failureTime,
                    commit: env.GIT_COMMIT_SHA ?: 'N/A',
                    status: "open",
                    description: "Pipeline failed in Build #${env.BUILD_NUMBER}"
                ])
                bat "curl -X POST ${APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${incidentData.replace('"', '\\"')}\""
            }
        }

        always {
            script {
                try {
                    def pipelineEndTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    long endEpoch = (long) (System.currentTimeMillis() / 1000)
                    long startEpoch = env.PIPELINE_START_EPOCH ? env.PIPELINE_START_EPOCH.toLong() : endEpoch
                    def duration = endEpoch - startEpoch

                    echo "⏱️ Pipeline Duration: ${duration}s"
                    echo "🕐 Started: ${env.PIPELINE_START_TIME} | Ended: ${pipelineEndTime}"
                } catch (Exception e) {
                    echo "⚠️ Could not calculate final duration: ${e.message}"
                }
            }
        }
    }
}
