import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        METRICS_API_KEY = credentials('METRICS_API_KEY')
        APP_URL         = credentials('APP_URL')
        TOOL_NAME       = 'jenkins'
        PHP_BIN         = "C:\\laragon\\bin\\php\\php-8.2.30-nts-Win32-vs16-x64\\php.exe"

        // ✅ NUEVO: Directorios de caché
        COMPOSER_CACHE_DIR = "C:\\Jenkins\\composer-cache"
        COMPOSER_HOME      = "C:\\Jenkins\\composer-home"
    }

    stages {
        // ============================================
        // STAGE 1: CHECKOUT & INFO
        // ============================================
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

        // ============================================
        // STAGE 2: SETUP CACHE (NUEVO)
        // ============================================
        stage('Setup Cache') {
            steps {
                script {
                    echo "🗂️  Setting up Composer cache..."

                    // Crear directorios de caché si no existen
                    bat """
                        @echo off
                        if not exist "${env.COMPOSER_CACHE_DIR}" (
                            echo Creating Composer cache directory...
                            mkdir "${env.COMPOSER_CACHE_DIR}"
                        ) else (
                            echo ✅ Composer cache directory exists
                        )

                        if not exist "${env.COMPOSER_HOME}" (
                            echo Creating Composer home directory...
                            mkdir "${env.COMPOSER_HOME}"
                        ) else (
                            echo ✅ Composer home directory exists
                        )
                    """

                    echo "✅ Cache directories ready"
                }
            }
        }

        // ============================================
        // STAGE 3: INSTALL DEPENDENCIES (MEJORADO CON CACHE)
        // ============================================
        stage('Install Dependencies') {
            steps {
                echo "📦 Installing dependencies with PHP 8.2 (using cache)..."
                script {
                    def startTime = System.currentTimeMillis()

                    // Configurar variables de entorno de Composer para usar caché
                    bat """
                        @echo off
                        echo Setting Composer cache environment...
                        set COMPOSER_CACHE_DIR=${env.COMPOSER_CACHE_DIR}
                        set COMPOSER_HOME=${env.COMPOSER_HOME}

                        echo Running composer install with cache...
                        \"${env.PHP_BIN}\" \"C:\\ProgramData\\ComposerSetup\\bin\\composer.phar\" install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
                    """

                    def endTime = System.currentTimeMillis()
                    def duration = (endTime - startTime) / 1000
                    echo "⏱️  Composer install completed in ${duration} seconds"

                    // Preparar Laravel
                    bat "if not exist .env copy .env.example .env"
                    bat "\"${env.PHP_BIN}\" artisan key:generate"

                    echo "✅ Dependencies installed successfully"
                }
            }
        }

        // ============================================
        // STAGE 4: RUN TESTS
        // ============================================
        stage('Run Tests') {
            steps {
                echo "🧪 Running tests..."
                bat "\"${env.PHP_BIN}\" artisan test"
            }
        }

        // ============================================
        // STAGE 5: DEPLOY TO RAILWAY (OPTIMIZADO)
        // ============================================
        stage('Deploy to Railway') {
            steps {
                script {
                    echo "🚀 Deploying to Railway (Verification)..."
                    env.DEPLOY_START_TIME = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    def maxAttempts = 20  // Reducido de 30 a 20
                    def waitSeconds = 4   // Reducido de 5 a 4
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
                                echo "✅ Deploy verificado con éxito en intento ${attempt}"
                            } else {
                                echo "⏳ Intento ${attempt}/${maxAttempts}: HTTP ${response} - Retrying..."
                                if (attempt < maxAttempts) {
                                    sleep(waitSeconds)
                                }
                            }
                        } catch (e) {
                            echo "⚠️ Intento ${attempt}: ${e.message}"
                            if (attempt < maxAttempts) {
                                sleep(waitSeconds)
                            }
                        }
                    }

                    if (!deploySuccess) {
                        error("❌ Railway no respondió después de ${maxAttempts} intentos.")
                    }
                }
            }
        }

        // ============================================
        // STAGE 6: TRACK DORA METRICS
        // ============================================
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
                    long leadTimeMinutes = leadTimeSeconds / 60

                    echo "=================================="
                    echo "📊 REAL LEAD TIME CALCULATION"
                    echo "=================================="
                    echo "⏱️  Commit Time: ${env.COMMIT_TIME}"
                    echo "⏱️  Deploy End Time: ${env.DEPLOY_END_TIME}"
                    echo "⏱️  Lead Time: ${leadTimeSeconds} seconds"
                    echo "⏱️  Lead Time: ${leadTimeMinutes} minutes"
                    echo "=================================="

                    def leadTimeData = JsonOutput.toJson([
                        tool: TOOL_NAME,
                        commit: env.GIT_COMMIT_SHA,
                        commit_time: env.COMMIT_TIME,
                        deploy_time: env.DEPLOY_END_TIME,
                        lead_time_seconds: leadTimeSeconds
                    ])

                    bat "curl -X POST ${APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${leadTimeData.replace('"', '\\"')}\""
                    echo "✅ Lead time recorded: ${leadTimeSeconds}s (${leadTimeMinutes}m)"

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
                        def resolveResponse = bat(
                            script: "curl -s -X POST ${APP_URL}/api/metrics/incident/resolve -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${resolveData.replace('"', '\\"')}\"",
                            returnStdout: true
                        ).trim()

                        if (resolveResponse.contains("resolved")) {
                            echo "✅ Previous incident resolved"
                        } else {
                            echo "ℹ️  No open incidents to resolve"
                        }
                    } catch (e) {
                        echo "ℹ️  No incidents to resolve"
                    }
                }
            }
        }

        // ============================================
        // STAGE 7: METRICS SUMMARY
        // ============================================
        stage('Metrics Summary') {
            steps {
                script {
                    def pipelineEndEpoch = (System.currentTimeMillis() / 1000).toLong()
                    def totalDuration = pipelineEndEpoch - env.PIPELINE_START_EPOCH.toLong()
                    def totalMinutes = totalDuration / 60

                    echo "=========================================="
                    echo "📊 DORA METRICS - DEPLOYMENT SUMMARY"
                    echo "=========================================="
                    echo "✅ Deployment Status: SUCCESS"
                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️  Total Pipeline Duration: ${totalDuration}s (${totalMinutes}m)"
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

        // ============================================
        // STAGE 8: CACHE STATS (OPCIONAL - INFORMATIVO)
        // ============================================
        stage('Cache Statistics') {
            steps {
                script {
                    echo "📊 Composer Cache Statistics..."

                    try {
                        bat """
                            @echo off
                            echo ========================================
                            echo Composer Cache Directory: ${env.COMPOSER_CACHE_DIR}
                            dir "${env.COMPOSER_CACHE_DIR}" /s | find "File(s)"
                            echo ========================================
                        """
                    } catch (e) {
                        echo "ℹ️  Could not retrieve cache stats"
                    }
                }
            }
        }
    }

    // ============================================
    // POST-BUILD ACTIONS
    // ============================================
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
                    is_failure: true,
                    failure_stage: "jenkins-pipeline"
                ])

                bat "curl -X POST ${APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${failData.replace('"', '\\"')}\""

                // Crear incidente
                def incidentData = JsonOutput.toJson([
                    tool: TOOL_NAME,
                    incident_id: env.BUILD_ID,
                    start_time: failureTime,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
                    status: "open",
                    description: "Pipeline failed in Jenkins - Build #${env.BUILD_NUMBER}"
                ])

                bat "curl -X POST ${APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -H \"X-API-Key: ${METRICS_API_KEY}\" -d \"${incidentData.replace('"', '\\"')}\""

                echo "❌ Failure metrics recorded"
                echo "=========================================="
                echo "❌ JENKINS PIPELINE FAILED"
                echo "=========================================="
                echo "Build: #${env.BUILD_NUMBER}"
                echo "Commit: ${env.GIT_COMMIT_SHA}"
                echo "Failed Stage: ${env.STAGE_NAME}"
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
                echo "🕐 Pipeline Started: ${env.PIPELINE_START_EPOCH}"
                echo "🕐 Pipeline Ended: ${pipelineEndTime}"
            }
        }
    }
}
