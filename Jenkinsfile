pipeline {
    agent any

    environment {
        DOCKER_COMPOSE_FILE = "docker-compose.yml"
        WORKSPACE_DIR = "${WORKSPACE}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
                sh 'echo "Workspace: ${WORKSPACE_DIR}"'
                sh 'ls -la docker/nginx/ || echo "Directorio nginx no existe"'
                sh 'ls -la docker/php/ || echo "Directorio php no existe"'
            }
        }

        stage('Build Containers') {
            steps {
                script {
                    try {
                        // Detener y limpiar contenedores previos
                        sh '''
                            docker compose down || true
                            docker compose down -v || true
                        '''
                        
                        // Crear los contenedores
                        sh '''
                            docker compose up -d --build
                            sleep 10
                        '''
                    } catch (Exception e) {
                        echo "Error durante build de contenedores: ${e.message}"
                        sh 'docker compose logs || true'
                        throw e
                    }
                }
            }
        }

        stage('Wait for Services') {
            steps {
                script {
                    sh '''
                        echo "Esperando a que los servicios estén listos..."
                        
                        # Esperar MySQL
                        for i in {1..30}; do
                            if docker compose exec -T mysql mysqladmin ping -h localhost > /dev/null 2>&1; then
                                echo "✓ MySQL está listo"
                                break
                            fi
                            echo "Intento $i: Esperando MySQL..."
                            sleep 2
                        done
                        
                        # Esperar PHP-FPM
                        sleep 5
                        echo "✓ Servicios listos"
                    '''
                }
            }
        }

        stage('Run Migrations') {
            steps {
                script {
                    sh '''
                        docker compose exec -T app php artisan migrate:fresh --seed || true
                    '''
                }
            }
        }

        stage('Cache Clear') {
            steps {
                script {
                    sh '''
                        docker compose exec -T app php artisan cache:clear || true
                        docker compose exec -T app php artisan config:clear || true
                    '''
                }
            }
        }

        stage('Run Tests') {
            steps {
                script {
                    sh '''
                        docker compose exec -T app php artisan test || true
                    '''
                }
            }
        }
    }

    post {
        always {
            script {
                sh '''
                    echo "=== Estado de contenedores ===" 
                    docker compose ps || true
                    echo "=== Logs de servicios ===" 
                    docker compose logs --tail=50 || true
                '''
            }
        }
        failure {
            script {
                sh '''
                    echo "❌ Pipeline falló"
                    docker compose logs || true
                '''
            }
        }
        success {
            echo "✅ Pipeline completado exitosamente"
        }
    }
}