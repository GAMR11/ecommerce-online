// ============================================
// JENKINSFILE PARA WINDOWS - VERSIÓN SIMPLIFICADA
// ============================================
// ⚠️ IMPORTANTE: 
// - Usa 'bat' en lugar de 'sh' 
// - Sin rutas complejas con backslashes
// - Enfocado en capturar métricas DORA

pipeline {
    agent any
    
    environment {
        // Configuración de API
        METRICS_API_URL = 'http://localhost:8000/api/metrics'
        METRICS_TOOL = 'jenkins'
    }
    
    triggers {
        githubPush()
        pollSCM('H/5 * * * *')
    }
    
    options {
        timestamps()
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }
    
    stages {
        // ============================================
        // STAGE 1: CHECKOUT
        // ============================================
        stage('Checkout') {
            steps {
                script {
                    echo "🔍 Checkout: Descargando código..."
                    checkout scm
                    echo "📌 Commit: ${GIT_COMMIT}"
                    echo "📌 Branch: ${GIT_BRANCH}"
                }
            }
        }
        
        // ============================================
        // STAGE 2: VERIFICAR ENTORNO
        // ============================================
        stage('Environment Check') {
            steps {
                script {
                    echo "🐳 Verificando Docker..."
                    bat 'docker compose --version'
                    bat 'docker --version'
                }
            }
        }
        
        // ============================================
        // STAGE 3: LIMPIAR Y PREPARAR
        // ============================================
        stage('Clean Previous') {
            steps {
                script {
                    echo "🧹 Limpiando contenedores previos..."
                    bat '''
                        docker compose down -v 2>nul || echo Nada que limpiar
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 4: BUILD Y START CONTAINERS
        // ============================================
        stage('Build Containers') {
            steps {
                script {
                    echo "🏗️ Construyendo contenedores..."
                    bat 'docker compose up -d --build'
                }
            }
        }
        
        // ============================================
        // STAGE 5: ESPERAR A SERVICIOS
        // ============================================
        stage('Wait for Services') {
            steps {
                script {
                    echo "⏳ Esperando a que servicios estén listos..."
                    bat '''
                        setlocal enabledelayedexpansion
                        set MAX_ATTEMPTS=30
                        set ATTEMPT=0
                        
                        :wait_loop
                        set /a ATTEMPT+=1
                        if !ATTEMPT! GTR !MAX_ATTEMPTS! (
                            echo ❌ MySQL no respondio
                            exit /b 1
                        )
                        
                        docker compose exec -T mysql mysqladmin ping -h mysql -u root -proot >nul 2>&1
                        if errorlevel 1 (
                            echo Intento !ATTEMPT!/!MAX_ATTEMPTS!...
                            ping mysql -n 2 >nul
                            goto wait_loop
                        )
                        
                        echo ✅ MySQL listo
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 6: MIGRATIONS
        // ============================================
        stage('Run Migrations') {
            steps {
                script {
                    echo "📊 Ejecutando migraciones..."
                    bat 'docker compose exec -T app php artisan migrate:fresh --force --seed'
                }
            }
        }
        
        // ============================================
        // STAGE 7: TESTS
        // ============================================
        stage('Run Tests') {
            steps {
                script {
                    echo "🧪 Ejecutando tests..."
                    bat '''
                        docker compose exec -T app php artisan test 2>&1 || (
                            echo ❌ Tests fallaron
                            exit /b 1
                        )
                        echo ✅ Tests exitosos
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 8: CACHE CLEAR
        // ============================================
        stage('Cache Clear') {
            steps {
                script {
                    echo "🧹 Limpiando cache..."
                    bat '''
                        docker compose exec -T app php artisan cache:clear
                        docker compose exec -T app php artisan config:clear
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 9: VERIFICACIÓN FINAL
        // ============================================
        stage('Verify Deployment') {
            steps {
                script {
                    echo "✅ Verificando deployment..."
                    bat '''
                        echo Estado de contenedores:
                        docker compose ps
                        
                        echo.
                        echo Health check:
                        docker compose exec -T app php artisan tinker --execute="echo 'Laravel OK'"
                    '''
                }
            }
        }
        
        // ============================================
        // STAGE 10: GUARDAR MÉTRICAS
        // ============================================
        stage('Save Build Metrics') {
            steps {
                script {
                    echo "📊 Guardando información del build..."
                    bat '''
                        setlocal enabledelayedexpansion
                        
                        REM Crear timestamp actual
                        for /f "tokens=*" %%A in ('powershell -Command "[datetime]::UtcNow.ToString('yyyy-MM-dd HH:mm:ss')"') do set BUILD_TIMESTAMP=%%A
                        
                        REM Crear archivo de resumen (sin rutas complejas)
                        (
                            echo Build Number: %BUILD_NUMBER%
                            echo Commit: %GIT_COMMIT%
                            echo Branch: %GIT_BRANCH%
                            echo Timestamp: !BUILD_TIMESTAMP!
                            echo Status: SUCCESS
                        ) > build_summary.txt
                        
                        type build_summary.txt
                        echo Build summary guardado
                    '''
                }
            }
        }
    }
    
    post {
        always {
            script {
                echo "🧹 Limpieza post-build..."
                bat '''
                    echo Build completado
                    echo Timestamp: %date% %time%
                '''
            }
        }
        
        success {
            script {
                echo "✅ BUILD EXITOSO"
                bat 'echo ✅ Pipeline completado exitosamente'
            }
        }
        
        failure {
            script {
                echo "❌ BUILD FALLÓ"
                bat 'echo ❌ Pipeline finalizo con error'
            }
        }
        
        cleanup {
            script {
                echo "🧹 Limpieza final..."
                deleteDir()
            }
        }
    }
}