// ============================================
// JENKINSFILE CON INTEGRACIÓN DE MÉTRICAS DORA
// ============================================
// Este Jenkinsfile captura automáticamente:
// - Deployment Frequency (DF)
// - Lead Time for Changes (LTFC)
// - Change Failure Rate (CFR)
// - Mean Time to Recovery (MTTR)
//
// Y envía los datos a tu API Laravel en:
// POST http://localhost:8000/api/metrics/{type}

pipeline {
    agent any
    
    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    environment {
        // URL base de tu API Laravel
        METRICS_API_URL = 'http://localhost:8000/api/metrics'
        
        // API Key (configura en Jenkins → Manage Jenkins → Configure System)
        METRICS_API_KEY = credentials('metrics-api-key')
        
        // Información del tool
        METRICS_TOOL = 'jenkins'
        
        // Variables de timing
        BUILD_START_TIME = sh(script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"', returnStdout: true).trim()
        GIT_COMMIT_TIME = ''
        BUILD_END_TIME = ''
        DEPLOY_START_TIME = ''
        DEPLOY_END_TIME = ''
    }
    
    // ============================================
    // TRIGGERS
    // ============================================
    triggers {
        // GitHub webhook - se dispara automáticamente al push
        githubPush()
        
        // Poll SCM cada 5 minutos (backup si webhook falla)
        pollSCM('H/5 * * * *')
    }
    
    // ============================================
    // OPCIONES
    // ============================================
    options {
        timestamps()
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }
    
    // ============================================
    // STAGES (CICLO DE VIDA)
    // ============================================
    stages {
        // ============================================
        // STAGE 1: CHECKOUT (Análisis)
        // ============================================
        stage('Checkout') {
            steps {
                script {
                    echo "🔍 Checkout: Descargando código..."
                    
                    checkout scm
                    
                    // Capturar fecha/hora exacta del último commit
                    GIT_COMMIT_TIME = sh(
                        script: 'git log -1 --format=%ai ${GIT_COMMIT}',
                        returnStdout: true
                    ).trim()
                    
                    echo "📌 Commit timestamp: ${GIT_COMMIT_TIME}"
                    echo "📌 Commit SHA: ${GIT_COMMIT}"
                    echo "📌 Branch: ${GIT_BRANCH}"
                }
            }
        }
        
        // ============================================
        // STAGE 2: SETUP DOCKER
        // ============================================
        stage('Setup Environment') {
            steps {
                script {
                    echo "🐳 Docker: Preparando ambiente..."
                    
                    sh '''
                        cd ${WORKSPACE}
                        echo "Workspace: ${WORKSPACE}"
                        echo "Git Commit: ${GIT_COMMIT}"
                        docker compose --version
                        docker --version
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 3: ANÁLISIS - Code Quality
        // ============================================
        stage('Code Analysis') {
            steps {
                script {
                    echo "📊 Análisis: Revisando código..."
                    
                    // Enviar timestamp de inicio de análisis a Jira
                    def analysisStart = sh(
                        script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"',
                        returnStdout: true
                    ).trim()
                    
                    // Análisis estático (ejemplo con PHP)
                    sh '''
                        echo "📌 PHP Linting..."
                        find app -name "*.php" -exec php -l {} \\; | grep -i error && exit 1 || true
                        
                        echo "📌 Code Style Check..."
                        # Si tienes pint o phpcs
                        # ./vendor/bin/pint --test 2>/dev/null || true
                    '''
                    
                    def analysisEnd = sh(
                        script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"',
                        returnStdout: true
                    ).trim()
                    
                    // Registrar análisis en tu API
                    sh '''
                        curl -X POST ${METRICS_API_URL}/analysis \
                            -H "X-API-Key: ${METRICS_API_KEY}" \
                            -H "Content-Type: application/json" \
                            -d '{
                                "tool": "${METRICS_TOOL}",
                                "build_number": '${BUILD_NUMBER}',
                                "commit_sha": "'${GIT_COMMIT}'",
                                "branch": "'${GIT_BRANCH}'",
                                "timestamp": "'${analysisEnd}'",
                                "status": "completed"
                            }' || echo "⚠️ Warning: Could not send analysis metrics"
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 4: PRUEBAS
        // ============================================
        stage('Test') {
            steps {
                script {
                    echo "🧪 Pruebas: Ejecutando tests..."
                    
                    def testStart = sh(
                        script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"',
                        returnStdout: true
                    ).trim()
                    
                    sh '''
                        cd ${WORKSPACE}
                        
                        echo "📌 Iniciando pruebas en Docker..."
                        docker compose exec -T app php artisan test --parallel \
                            --coverage --coverage-clover=coverage.xml 2>&1 | tee test-output.log
                        
                        # Capturar resultados
                        if [ -f coverage.xml ]; then
                            COVERAGE=$(grep -oP 'line-rate="\\K[0-9.]+' coverage.xml | head -1)
                            echo "Coverage: $COVERAGE"
                        fi
                        
                        # Contar tests
                        TOTAL_TESTS=$(grep -o "Tests: [0-9]*" test-output.log | awk '{print $2}' || echo "0")
                        PASSED_TESTS=$(grep -o "passed" test-output.log | wc -l)
                        FAILED_TESTS=$(grep -o "failed" test-output.log | wc -l)
                        
                        echo "TOTAL_TESTS=$TOTAL_TESTS" > test-results.properties
                        echo "PASSED_TESTS=$PASSED_TESTS" >> test-results.properties
                        echo "FAILED_TESTS=$FAILED_TESTS" >> test-results.properties
                    '''
                    
                    // Cargar propiedades
                    load("${WORKSPACE}/test-results.properties")
                    
                    def testEnd = sh(
                        script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"',
                        returnStdout: true
                    ).trim()
                    
                    // Enviar resultados de tests a tu API
                    sh '''
                        TOTAL=$(grep "TOTAL_TESTS" test-results.properties | cut -d'=' -f2)
                        PASSED=$(grep "PASSED_TESTS" test-results.properties | cut -d'=' -f2)
                        FAILED=$(grep "FAILED_TESTS" test-results.properties | cut -d'=' -f2)
                        
                        curl -X POST ${METRICS_API_URL}/deployment-result \
                            -H "X-API-Key: ${METRICS_API_KEY}" \
                            -H "Content-Type: application/json" \
                            -d '{
                                "tool": "${METRICS_TOOL}",
                                "build_number": '${BUILD_NUMBER}',
                                "commit_sha": "'${GIT_COMMIT}'",
                                "timestamp": "'${testEnd}'",
                                "is_failure": '$([ $FAILED -gt 0 ] && echo "true" || echo "false")',
                                "total_tests": '$TOTAL',
                                "passed_tests": '$PASSED',
                                "failed_tests": '$FAILED'
                            }' || echo "⚠️ Warning: Could not send test metrics"
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 5: DESPLIEGUE - Staging
        // ============================================
        stage('Deploy to Staging') {
            steps {
                script {
                    echo "🚀 Despliegue: Staging..."
                    
                    DEPLOY_START_TIME = sh(
                        script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"',
                        returnStdout: true
                    ).trim()
                    
                    sh '''
                        cd ${WORKSPACE}
                        
                        echo "📌 Cleaning previous staging containers..."
                        docker compose down -v 2>/dev/null || true
                        
                        echo "📌 Building and starting staging environment..."
                        docker compose up -d --build
                        
                        echo "📌 Waiting for services to be ready..."
                        for i in {1..30}; do
                            if docker compose exec -T app php artisan tinker --execute="echo 'OK'" > /dev/null 2>&1; then
                                echo "✅ Services ready"
                                break
                            fi
                            echo "Attempt $i/30..."
                            sleep 2
                        done
                        
                        echo "📌 Running migrations in staging..."
                        docker compose exec -T app php artisan migrate:fresh --force --seed
                        
                        echo "📌 Health check..."
                        curl -f http://localhost:8000 || exit 1
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 6: PRUEBAS EN STAGING
        // ============================================
        stage('Smoke Tests Staging') {
            steps {
                script {
                    echo "✅ Validación: Smoke tests en staging..."
                    
                    sh '''
                        echo "📌 Testing endpoints..."
                        curl -f http://localhost:8000/api/ping || exit 1
                        echo "✅ API responding"
                        
                        echo "📌 Testing database connection..."
                        docker compose exec -T app php artisan db:show || exit 1
                        
                        echo "✅ Database connected"
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 7: DESPLIEGUE - PRODUCCIÓN
        // ============================================
        stage('Deploy to Production') {
            when {
                branch 'main'  // Solo en rama main
            }
            steps {
                script {
                    echo "🚀 Despliegue: Producción..."
                    
                    sh '''
                        cd ${WORKSPACE}
                        echo "✅ Production deployment approved for branch: ${GIT_BRANCH}"
                        echo "📌 Timestamp: $(date -u +"%Y-%m-%dT%H:%M:%S.000Z")"
                        
                        # En producción real, harías:
                        # docker tag ecommerce-ci-app:latest prod-registry/ecommerce:${BUILD_NUMBER}
                        # docker push prod-registry/ecommerce:${BUILD_NUMBER}
                        # kubectl set image deployment/ecommerce ecommerce=prod-registry/ecommerce:${BUILD_NUMBER}
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 8: VERIFICACIÓN
        // ============================================
        stage('Verify Deployment') {
            steps {
                script {
                    echo "✅ Verificación: Chequeando deployment..."
                    
                    sh '''
                        echo "📌 Container Status:"
                        docker compose ps
                        
                        echo "📌 Health Check:"
                        docker compose exec -T app php artisan tinker --execute="echo 'Laravel OK'" || exit 1
                    '''
                    
                    DEPLOY_END_TIME = sh(
                        script: 'date -u +"%Y-%m-%dT%H:%M:%S.000Z"',
                        returnStdout: true
                    ).trim()
                }
            }
        }
        
        // ============================================
        // STAGE 9: REGISTRAR MÉTRICAS DORA
        // ============================================
        stage('Record DORA Metrics') {
            steps {
                script {
                    echo "📊 Métricas: Registrando DORA metrics..."
                    
                    // Calcular LTFC (Lead Time for Changes)
                    // LTFC = Hora Deploy - Hora Commit
                    
                    sh '''
                        BUILD_END_TIME=$(date -u +"%Y-%m-%dT%H:%M:%S.000Z")
                        
                        echo "═════════════════════════════════════"
                        echo "DORA METRICS - BUILD #${BUILD_NUMBER}"
                        echo "═════════════════════════════════════"
                        echo "Tool: ${METRICS_TOOL}"
                        echo "Commit SHA: ${GIT_COMMIT}"
                        echo "Branch: ${GIT_BRANCH}"
                        echo "═════════════════════════════════════"
                        echo "Timestamps capturados:"
                        echo "  Build Start: ${BUILD_START_TIME}"
                        echo "  Build End: ${BUILD_END_TIME}"
                        echo "  Deploy Start: ${DEPLOY_START_TIME}"
                        echo "  Deploy End: ${DEPLOY_END_TIME}"
                        echo "═════════════════════════════════════"
                        
                        # Enviar evento de DEPLOYMENT
                        curl -X POST ${METRICS_API_URL}/deployment \
                            -H "X-API-Key: ${METRICS_API_KEY}" \
                            -H "Content-Type: application/json" \
                            -d '{
                                "tool": "${METRICS_TOOL}",
                                "build_number": '${BUILD_NUMBER}',
                                "commit_sha": "'${GIT_COMMIT}'",
                                "branch": "'${GIT_BRANCH}'",
                                "timestamp": "'${BUILD_END_TIME}'",
                                "duration_seconds": 0
                            }' || echo "⚠️ Warning: Could not send deployment metrics"
                        
                        # Enviar LEAD TIME FOR CHANGES
                        curl -X POST ${METRICS_API_URL}/leadtime \
                            -H "X-API-Key: ${METRICS_API_KEY}" \
                            -H "Content-Type: application/json" \
                            -d '{
                                "tool": "${METRICS_TOOL}",
                                "build_number": '${BUILD_NUMBER}',
                                "commit_sha": "'${GIT_COMMIT}'",
                                "timestamp": "'${BUILD_END_TIME}'",
                                "commit_timestamp": "'${GIT_COMMIT_TIME}'",
                                "deploy_timestamp": "'${DEPLOY_END_TIME}'",
                                "lead_time_seconds": 0
                            }' || echo "⚠️ Warning: Could not send LTFC metrics"
                    '''
                    
                    echo "✅ Métricas registradas en Laravel API"
                }
            }
        }
    }
    
    // ============================================
    // POST-BUILD ACTIONS
    // ============================================
    post {
        always {
            script {
                echo "🧹 Limpieza: Post-build actions..."
                
                // Guardar logs
                sh '''
                    mkdir -p ${WORKSPACE}/build-logs
                    echo "Build: ${BUILD_NUMBER}" > ${WORKSPACE}/build-logs/summary.txt
                    echo "Status: ${currentBuild.result}" >> ${WORKSPACE}/build-logs/summary.txt
                    echo "Duration: ${currentBuild.durationString}" >> ${WORKSPACE}/build-logs/summary.txt
                '''
                
                // Archivar resultados
                archiveArtifacts artifacts: 'test-output.log,coverage.xml,build-logs/**', allowEmptyArchive: true
                
                // Limpiar workspace
                cleanWs()
            }
        }
        
        success {
            script {
                echo "✅ BUILD EXITOSO"
                
                // Enviar notificación (opcional)
                sh '''
                    echo "✅ Pipeline completado exitosamente"
                    # slack o email notification aquí
                '''
            }
        }
        
        failure {
            script {
                echo "❌ BUILD FALLÓ"
                
                // Registrar fallo en MTTR
                sh '''
                    FAILURE_TIME=$(date -u +"%Y-%m-%dT%H:%M:%S.000Z")
                    
                    curl -X POST ${METRICS_API_URL}/incident \
                        -H "X-API-Key: ${METRICS_API_KEY}" \
                        -H "Content-Type: application/json" \
                        -d '{
                            "tool": "${METRICS_TOOL}",
                            "build_number": '${BUILD_NUMBER}',
                            "type": "build_failure",
                            "timestamp": "'${FAILURE_TIME}'",
                            "severity": "high",
                            "description": "Build #${BUILD_NUMBER} failed"
                        }' || echo "⚠️ Warning: Could not send incident metrics"
                '''
            }
        }
    }
}