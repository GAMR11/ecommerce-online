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

                    // SOLUCIÓN AL ERROR: Capturamos solo el timestamp (segundos) para evitar errores de formato --pretty
                    // %ct devuelve el tiempo del commit en formato Unix (ej: 1737845553)
                    def commitTimestamp = bat(script: "@echo off & git show -s --format=%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()

                    // Convertimos ese número a una fecha ISO 8601 real usando Groovy
                    long commitSeconds = commitTimestamp.toLong()
                    env.COMMIT_TIME = new Date(commitSeconds * 1000).format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    // Timestamp de inicio del pipeline
                    env.PIPELINE_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    long startEpoch = (long) (System.currentTimeMillis() / 1000)
                    env.PIPELINE_START_EPOCH = startEpoch.toString()

                    echo "📝 Commit SHA: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️  Commit Time (ISO): ${env.COMMIT_TIME}"
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

                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def maxAttempts = 30
                    def attempt = 0
                    def deploySuccess = false

                    while (attempt < maxAttempts && !deploySuccess) {
                        attempt++
                        try {
                            def response = bat(
                                script: "@echo off & curl -s -o nul -w \"%%{http_code}\" ${APP_URL}/api/ping",
                                returnStdout: true
                            ).trim()

                            if (response == '200') {
                                echo "✅ Railway deployment is live!"
                                deploySuccess = true
                                env.DEPLOY_END_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                                long deployEnd = (long) (System.currentTimeMillis() / 1000)
                                env.DEPLOY_END_EPOCH = deployEnd.toString()
                            } else {
                                echo "⏳ Attempt ${attempt}/${maxAttempts} - HTTP ${response}..."
                                sleep(5)
                            }
                        } catch (Exception e) {
                            echo "⚠️ Connection failed - Retrying..."
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

                    // 3. CHANGE FAILURE RATE
                    def successData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        commit: env.GIT_COMMIT_SHA,
                        status: "success",
                        is_failure: false
                    ])
                    bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${successData.replace('"', '\\"')}\""
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

                def incidentData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    incident_id: env.BUILD_ID,
                    start_time: failureTime,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
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
                    echo "⏱️ Pipeline Duration: ${endEpoch - startEpoch}s"
                } catch (Exception e) {
                    echo "⚠️ Could not calculate duration"
                }
            }
        }
    }
}
