pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Containers') {
            steps {
                sh 'docker compose down'
                sh 'docker compose down -v || true'
                sh 'docker compose up -d --build'
            }
        }

        stage('Run Migrations') {
            steps {
                sh 'docker compose exec -T app php artisan migrate --force'
            }
        }

        stage('Cache Clear') {
            steps {
                sh 'docker compose exec -T app php artisan config:clear'
                sh 'docker compose exec -T app php artisan cache:clear'
            }
        }
    }
}
