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
        // STAGE 1: CHECKOUT & CAPTURE START TIME //
        // ============================================
        stage('Checkout & Info') {
            steps {
                script {
                    echo "📦 Checking out code..."

                    // Capturar SHA del commit
                    env.GIT_COMMIT_SHA = bat(script: "git rev-parse HEAD", returnStdout: true).trim().split('\r\n').last()

                    // Capturar tiempo del commit (para Lead Time real)
                    def commitTimeRaw = bat(script: "git show -s --format=%cI ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                    env.COMMIT_TIME = commitTimeRaw.split('\r\n').last()

                    // Capturar timestamp de inicio del pipeline
                    env.PIPELINE_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    env.PIPELINE_START_EPOCH = (System.currentTimeMillis() / 1000).toLong().toString()

                    echo "📝 Commit SHA: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️  Commit Time: ${env.COMMIT_TIME}"
                    echo "🕐 Pipeline Started: ${env.PIPELINE_START_TIME}"
                }
            }
        }

        // ============================================
        // STAGE 2: INSTALL DEPENDENCIES
        // ============================================
        stage('Install Dependencies') {
            steps {
                echo "📦 Installing dependencies..."
                bat 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                bat 'copy .env.example .env /Y'
                bat 'php artisan key:generate'
            }
        }

        // ============================================
        // STAGE 3: RUN TESTS
        // ============================================
        stage('Run Tests') {
            steps {
                echo "🧪 Running tests..."
                bat 'php artisan test'
            }
        }

        // ============================================
        // STAGE 4: DEPLOY TO RAILWAY
        // ============================================
        stage('Deploy to Railway') {
            steps {
                script {
                    echo "🚀 Deploying to Railway..."
                    echo "📦 Commit: ${env.GIT_COMMIT_SHA}"

                    // Capturar inicio del deploy
                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    env.DEPLOY_START_EPOCH = (System.currentTimeMillis() / 1000).toLong().toString()

                    echo "⏳ Railway auto-deploys via GitHub webhook..."
                    echo "🔍 Verifying deployment availability..."

                    // Verificación de deployment con retry inteligente
                    def maxAttempts = 30
                    def attempt = 0
                    def deploySuccess = false

                    while (attempt < maxAttempts && !deploySuccess) {
                        attempt++

                        try {
                            def response = bat(
                                script: "curl -s -o nul -w \"%%{http_code}\" ${APP_URL}/api/ping",
                                returnStdout: true
                            ).trim()

                            def httpCode = response.split('\r\n').last()

                            if (httpCode == '200') {
                                echo "✅ Railway deployment is live! (HTTP 200)"
                                deploySuccess = true

                                // Capturar fin del deploy
                                env.DEPLOY_END_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                                env.DEPLOY_END_EPOCH = (System.currentTimeMillis() / 1000).toLong().toString()
                            } else {
                                echo "⏳ Attempt ${attempt}/${maxAttempts} - HTTP ${httpCode} - Retrying in 5s..."
                                if (attempt < maxAttempts) {
                                    sleep(5)
                                }
                            }
                        } catch (Exception e) {
                            echo "⚠️  Attempt ${attempt}/${maxAttempts} - Connection failed - Retrying..."
                            if (attempt < maxAttempts) {
                                sleep(5)
                            }
                        }
                    }

                    if (!deploySuccess) {
                        error("❌ Deployment verification failed after ${maxAttempts} attempts")
                    }

                    echo "✅ Deploy completed at: ${env.DEPLOY_END_TIME}"
                }
            }
        }

        // ============================================
        // STAGE 5: TRACK DORA METRICS
        // ============================================
        stage('Track DORA Metrics') {
            steps {
                script {
                    echo "📊 Recording DORA Metrics..."

                    // ============================================
                    // MÉTRICA 1: DEPLOYMENT FREQUENCY
                    // ============================================
                    echo "📊 Recording Deployment Frequency..."
                    def deploymentData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        branch: env.BRANCH_NAME ?: 'master',
                        commit: env.GIT_COMMIT_SHA,
                        status: "success"
                    ])

                    bat """curl -X POST ${APP_URL}/api/metrics/deployment ^
                        -H "Content-Type: application/json" ^
                        -H "X-API-Key: ${METRICS_API_KEY}" ^
                        -d "${deploymentData.replace('"', '\\"')}" """

                    echo "✅ Deployment frequency recorded"

                    // ============================================
                    // MÉTRICA 2: LEAD TIME FOR CHANGES (REAL)
                    // ============================================
                    echo "📊 Calculating Real Lead Time..."

                    // Calcular Lead Time real
                    def commitEpoch = bat(
                        script: "@echo off & git show -s --format=%ct ${env.GIT_COMMIT_SHA}",
                        returnStdout: true
                    ).trim().split('\r\n').last().toLong()

                    def deployEpoch = env.DEPLOY_END_EPOCH.toLong()
                    def leadTimeSeconds = deployEpoch - commitEpoch
                    def leadTimeMinutes = leadTimeSeconds / 60
                    def leadTimeHours = String.format("%.2f", leadTimeSeconds / 3600.0)

                    echo "=================================="
                    echo "📊 REAL LEAD TIME CALCULATION"
                    echo "=================================="
                    echo "⏱️  Commit Time: ${env.COMMIT_TIME}"
                    echo "⏱️  Deploy End Time: ${env.DEPLOY_END_TIME}"
                    echo "⏱️  Lead Time: ${leadTimeSeconds} seconds"
                    echo "⏱️  Lead Time: ${leadTimeMinutes} minutes"
                    echo "⏱️  Lead Time: ${leadTimeHours} hours"
                    echo "=================================="

                    def leadTimeData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        commit: env.GIT_COMMIT_SHA,
                        commit_time: env.COMMIT_TIME,
                        deploy_time: env.DEPLOY_END_TIME,
                        lead_time_seconds: leadTimeSeconds
                    ])

                    bat """curl -X POST ${APP_URL}/api/metrics/leadtime ^
                        -H "Content-Type: application/json" ^
                        -H "X-API-Key: ${METRICS_API_KEY}" ^
                        -d "${leadTimeData.replace('"', '\\"')}" """

                    echo "✅ Real lead time recorded: ${leadTimeSeconds}s (${leadTimeMinutes}m)"

                    // ============================================
                    // MÉTRICA 3: CHANGE FAILURE RATE (Success)
                    // ============================================
                    echo "📊 Recording Deployment Success..."
                    def successData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        timestamp: env.DEPLOY_END_TIME,
                        commit: env.GIT_COMMIT_SHA,
                        status: "success",
                        is_failure: false
                    ])

                    bat """curl -X POST ${APP_URL}/api/metrics/deployment-result ^
                        -H "Content-Type: application/json" ^
                        -H "X-API-Key: ${METRICS_API_KEY}" ^
                        -d "${successData.replace('"', '\\"')}" """

                    echo "✅ Deployment success recorded"

                    // ============================================
                    // MÉTRICA 4: MEAN TIME TO RECOVERY (Auto-resolve)
                    // ============================================
                    echo "📊 Auto-resolving previous incidents..."
                    def resolveData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        resolution_time: env.DEPLOY_END_TIME
                    ])

                    def resolveResponse = bat(
                        script: """curl -s -X POST ${APP_URL}/api/metrics/incident/resolve ^
                            -H "Content-Type: application/json" ^
                            -H "X-API-Key: ${METRICS_API_KEY}" ^
                            -d "${resolveData.replace('"', '\\"')}" """,
                        returnStdout: true
                    ).trim()

                    echo "Response: ${resolveResponse}"

                    if (resolveResponse.contains('resolved')) {
                        echo "✅ Previous incident resolved"
                    } else {
                        echo "ℹ️  No open incidents to resolve"
                    }
                }
            }
        }

        // ============================================
        // STAGE 6: DISPLAY METRICS SUMMARY
        // ============================================
        stage('Metrics Summary') {
            steps {
                script {
                    echo "=========================================="
                    echo "📊 DORA METRICS - DEPLOYMENT SUMMARY"
                    echo "=========================================="
                    echo "🚀 Deployment Status: SUCCESS"
                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo "🌿 Branch: ${env.BRANCH_NAME ?: 'master'}"
                    echo ""
                    echo "✅ Metrics Recorded:"
                    echo "  • Deployment Frequency ✓"
                    echo "  • Lead Time for Changes ✓ (Real time from commit to deploy)"
                    echo "  • Change Failure Rate ✓"
                    echo "  • Mean Time to Recovery ✓"
                    echo "=========================================="
                    echo ""
                    echo "📈 View full DORA metrics:"
                    echo "${APP_URL}/api/metrics/dora?tool=jenkins&period=30"
                    echo "=========================================="

                    // Opcional: Obtener y mostrar métricas actuales
                    try {
                        def metricsResponse = bat(
                            script: """curl -s -X GET "${APP_URL}/api/metrics/dora?tool=jenkins&period=30" ^
                                -H "X-API-Key: ${METRICS_API_KEY}" """,
                            returnStdout: true
                        ).trim()

                        echo ""
                        echo "📊 Current DORA Performance (Last 30 days):"
                        echo "=========================================="
                        echo metricsResponse
                        echo "=========================================="
                    } catch (Exception e) {
                        echo "⚠️  Could not fetch current metrics"
                    }
                }
            }
        }
    }

    // ============================================
    // POST-BUILD ACTIONS
    // ============================================
    post {
        failure {
            script {
                echo "❌ Pipeline FAILED - Recording failure metrics..."
                def failureTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                // ============================================
                // MÉTRICA 3: CHANGE FAILURE RATE (Failure)
                // ============================================
                echo "📊 Recording deployment failure..."
                def failData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    timestamp: failureTime,
                    commit: env.GIT_COMMIT_SHA,
                    status: "failure",
                    is_failure: true
                ])

                bat """curl -X POST ${APP_URL}/api/metrics/deployment-result ^
                    -H "Content-Type: application/json" ^
                    -H "X-API-Key: ${METRICS_API_KEY}" ^
                    -d "${failData.replace('"', '\\"')}" """

                echo "❌ Deployment failure recorded"

                // ============================================
                // MÉTRICA 4: MEAN TIME TO RECOVERY (Create Incident)
                // ============================================
                echo "📊 Creating incident..."
                def incidentData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    incident_id: env.BUILD_ID,
                    start_time: failureTime,
                    commit: env.GIT_COMMIT_SHA,
                    status: "open",
                    description: "Pipeline failed in stage: ${env.STAGE_NAME} - Build #${env.BUILD_NUMBER}"
                ])

                bat """curl -X POST ${APP_URL}/api/metrics/incident ^
                    -H "Content-Type: application/json" ^
                    -H "X-API-Key: ${METRICS_API_KEY}" ^
                    -d "${incidentData.replace('"', '\\"')}" """

                echo "🚨 Incident created for failed deployment"

                echo "=========================================="
                echo "❌ DEPLOYMENT FAILED"
                echo "=========================================="
                echo "Build Number: ${env.BUILD_NUMBER}"
                echo "Commit: ${env.GIT_COMMIT_SHA}"
                echo "Failed Stage: ${env.STAGE_NAME}"
                echo "Incident ID: ${env.BUILD_ID}"
                echo "=========================================="
            }
        }

        success {
            script {
                echo "=========================================="
                echo "✅ DEPLOYMENT SUCCESSFUL"
                echo "=========================================="
                echo "Build Number: ${env.BUILD_NUMBER}"
                echo "Commit: ${env.GIT_COMMIT_SHA}"
                echo "Deployed to: ${APP_URL}"
                echo "=========================================="
            }
        }

        always {
            script {
                def pipelineEndTime = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                def pipelineEndEpoch = (System.currentTimeMillis() / 1000).toLong()
                def totalDuration = pipelineEndEpoch - env.PIPELINE_START_EPOCH.toLong()

                echo ""
                echo "⏱️  Pipeline Duration: ${totalDuration} seconds (${totalDuration / 60} minutes)"
                echo "🕐 Pipeline Started: ${env.PIPELINE_START_TIME}"
                echo "🕐 Pipeline Ended: ${pipelineEndTime}"
            }
        }
    }
}
