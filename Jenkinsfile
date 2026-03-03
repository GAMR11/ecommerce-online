import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        APP_URL         = 'http://localhost:8000'
        METRICS_API_KEY = 'tu_clave_aqui'
        TOOL_NAME       = 'jenkins'
        GITHUB_TOKEN    = credentials('token-api-jenkins')
    }

    stages {
        // ============================================
        // STAGE 1: CHECKOUT & CAPTURAR INFO GIT
        // (Necesario para tener las variables antes que nada)
        // ============================================
        stage('Checkout & Git Info') {
            steps {
                script {
                    echo '🔍 Capturando información de Git...'
                    checkout scm

                    env.GIT_COMMIT_SHA = bat(script: '@echo off & git rev-parse HEAD', returnStdout: true).trim()
                    env.GIT_COMMIT_SHORT = bat(script: '@echo off & git rev-parse --short HEAD', returnStdout: true).trim()

                    def commitTimestampEpoch = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()
                    env.COMMIT_TIME_EPOCH = commitTimestampEpoch
                    env.COMMIT_TIME_ISO = new Date(commitTimestampEpoch.toLong() * 1000).format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                    env.GIT_AUTHOR = bat(script: '@echo off & git log -1 --format=%%an', returnStdout: true).trim()
                    env.GIT_AUTHOR_EMAIL = bat(script: '@echo off & git log -1 --format=%%ae', returnStdout: true).trim()
                    env.GIT_MESSAGE = bat(script: '@echo off & git log -1 --format=%%s', returnStdout: true).trim()
                    env.GIT_BRANCH = bat(script: '@echo off & git rev-parse --abbrev-ref HEAD', returnStdout: true).trim()
                    env.PIPELINE_START_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()
                    env.PIPELINE_START_ISO = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                }
            }
        }

        // ============================================
        // STAGE 2: BUILD & DEPLOY (CORREGIDO: AHORA VA SEGUNDO)
        // Levantamos la app PRIMERO para que los CURLs funcionen
        // ============================================
        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    echo '🏗️ Levantando entorno Docker...'
                    bat 'docker compose up -d --build'

                    echo '⏳ Esperando a que el servicio esté listo en localhost:8000...'
                    // Espera de 10 segundos para asegurar que el servidor web responda
                    bat 'timeout /t 10 /nobreak'

                    echo '📊 Migraciones y Limpieza...'
                    bat 'docker compose exec -T app php artisan migrate --force --seed'
                    bat 'docker compose exec -T app php artisan cache:clear'
                }
            }
        }

        // ============================================
        // STAGE 3: EXTRAER Y REGISTRAR ISSUES JIRA
        // (Ya podemos hacer POST porque la app está arriba)
        // ============================================
        stage('Extract & Register Jira Issues') {
            steps {
                script {
                    echo '🔗 Buscando issues de Jira asociados...'
                    def jiraPattern = '([A-Z]+-\\d+)'
                    def issuesFromMessage = (env.GIT_MESSAGE =~ jiraPattern).collect { it[1] }
                    def issuesFromBranch = (env.GIT_BRANCH =~ jiraPattern).collect { it[1] }
                    def allIssues = (issuesFromMessage + issuesFromBranch).unique()

                    if (allIssues.isEmpty()) {
                        echo '⚠️ No se encontraron issues'
                    } else {
                        allIssues.each { issueKey ->
                            def fetchData = JsonOutput.toJson([tool: env.TOOL_NAME, issue_key: issueKey, timestamp: env.PIPELINE_START_ISO])
                            def fetchResponse = bat(
                                script: "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue/fetch -H \"Content-Type: application/json\" -d \"${fetchData.replace('"', '\\"')}\" || echo '{\"error\": \"fetch_failed\"}'",
                                returnStdout: true
                            ).trim()

                            if (fetchResponse.contains('\"error\"')) {
                                def manualIssue = JsonOutput.toJson([
                                    tool: 'jenkins', issue_key: issueKey, issue_type: 'Story',
                                    summary: "Issue linked in commit ${env.GIT_COMMIT_SHORT}",
                                    assignee: env.GIT_AUTHOR, reporter: env.GIT_AUTHOR,
                                    timestamp: env.PIPELINE_START_ISO
                                ])
                                bat "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue -H \"Content-Type: application/json\" -d \"${manualIssue.replace('"', '\\"')}\" > NUL 2>&1"
                            }
                        }
                    }
                }
            }
        }

        // ============================================
        // STAGE 4: TESTS
        // ============================================
        stage('Run Tests') {
            steps {
                script {
                    echo '🧪 Ejecutando tests...'
                    bat 'docker compose exec -T app php artisan test'
                }
            }
        }

        // ============================================
        // STAGE 5: CAPTURAR DATOS DE GITHUB
        // ============================================
        stage('Capture GitHub Data') {
            steps {
                script {
                    def githubData = JsonOutput.toJson([
                        tool: 'jenkins', commit_sha: env.GIT_COMMIT_SHA,
                        branch: env.GIT_BRANCH, author: env.GIT_AUTHOR,
                        message: env.GIT_MESSAGE, timestamp: env.COMMIT_TIME_ISO
                    ])
                    bat "curl -X POST ${env.APP_URL}/api/metrics/github-commit -H \"Content-Type: application/json\" -d \"${githubData.replace('"', '\\"')}\""
                }
            }
        }

        // ============================================
        // STAGE 6: CAPTURAR MÉTRICAS DORA
        // ============================================
        stage('Track DORA Metrics') {
            steps {
                script {
                    def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                    def nowEpoch = (System.currentTimeMillis() / 1000).toLong()

                    // Deployment Frequency
                    def deploymentData = JsonOutput.toJson([
                        tool: env.TOOL_NAME, build_number: env.BUILD_NUMBER,
                        commit_sha: env.GIT_COMMIT_SHA, status: 'success',
                        deployed_at: nowIso, timestamp: nowIso
                    ])
                    bat "curl -X POST ${env.APP_URL}/api/metrics/deployment -H \"Content-Type: application/json\" -d \"${deploymentData.replace('"', '\\"')}\""

                    // Lead Time
                    long leadTimeSeconds = nowEpoch - env.COMMIT_TIME_EPOCH.toLong()
                    def leadTimeData = JsonOutput.toJson([
                        tool: env.TOOL_NAME, commit_sha: env.GIT_COMMIT_SHA,
                        lead_time_seconds: leadTimeSeconds, timestamp: nowIso
                    ])
                    bat "curl -X POST ${env.APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -d \"${leadTimeData.replace('"', '\\"')}\""
                }
            }
        }
    }

    post {
        failure {
            script {
                echo '❌ Pipeline FALLÓ - Registrando Incidente...'
                // Aquí también funcionará porque docker ya se intentó levantar
                def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                def failData = JsonOutput.toJson([tool: env.TOOL_NAME, status: 'failure', is_failure: true, timestamp: nowIso])
                bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -d \"${failData.replace('"', '\\"')}\""
            }
        }
        success {
            echo '✅ Pipeline exitoso'
        }
    }
}
