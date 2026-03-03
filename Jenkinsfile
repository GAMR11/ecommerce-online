import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        APP_URL         = 'http://localhost:8000'
        METRICS_API_KEY = 'tu_clave_aqui'
        TOOL_NAME       = 'jenkins'
        GITHUB_TOKEN = credentials('token-api-jenkins')
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
                    echo "⏱️ Commit Time: ${env.COMMIT_TIME_ISO}"
                    echo "👤 Author: ${env.GIT_AUTHOR}"
                    echo "💬 Message: ${env.GIT_MESSAGE}"
                    echo "🌿 Branch: ${env.GIT_BRANCH}"
                }
            }
        }

        // ============================================
        // STAGE 2: BUILD & DEPLOY
        // ============================================
        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    echo '🏗️ Levantando entorno Docker...'
                    bat 'docker compose up -d --build'

                    echo '📊 Migraciones y Limpieza...'
                    bat 'docker compose exec -T app php artisan migrate --force --seed'
                    bat 'docker compose exec -T app php artisan cache:clear'
                }
            }
        }

        // ============================================
        // STAGE 3: TESTS
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
        // STAGE 4: CAPTURAR MÉTRICAS DORA COMPLETAS
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
                        '-H \"Content-Type: application/json\" ' +
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
                        '-H \"Content-Type: application/json\" ' +
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
                        '-H \"Content-Type: application/json\" ' +
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
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${resolveData.replace('"', '\\"')}\""
                }
            }
        }

        // ============================================
        // STAGE 4: JIRA ISSUE DISCOVERY (AJUSTADO)
        // ============================================
        stage('Jira Issue Discovery') {
            steps {
                script {
                    echo '🔗 Analizando tickets de Jira...'
                    
                    def jiraPattern = /([A-Z]+-\d+)/
                    def issues = []
                    
                    // Buscar en mensaje de commit y nombre de rama
                    def msgMatches = (env.GIT_MESSAGE =~ jiraPattern)
                    while (msgMatches.find()) { issues << msgMatches.group(1) }
                    
                    def branchMatches = (env.GIT_BRANCH =~ jiraPattern)
                    while (branchMatches.find()) { issues << branchMatches.group(1) }
                    
                    issues = issues.unique()

                    if (issues.isEmpty()) {
                        echo 'ℹ️ No se detectaron llaves de Jira.'
                    } else {
                        issues.each { issueKey ->
                            echo "🚀 Registrando métrica para ticket: ${issueKey}"
                            
                            // Creamos un objeto que el controlador guardará en la columna 'data'
                            def jiraMetric = [
                                type: 'jira-issue',
                                tool: env.TOOL_NAME,
                                timestamp: env.PIPELINE_START_ISO,
                                data: [
                                    issue_key: issueKey,
                                    summary: env.GIT_MESSAGE,
                                    assignee: env.GIT_AUTHOR,
                                    branch: env.GIT_BRANCH,
                                    commit_sha: env.GIT_COMMIT_SHA,
                                    status: 'In Progress'
                                ]
                            ]
                            
                            def payload = JsonOutput.toJson(jiraMetric)
                            // Enviamos al endpoint genérico de métricas
                            bat "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue -H \"Content-Type: application/json\" -d \"${payload.replace('"', '\\"')}\""
                        }
                    }
                }
            }
        }
        // ========================================
        // STAGE 5: CAPTURAR DATOS DE GITHUB (OPCIONAL)
        // ========================================
        stage('Capture GitHub Data') {
            when {
                expression {
                    // Solo ejecutar si tenemos token de GitHub
                    return env.GITHUB_TOKEN != null && env.GITHUB_TOKEN.length() > 0
                }
            }
            steps {
                script {
                    echo '🔍 Capturando datos de GitHub...'

                    // Los datos de GitHub los capturamos del commit que ya hicimos checkout
                    // Puedes expandir esto para obtener info de PR, reviews, etc.

                    def githubData = JsonOutput.toJson([
                        tool: 'github',
                        commit_sha: env.GIT_COMMIT_SHA,
                        branch: env.GIT_BRANCH,
                        author: env.GIT_AUTHOR,
                        message: env.GIT_MESSAGE,
                        timestamp: env.COMMIT_TIME_ISO
                    ])

                    echo '📤 Enviando GitHub commit data...'
                    bat "curl -X POST ${env.APP_URL}/api/metrics/github-commit " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${githubData.replace('"', '\\"')}\""
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
                    '-H \"Content-Type: application/json\" ' +
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
                    '-H \"Content-Type: application/json\" ' +
                    "-d \"${incidentData.replace('"', '\\"')}\""
            }
        }

        success {
            echo '✅ Pipeline exitoso - Métricas registradas'
        }
    }
}