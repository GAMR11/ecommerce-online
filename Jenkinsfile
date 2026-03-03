pipeline {
    agent any

    environment {
        APP_URL = "http://localhost:8000"
        GITHUB_TOKEN = credentials('github-token')
    }

    stages {

        // ========================================
        // 1️⃣ CHECKOUT & GIT INFO
        // ========================================
        stage('Checkout & Git Info') {
            steps {
                checkout scm
                script {
                    env.GIT_COMMIT_SHA = bat(script: 'git rev-parse HEAD', returnStdout: true).trim()
                    env.GIT_BRANCH_NAME = env.BRANCH_NAME ?: "unknown"
                    env.GIT_COMMIT_AUTHOR = bat(script: 'git log -1 --pretty=format:"%an"', returnStdout: true).trim()
                    env.GIT_COMMIT_MESSAGE = bat(script: 'git log -1 --pretty=format:"%s"', returnStdout: true).trim()
                    env.GIT_COMMIT_TIMESTAMP = bat(script: 'git log -1 --pretty=format:"%ci"', returnStdout: true).trim()
                }
            }
        }

        // ========================================
        // 2️⃣ BUILD & DEPLOY (MOVIDO ARRIBA)
        // ========================================
        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    bat 'docker compose down'
                    bat 'docker compose up -d --build'

                    echo '⏳ Esperando que Laravel esté listo...'
                    sleep 15
                }
            }
        }

        // ========================================
        // 3️⃣ EXTRAER Y REGISTRAR ISSUES JIRA
        // ========================================
        stage('Extract & Register Jira Issues') {
            steps {
                script {
                    def issueMatcher = (env.GIT_BRANCH_NAME =~ /(KAN-\d+)/)
                    if (issueMatcher) {
                        env.JIRA_ISSUE_KEY = issueMatcher[0][0]

                        bat """
                        curl -s -X POST ${APP_URL}/api/metrics/jira-issue/fetch ^
                        -H "Content-Type: application/json" ^
                        -d "{\\"issue_key\\":\\"${env.JIRA_ISSUE_KEY}\\"}" > nul 2>&1
                        """
                    }
                }
            }
        }

        // ========================================
        // 4️⃣ RUN TESTS
        // ========================================
        stage('Run Tests') {
            steps {
                script {
                    def testStatus = bat(
                        script: 'docker compose exec -T app php artisan test',
                        returnStatus: true
                    )

                    env.TEST_STATUS = testStatus == 0 ? "SUCCESS" : "FAILURE"

                    if (testStatus != 0) {
                        error("Tests fallaron")
                    }
                }
            }
        }

        // ========================================
        // 5️⃣ CAPTURAR DATOS DE GITHUB
        // ========================================
        stage('Capture GitHub Data') {
            when {
                expression {
                    return env.GITHUB_TOKEN != null && env.GITHUB_TOKEN.length() > 0
                }
            }
            steps {
                script {
                    bat """
                    curl -s -X POST ${APP_URL}/api/metrics/github-commit ^
                    -H "Content-Type: application/json" ^
                    -d "{\\"tool\\":\\"github\\",\\"commit_sha\\":\\"${env.GIT_COMMIT_SHA}\\",\\"branch\\":\\"${env.GIT_BRANCH_NAME}\\",\\"author\\":\\"${env.GIT_COMMIT_AUTHOR}\\",\\"message\\":\\"${env.GIT_COMMIT_MESSAGE}\\",\\"timestamp\\":\\"${env.GIT_COMMIT_TIMESTAMP}\\"}" > nul 2>&1
                    """
                }
            }
        }

        // ========================================
        // 6️⃣ TRACK DORA METRICS
        // ========================================
        stage('Track DORA Metrics') {
            steps {
                script {
                    bat """
                    curl -s -X POST ${APP_URL}/api/metrics/deployment ^
                    -H "Content-Type: application/json" ^
                    -d "{\\"tool\\":\\"jenkins\\",\\"commit_sha\\":\\"${env.GIT_COMMIT_SHA}\\",\\"branch\\":\\"${env.GIT_BRANCH_NAME}\\",\\"status\\":\\"${env.TEST_STATUS}\\"}" > nul 2>&1
                    """
                }
            }
        }
    }

    // ========================================
    // POST
    // ========================================
    post {

        success {
            script {
                echo "Pipeline exitoso 🚀"
            }
        }

        failure {
            script {
                bat """
                curl -s -X POST ${APP_URL}/api/metrics/deployment ^
                -H "Content-Type: application/json" ^
                -d "{\\"tool\\":\\"jenkins\\",\\"commit_sha\\":\\"${env.GIT_COMMIT_SHA}\\",\\"branch\\":\\"${env.GIT_BRANCH_NAME}\\",\\"status\\":\\"FAILURE\\"}" > nul 2>&1
                """
            }
        }

        always {
            echo "Pipeline finalizado."
        }
    }
}