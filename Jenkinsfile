pipeline {
    agent any

    stages {
        stage('Set PHP Path') {
            steps {
                bat 'set PATH=C:\\laragon\\bin\\php\\php-8.1.x;%PATH%'
            }
        }

        stage('Clone Repository') {
            steps {
                git branch: 'master', url: 'https://github.com/GAMR10/Gusmor_inventario.git'
            }
        }

        // stage('Install Dependencies') {
        //     steps {
        //         bat 'composer install --no-dev --optimize-autoloader'
        //     }
        // }
         stage('Install Dependencies') {
            steps {
                bat 'composer install'
            }
        }

        stage('Dump Autoload') {
            steps {
                bat 'composer dump-autoload'
            }
        }

        stage('Run Tests') {
            steps {
                bat 'phpunit'
                //  bat 'composer test'
                // bat 'vendor\\bin\\phpunit'
                // bat 'php artisan test'
            }
        }
    }

    post {
        success {
            echo 'Build completed successfully!'
        }
        failure {
            echo 'Build failed. Check the logs for details.'
        }
    }
}
