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
                    echo "📁 Clonando repositorio..."
                    checkout scm
                    sh 'echo "Workspace: ${WORKSPACE}"'
                }
            }
        }

        stage('Build Containers') {
            steps {
                script {
                    echo "🔨 Construyendo contenedores..."
                    sh '''
                        docker compose down || true
                        docker compose down -v || true
                        docker compose up -d --build
                        sleep 15
                    '''
                }
            }
        }

        stage('Wait for Services') {
            steps {
                script {
                    echo "⏳ Esperando que MySQL esté listo..."
                    sh '''
                        MAX_ATTEMPTS=30
                        ATTEMPT=1
                        
                        while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do
                            if docker compose exec -T mysql mysqladmin ping -h localhost -u root -proot > /dev/null 2>&1; then
                                echo "✅ MySQL está listo"
                                break
                            fi
                            
                            if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
                                echo "❌ MySQL no respondió"
                                docker compose logs mysql
                                exit 1
                            fi
                            
                            sleep 2
                            ATTEMPT=$((ATTEMPT + 1))
                        done
                    '''
                }
            }
        }

        stage('Run Migrations') {
            steps {
                script {
                    echo "🗄️ Ejecutando migraciones..."
                    sh 'docker compose exec -T app php artisan migrate:fresh --force --seed || true'
                }
            }
        }

        stage('Cache Clear') {
            steps {
                script {
                    echo "🧹 Limpiando caché..."
                    sh '''
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
                    sh 'docker compose ps'
                }
            }
        }
    }

    post {
        always {
            script {
                sh 'docker compose logs --tail=30 || true'
            }
        }

        success {
            echo "✅ BUILD EXITOSO"
            echo "App en: http://localhost:8000"
        }

        failure {
            echo "❌ BUILD FALLÓ"
            script {
                sh 'docker compose logs || true'
            }
        }
    }
}
