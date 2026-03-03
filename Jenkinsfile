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
        // ============================================
        stage('Checkout & Git Info') {
            steps {
                script {
                    echo '🔍 Capturando información de Git...'
                    checkout scm

                    // ========== DATOS DE GIT ==========
                    // Commit SHA
                    env.GIT_COMMIT_SHA = bat(
                        script: '@echo off & git rev-parse HEAD',
                        returnStdout: true
                    ).trim()

                    // Commit SHA Corto
                    env.GIT_COMMIT_SHORT = bat(
                        script: '@echo off & git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()

                    // Timestamp del commit (cuando se hizo el commit)
                    def commitTimestampEpoch = bat(
                        script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}",
                        returnStdout: true
                    ).trim()
                    env.COMMIT_TIME_EPOCH = commitTimestampEpoch
                    env.COMMIT_TIME_ISO = new Date(
                        commitTimestampEpoch.toLong() * 1000
                    ).format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                    // Autor del commit
                    env.GIT_AUTHOR = bat(
                        script: '@echo off & git log -1 --format=%%an',
                        returnStdout: true
                    ).trim()

                    // Email del autor
                    env.GIT_AUTHOR_EMAIL = bat(
                        script: '@echo off & git log -1 --format=%%ae',
                        returnStdout: true
                    ).trim()

                    // Mensaje del commit
                    env.GIT_MESSAGE = bat(
                        script: '@echo off & git log -1 --format=%%s',
                        returnStdout: true
                    ).trim()

                    // Branch actual
                    env.GIT_BRANCH = bat(
                        script: '@echo off & git rev-parse --abbrev-ref HEAD',
                        returnStdout: true
                    ).trim()

                    // Timestamp de ahora (cuando inicia el pipeline)
                    env.PIPELINE_START_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()
                    env.PIPELINE_START_ISO = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                    // ========== MOSTRAR INFO ==========
                    echo "📝 Commit SHA: ${env.GIT_COMMIT_SHA}"
                    echo "📝 Commit Short: ${env.GIT_COMMIT_SHORT}"
                    echo "⏱️  Commit Time: ${env.COMMIT_TIME_ISO}"
                    echo "👤 Author: ${env.GIT_AUTHOR} <${env.GIT_AUTHOR_EMAIL}>"
                    echo "💬 Message: ${env.GIT_MESSAGE}"
                    echo "🌿 Branch: ${env.GIT_BRANCH}"
                }
            }
        }

        // ============================================
        // STAGE 2: EXTRAER Y REGISTRAR ISSUES JIRA
        // ============================================
        stage('Extract & Register Jira Issues') {
            steps {
                script {
                    echo '🔗 Buscando issues de Jira asociados...'

                    // Buscar issues en el commit message y branch
                    // Patrón: KAN-1, PROJ-123, etc
                    def jiraPattern = '([A-Z]+-\\d+)'

                    def issuesFromMessage = (env.GIT_MESSAGE =~ jiraPattern).collect { it[1] }
                    def issuesFromBranch = (env.GIT_BRANCH =~ jiraPattern).collect { it[1] }

                    def allIssues = (issuesFromMessage + issuesFromBranch).unique()

                    if (allIssues.isEmpty()) {
                        echo '⚠️  No se encontraron issues de Jira en el mensaje o rama'
                        echo "   Commit message: ${env.GIT_MESSAGE}"
                        echo "   Branch: ${env.GIT_BRANCH}"
                    } else {
                        echo "✅ Issues encontrados: ${allIssues.join(', ')}"

                        // Procesar cada issue encontrado
                        allIssues.each { issueKey ->
                            echo ''
                            echo "📋 Registrando issue: ${issueKey}"

                            // Intentar obtener del Jira API primero
                            def fetchData = JsonOutput.toJson([
                                tool: env.TOOL_NAME,
                                issue_key: issueKey,
                                timestamp: env.PIPELINE_START_ISO
                            ])

                            def fetchResponse = bat(
                                script: "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue/fetch " +
                                    '-H "Content-Type: application/json" ' +
                                    "-d \"${fetchData.replace('"', '\\"')}\" || echo '{\"error\": \"fetch_failed\"}'",
                                returnStdout: true
                            ).trim()

                            // Si la API falla, registrar un issue manual
                            if (fetchResponse.contains('\"error\"')) {
                                echo '⚠️  No se pudo obtener de Jira API, registrando manualmente...'

                                def manualIssue = JsonOutput.toJson([
                                    tool: 'jenkins',
                                    issue_key: issueKey,
                                    issue_type: 'Story',
                                    summary: "Issue linked in commit ${env.GIT_COMMIT_SHORT}",
                                    description: 'Automatically detected from commit message or branch name',
                                    status: 'In Progress',
                                    assignee: env.GIT_AUTHOR,
                                    reporter: env.GIT_AUTHOR,
                                    created_at: env.PIPELINE_START_ISO,
                                    completed_at: null,
                                    sprint_id: null,
                                    story_points: null,
                                    timestamp: env.PIPELINE_START_ISO
                                ])

                                bat "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue " +
                                    '-H "Content-Type: application/json" ' +
                                    "-d \"${manualIssue.replace('"', '\\"')}\" > /dev/null 2>&1"

                                echo "✅ Issue ${issueKey} registrado"
                            } else {
                                echo "✅ Issue ${issueKey} obtenido de Jira API"
                            }
                        }
                    }
                }
            }
        }

        // ============================================
        // STAGE 3: BUILD & DEPLOY
        // ============================================
        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    echo '🏗️  Levantando entorno Docker...'
                    bat 'docker compose up -d --build'

                    echo '📊 Migraciones y Limpieza...'
                    bat 'docker compose exec -T app php artisan migrate --force --seed'
                    bat 'docker compose exec -T app php artisan cache:clear'
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
                    echo '📤 Registrando datos del commit en GitHub...'

                    def githubData = JsonOutput.toJson([
                        tool: 'jenkins',
                        commit_sha: env.GIT_COMMIT_SHA,
                        branch: env.GIT_BRANCH,
                        author: env.GIT_AUTHOR,
                        message: env.GIT_MESSAGE,
                        timestamp: env.COMMIT_TIME_ISO
                    ])

                    echo '📤 Enviando GitHub commit data...'
                    bat "curl -X POST ${env.APP_URL}/api/metrics/github-commit " +
                        '-H "Content-Type: application/json" ' +
                        "-d \"${githubData.replace('"', '\\"')}\""
                }
            }
        }

        // ============================================
        // STAGE 6: CAPTURAR MÉTRICAS DORA COMPLETAS
        // ============================================
        stage('Track DORA Metrics') {
            steps {
                script {
                    echo '📊 Registrando Métricas DORA Completas...'

                    def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                    def nowEpoch = (System.currentTimeMillis() / 1000).toLong()

                    // ========================================
                    // 1️⃣ DEPLOYMENT FREQUENCY
                    // ========================================
                    // Evento: Se ejecutó un deployment exitoso
                    def deploymentData = JsonOutput.toJson([
                        // Datos de Jenkins
                        tool: env.TOOL_NAME,
                        build_number: env.BUILD_NUMBER,
                        build_duration_seconds: env.BUILD_DURATION ?: 0,

                        // Datos de Git
                        commit_sha: env.GIT_COMMIT_SHA,
                        commit_author: env.GIT_AUTHOR,
                        commit_message: env.GIT_MESSAGE,
                        commit_timestamp: env.COMMIT_TIME_ISO,
                        branch: env.GIT_BRANCH,

                        // Estados
                        status: 'success',
                        is_failure: false,
                        environment: 'prod',

                        // Timestamps
                        deployed_at: nowIso,
                        timestamp: nowIso
                    ])

                    echo '📤 Enviando deployment metric...'
                    bat "curl -X POST ${env.APP_URL}/api/metrics/deployment " +
                        '-H "Content-Type: application/json" ' +
                        "-d \"${deploymentData.replace('"', '\\"')}\""

                    // ========================================
                    // 2️⃣ LEAD TIME FOR CHANGES
                    // ========================================
                    // Lead Time = Tiempo entre commit y deployment
                    long leadTimeSeconds = nowEpoch - env.COMMIT_TIME_EPOCH.toLong()

                    def leadTimeData = JsonOutput.toJson([
                        tool: env.TOOL_NAME,
                        commit_sha: env.GIT_COMMIT_SHA,
                        commit_timestamp: env.COMMIT_TIME_ISO,
                        deployment_timestamp: nowIso,
                        lead_time_seconds: leadTimeSeconds,
                        lead_time_minutes: (leadTimeSeconds / 60).toInteger(),
                        timestamp: nowIso
                    ])

                    echo '📤 Enviando lead time metric...'
                    bat "curl -X POST ${env.APP_URL}/api/metrics/leadtime " +
                        '-H "Content-Type: application/json" ' +
                        "-d \"${leadTimeData.replace('"', '\\"')}\""

                    // ========================================
                    // 3️⃣ CHANGE FAILURE RATE
                    // ========================================
                    // Enviamos resultado: SUCCESS o FAILURE
                    def resultData = JsonOutput.toJson([
                        tool: env.TOOL_NAME,
                        build_number: env.BUILD_NUMBER,
                        commit_sha: env.GIT_COMMIT_SHA,
                        status: 'success',
                        is_failure: false,
                        timestamp: nowIso
                    ])

                    echo '📤 Enviando deployment result metric...'
                    bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result " +
                        '-H "Content-Type: application/json" ' +
                        "-d \"${resultData.replace('"', '\\"')}\""

                    // ========================================
                    // 4️⃣ MTTR (Auto-Resolve)
                    // ========================================
                    // Si el build fue exitoso, resolvemos incident previos
                    def resolveData = JsonOutput.toJson([
                        tool: env.TOOL_NAME,
                        resolved_by: 'jenkins-automation',
                        resolution_time: nowIso,
                        timestamp: nowIso
                    ])

                    echo '📤 Resolviendo incidentes previos...'
                    bat "curl -X POST ${env.APP_URL}/api/metrics/incident/resolve " +
                        '-H "Content-Type: application/json" ' +
                        "-d \"${resolveData.replace('"', '\\"')}\""
                }
            }
        }
    }

    // ============================================
    // POST: Si algo falla
    // ============================================
    post {
        failure {
            script {
                echo '❌ Pipeline FALLÓ - Registrando Incidente...'
                def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                // 1. Registrar como deployment fallido (para Change Failure Rate)
                def failData = JsonOutput.toJson([
                    tool: env.TOOL_NAME,
                    build_number: env.BUILD_NUMBER,
                    commit_sha: env.GIT_COMMIT_SHA ?: 'unknown',
                    status: 'failure',
                    is_failure: true,
                    timestamp: nowIso
                ])

                bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result " +
                    '-H "Content-Type: application/json" ' +
                    "-d \"${failData.replace('"', '\\"')}\""

                // 2. Crear incidente abierto (para MTTR)
                def incidentData = JsonOutput.toJson([
                    tool: env.TOOL_NAME,
                    build_number: env.BUILD_NUMBER,
                    commit_sha: env.GIT_COMMIT_SHA ?: 'unknown',
                    status: 'open',
                    severity: 'high',
                    start_time: nowIso,
                    description: "Build #${env.BUILD_NUMBER} failed",
                    timestamp: nowIso
                ])

                bat "curl -X POST ${env.APP_URL}/api/metrics/incident " +
                    '-H "Content-Type: application/json" ' +
                    "-d \"${incidentData.replace('"', '\\"')}\""
            }
        }

        success {
            echo '✅ Pipeline exitoso - Métricas registradas'
        }
    }
}
