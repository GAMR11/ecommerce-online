pipeline {
    agent any

    options {
        timestamps()
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    stages {
        stage('Checkout') {
            steps {
                script {
                    echo "📁 Repositorio clonado"
                    echo "Branch: ${GIT_BRANCH ?: 'N/A'}"
                }
            }
        }

        stage('Clean Previous') {
            steps {
                script {
                    echo "🧹 Limpiando ejecuciones previas..."
                    bat '''
                        cd %WORKSPACE%
                        REM Detener y eliminar contenedores
                        docker compose down -v 2>nul
                        REM Eliminar contenedores huérfanos que puedan existir
                        for /f "tokens=*" %%i in ('docker ps -aq -f "label=com.docker.compose.project=ecommerce-ci"') do docker rm -f %%i 2>nul
                        for /f "tokens=*" %%i in ('docker ps -aq -f "label=com.docker.compose.project=ecommerce-ci2"') do docker rm -f %%i 2>nul
                        REM Eliminar contenedores específicos por nombre
                        docker rm -f ecommerce-app 2>nul
                        docker rm -f ecommerce-nginx 2>nul
                        docker rm -f ecommerce-mysql 2>nul
                    '''
                }
            }
        }

        stage('Build Containers') {
            steps {
                script {
                    echo "🔨 Construyendo contenedores..."
                    bat '''
                        cd %WORKSPACE%
                        docker compose up -d --build
                    '''
                }
            }
        }

        stage('Wait for Services') {
            steps {
                script {
                    echo "⏳ Esperando MySQL..."
                    bat '''
                        cd %WORKSPACE%
                        setlocal enabledelayedexpansion
                        set MAX_ATTEMPTS=30
                        set ATTEMPT=0
                        
                        :wait_loop
                        set /a ATTEMPT+=1
                        if !ATTEMPT! gtr !MAX_ATTEMPTS! (
                            echo ❌ MySQL no respondió
                            docker compose logs mysql
                            exit /b 1
                        )
                        
                        docker compose exec -T mysql mysqladmin ping -h localhost -u root -proot > nul 2>&1
                        if errorlevel 1 (
                            echo Intento !ATTEMPT!/!MAX_ATTEMPTS! - Esperando MySQL...
                            ping localhost -n 3 > nul
                            goto wait_loop
                        )
                        
                        echo ✅ MySQL está listo
                    '''
                }
            }
        }

        stage('Run Migrations') {
            steps {
                script {
                    echo "🗄️  Ejecutando migraciones..."
                    bat '''
                        cd %WORKSPACE%
                        docker compose exec -T app php artisan migrate:fresh --force --seed
                    '''
                }
            }
        }

        stage('Cache Clear') {
            steps {
                script {
                    echo "🧹 Limpiando caché..."
                    bat '''
                        cd %WORKSPACE%
                        docker compose exec -T app php artisan cache:clear
                        docker compose exec -T app php artisan config:clear
                    '''
                }
            }
        }

        stage('Verify') {
            steps {
                script {
                    echo "✅ Verificando..."
                    bat '''
                        cd %WORKSPACE%
                        docker compose ps
                    '''
                }
            }
        }
    }

    post {
        always {
            bat '''
                cd %WORKSPACE%
                docker compose logs --tail=30
            '''
        }

        success {
            echo "✅ BUILD EXITOSO"
            echo "App: http://localhost:8000"
        }

        failure {
            echo "❌ BUILD FALLÓ"
            bat '''
                cd %WORKSPACE%
                docker compose logs
            '''
        }
    }
}
