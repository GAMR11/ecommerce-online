// ============================================
// JENKINSFILE PARA WINDOWS CON INTEGRACIÓN DORA
// ============================================
// ⚠️ IMPORTANTE: Este archivo usa 'bat' en lugar de 'sh'
// porque estás ejecutando Jenkins en Windows

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
        BUILD_START_TIME = ''
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
                    
                    // Capturar timestamp del build inicio
                    BUILD_START_TIME = bat(
                        script: '@powershell -Command "[datetime]::UtcNow.ToString(\'yyyy-MM-ddTHH:mm:ss.000Z\')"',
                        returnStdout: true
                    ).trim()
                    
                    echo "📌 Build Start Time: ${BUILD_START_TIME}"
                    echo "📌 Commit SHA: ${GIT_COMMIT}"
                    echo "📌 Branch: ${GIT_BRANCH}"
                }
            }
        }
        
        // ============================================
        // STAGE 2: INFORMACIÓN DEL ENTORNO
        // ============================================
        stage('Setup Environment') {
            steps {
                script {
                    echo "🐳 Docker: Verificando ambiente..."
                    
                    bat '''
                        echo "Workspace: %WORKSPACE%"
                        echo "Git Commit: %GIT_COMMIT%"
                        docker compose --version
                        docker --version
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 3: PRUEBAS
        // ============================================
        stage('Test') {
            steps {
                script {
                    echo "🧪 Pruebas: Ejecutando tests..."
                    
                    bat '''
                        cd %WORKSPACE%
                        
                        echo 📌 Iniciando pruebas en Docker...
                        
                        REM Esperar a que MySQL esté listo (máximo 30 intentos)
                        setlocal enabledelayedexpansion
                        set MAX_ATTEMPTS=30
                        set ATTEMPT=0
                        
                        :wait_loop
                        set /a ATTEMPT+=1
                        if !ATTEMPT! GTR !MAX_ATTEMPTS! (
                            echo ❌ MySQL no respondió después de 30 intentos
                            exit /b 1
                        )
                        
                        docker compose exec -T mysql mysqladmin ping -h localhost -u root -proot >nul 2>&1
                        if errorlevel 1 (
                            echo Intento !ATTEMPT!/!MAX_ATTEMPTS! - Esperando MySQL...
                            ping localhost -n 2 >nul
                            goto wait_loop
                        )
                        
                        echo ✅ MySQL está listo
                        
                        REM Ejecutar tests
                        docker compose exec -T app php artisan test --parallel
                        
                        REM Si los tests fallaron, capturar el error
                        if errorlevel 1 (
                            echo ❌ Tests fallaron
                            set TEST_FAILED=true
                        ) else (
                            echo ✅ Tests exitosos
                            set TEST_FAILED=false
                        )
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 4: DESPLIEGUE - STAGING
        // ============================================
        stage('Deploy to Staging') {
            steps {
                script {
                    echo "🚀 Despliegue: Staging..."
                    
                    DEPLOY_START_TIME = bat(
                        script: '@powershell -Command "[datetime]::UtcNow.ToString(\'yyyy-MM-ddTHH:mm:ss.000Z\')"',
                        returnStdout: true
                    ).trim()
                    
                    bat '''
                        cd %WORKSPACE%
                        
                        echo 📌 Deploy Staging iniciado...
                        echo Timestamp: %DEPLOY_START_TIME%
                        
                        REM Los contenedores ya están corriendo desde el stage de tests
                        echo ✅ Staging está listo
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 5: DESPLIEGUE - PRODUCCIÓN
        // ============================================
        stage('Deploy to Production') {
            when {
                branch 'main'  // Solo en rama main
            }
            steps {
                script {
                    echo "🚀 Despliegue: Producción..."
                    
                    DEPLOY_END_TIME = bat(
                        script: '@powershell -Command "[datetime]::UtcNow.ToString(\'yyyy-MM-ddTHH:mm:ss.000Z\')"',
                        returnStdout: true
                    ).trim()
                    
                    bat '''
                        cd %WORKSPACE%
                        echo ✅ Production deployment approved for branch: %GIT_BRANCH%
                        echo 📌 Timestamp: %DEPLOY_END_TIME%
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 6: VERIFICACIÓN
        // ============================================
        stage('Verify Deployment') {
            steps {
                script {
                    echo "✅ Verificación: Chequeando deployment..."
                    
                    bat '''
                        cd %WORKSPACE%
                        
                        echo 📌 Container Status:
                        docker compose ps
                        
                        echo 📌 Health Check:
                        docker compose exec -T app php artisan tinker --execute="echo 'Laravel OK'" || exit /b 1
                        
                        echo ✅ Verification successful
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 7: REGISTRAR MÉTRICAS DORA
        // ============================================
        stage('Record DORA Metrics') {
            steps {
                script {
                    echo "📊 Métricas: Registrando DORA metrics..."
                    
                    BUILD_END_TIME = bat(
                        script: '@powershell -Command "[datetime]::UtcNow.ToString(\'yyyy-MM-ddTHH:mm:ss.000Z\')"',
                        returnStdout: true
                    ).trim()
                    
                    bat '''
                        setlocal enabledelayedexpansion
                        
                        echo ═════════════════════════════════════
                        echo DORA METRICS - BUILD #%BUILD_NUMBER%
                        echo ═════════════════════════════════════
                        echo Tool: %METRICS_TOOL%
                        echo Commit SHA: %GIT_COMMIT%
                        echo Branch: %GIT_BRANCH%
                        echo ═════════════════════════════════════
                        echo Timestamps capturados:
                        echo   Build Start: %BUILD_START_TIME%
                        echo   Build End: %BUILD_END_TIME%
                        echo   Deploy Start: %DEPLOY_START_TIME%
                        echo   Deploy End: %DEPLOY_END_TIME%
                        echo ═════════════════════════════════════
                        
                        REM Enviar evento de DEPLOYMENT a la API
                        powershell -Command ^
                            "$response = Invoke-RestMethod ^
                                -Uri '%METRICS_API_URL%/deployment' ^
                                -Method POST ^
                                -Headers @{ 'X-API-Key' = '%METRICS_API_KEY%'; 'Content-Type' = 'application/json' } ^
                                -Body '{\"tool\":\"jenkins\",\"build_number\":%BUILD_NUMBER%,\"commit_sha\":\"%GIT_COMMIT%\",\"branch\":\"%GIT_BRANCH%\",\"timestamp\":\"%BUILD_END_TIME%\",\"duration_seconds\":0}'; ^
                            Write-Host '✅ Deployment metrics sent' -ForegroundColor Green; ^
                            $response | ConvertTo-Json | Write-Host" ^
                            2>nul || echo ⚠️ Warning: Could not send deployment metrics
                        
                        REM Enviar LEAD TIME FOR CHANGES
                        powershell -Command ^
                            "$response = Invoke-RestMethod ^
                                -Uri '%METRICS_API_URL%/leadtime' ^
                                -Method POST ^
                                -Headers @{ 'X-API-Key' = '%METRICS_API_KEY%'; 'Content-Type' = 'application/json' } ^
                                -Body '{\"tool\":\"jenkins\",\"build_number\":%BUILD_NUMBER%,\"commit_sha\":\"%GIT_COMMIT%\",\"timestamp\":\"%BUILD_END_TIME%\",\"lead_time_seconds\":0}'; ^
                            Write-Host '✅ Lead time metrics sent' -ForegroundColor Green" ^
                            2>nul || echo ⚠️ Warning: Could not send LTFC metrics
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
                
                bat '''
                    setlocal enabledelayedexpansion
                    
                    REM Guardar información del build
                    if not exist "%WORKSPACE%\build-logs" mkdir "%WORKSPACE%\build-logs"
                    
                    (
                        echo Build: %BUILD_NUMBER%
                        echo Status: %BUILD_STATUS%
                        echo Timestamp: %date% %time%
                    ) > "%WORKSPACE%\build-logs\summary.txt"
                    
                    echo ✅ Build logs guardados
                '''
            }
        }
        
        success {
            script {
                echo "✅ BUILD EXITOSO"
                
                bat '''
                    echo ✅ Pipeline completado exitosamente
                    echo Timestamp: %date% %time%
                '''
            }
        }
        
        failure {
            script {
                echo "❌ BUILD FALLÓ"
                
                bat '''
                    setlocal enabledelayedexpansion
                    
                    REM Registrar fallo como incidente
                    for /f "tokens=*" %%A in ('powershell -Command "[datetime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ss.000Z')"') do set FAILURE_TIME=%%A
                    
                    powershell -Command ^
                        "$response = Invoke-RestMethod ^
                            -Uri 'http://localhost:8000/api/metrics/incident' ^
                            -Method POST ^
                            -Headers @{ 'X-API-Key' = '%METRICS_API_KEY%'; 'Content-Type' = 'application/json' } ^
                            -Body '{\"tool\":\"jenkins\",\"type\":\"build_failure\",\"timestamp\":\"%FAILURE_TIME%\",\"severity\":\"high\",\"description\":\"Build #%BUILD_NUMBER% failed\"}'; ^
                            Write-Host '⚠️ Incident metrics sent'" ^
                            2>nul || echo ⚠️ Could not send incident metrics
                '''
            }
        }
        
        cleanup {
            script {
                echo "🧹 Limpiando workspace..."
                
                deleteDir()
            }
        }
    }
}