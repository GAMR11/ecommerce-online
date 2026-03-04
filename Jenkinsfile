import groovy.json.JsonOutput

// ================================================================
// FUNCIONES @NonCPS
// Jenkins serializa el estado del pipeline entre pasos.
// Los objetos java.util.regex.Matcher NO son serializables,
// por lo que DEBEN vivir dentro de funciones @NonCPS.
// Estas funciones se ejecutan de forma no continuable (no pausan).
// ================================================================

@NonCPS
def extractJiraKeys(String text) {
    def found = []
    def matcher = text =~ /([A-Z]+-\d+)/
    for (int i = 0; i < matcher.count; i++) {
        found << matcher[i][1]
    }
    return found
}

@NonCPS
def buildJiraSuccessComment(String buildNumber, String branch, String commitMsg) {
    return groovy.json.JsonOutput.toJson([
        body: [
            type: 'doc',
            version: 1,
            content: [[
                type: 'paragraph',
                content: [[
                    type: 'text',
                    text: "✅ Deploy exitoso | Build #${buildNumber} | Branch: ${branch} | Commit: ${commitMsg}"
                ]]
            ]]
        ]
    ])
}

@NonCPS
def buildJiraFailureComment(String buildNumber, String branch) {
    return groovy.json.JsonOutput.toJson([
        body: [
            type: 'doc',
            version: 1,
            content: [[
                type: 'paragraph',
                content: [[
                    type: 'text',
                    text: "❌ Build FALLÓ | Build #${buildNumber} | Branch: ${branch} | Requiere atención inmediata."
                ]]
            ]]
        ]
    ])
}

// ================================================================
// PIPELINE PRINCIPAL
// ================================================================
pipeline {
    agent any

    environment {
        APP_URL          = 'http://localhost:8000'
        METRICS_API_KEY  = 'tu_clave_aqui'
        TOOL_NAME        = 'jenkins'
        GITHUB_TOKEN     = credentials('token-api-jenkins')

        // ⚠️  IMPORTANTE: Mueve estas credenciales a Jenkins Credentials Manager
        // Panel Jenkins → Manage Jenkins → Credentials → Add Credential (Secret text)
        // ID sugerido: 'jira-api-token'
        // Una vez hecho, reemplaza las líneas de abajo por:
        //   JIRA_API_TOKEN = credentials('jira-api-token')
        //
        // NUNCA pongas tokens en texto plano en el Jenkinsfile — están expuestos en Git.
        JIRA_URL         = 'https://gestortareas.atlassian.net'   // sin slash al final
        JIRA_USERNAME    = 'gamr130898@gmail.com'
        JIRA_API_TOKEN   = credentials('API TOKEN JIRA')          // crea este credential
    }

    stages {
        // ============================================================
        // STAGE 1: CHECKOUT & CAPTURAR INFO GIT
        // ============================================================
        stage('Checkout & Git Info') {
            steps {
                script {
                    echo '🔍 Capturando información de Git...'
                    checkout scm

                    // SHA del commit actual
                    env.GIT_COMMIT_SHA = bat(
                        script: '@echo off & git rev-parse HEAD',
                        returnStdout: true
                    ).trim()

                    // Timestamp epoch del commit
                    def commitTimestampEpoch = bat(
                        script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}",
                        returnStdout: true
                    ).trim()
                    env.COMMIT_TIME_EPOCH = commitTimestampEpoch
                    env.COMMIT_TIME_ISO   = new Date(
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

                    // Timestamp de inicio del pipeline
                    env.PIPELINE_START_EPOCH = ((long)(System.currentTimeMillis() / 1000)).toString()
                    env.PIPELINE_START_ISO   = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                    // ─── Extraer Jira keys con @NonCPS ────────────────────────
                    // Se combinan mensaje + branch, se extraen keys únicas y
                    // se guardan como String simple (ej: "KAN-1,KAN-2") en env
                    // para que sea serializable entre stages sin problema.
                    def rawText  = (env.GIT_MESSAGE ?: '') + ' ' + (env.GIT_BRANCH ?: '')
                    def keys     = extractJiraKeys(rawText)
                    env.JIRA_KEYS = keys.unique().join(',')  // String serializable

                    echo "📝 Commit SHA   : ${env.GIT_COMMIT_SHA}"
                    echo "⏱️  Commit Time  : ${env.COMMIT_TIME_ISO}"
                    echo "👤 Author       : ${env.GIT_AUTHOR}"
                    echo "💬 Message      : ${env.GIT_MESSAGE}"
                    echo "🌿 Branch       : ${env.GIT_BRANCH}"
                    echo "🎫 Jira Keys    : ${env.JIRA_KEYS ?: 'ninguno detectado'}"
                }
            }
        }

        // ============================================================
        // STAGE 2: BUILD & DEPLOY (Docker)
        // Capturamos tiempo de build para aportarlo al Lead Time
        // ============================================================
        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    echo '🏗️ Levantando entorno Docker...'

                    env.DOCKER_BUILD_START = ((long)(System.currentTimeMillis() / 1000)).toString()
                    bat 'docker compose up -d --build'
                    env.DOCKER_BUILD_DURATION = ((long)(System.currentTimeMillis() / 1000) - env.DOCKER_BUILD_START.toLong()).toString()
                    echo "🐳 Docker build: ${env.DOCKER_BUILD_DURATION}s"

                    echo '📊 Migraciones y Limpieza...'
                    bat 'docker compose exec -T app php artisan migrate --force --seed'
                    bat 'docker compose exec -T app php artisan cache:clear'
                }
            }
        }

        // ============================================================
        // STAGE 3: TESTS
        // Capturamos duración para métricas de calidad
        // ============================================================
        stage('Run Tests') {
            steps {
                script {
                    echo '🧪 Ejecutando tests...'

                    env.TEST_START = ((long)(System.currentTimeMillis() / 1000)).toString()
                    bat 'docker compose exec -T app php artisan test'
                    env.TEST_DURATION = ((long)(System.currentTimeMillis() / 1000) - env.TEST_START.toLong()).toString()
                    echo "✅ Tests completados en: ${env.TEST_DURATION}s"
                }
            }
        }

        // ============================================================
        // STAGE 4: MÉTRICAS DORA — 4 métricas base
        // ============================================================
        stage('Track DORA Metrics') {
            steps {
                script {
                    echo '📊 Registrando Métricas DORA...'

                    def nowIso   = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                    def nowEpoch = (System.currentTimeMillis() / 1000).toLong()

                    // ── 1. DEPLOYMENT FREQUENCY ──────────────────────────────
                    def deploymentData = JsonOutput.toJson([
                        tool                  : env.TOOL_NAME,
                        build_number          : env.BUILD_NUMBER,
                        build_duration_seconds: env.DOCKER_BUILD_DURATION?.toInteger() ?: 0,
                        test_duration_seconds : env.TEST_DURATION?.toInteger() ?: 0,
                        commit_sha            : env.GIT_COMMIT_SHA,
                        commit_author         : env.GIT_AUTHOR,
                        commit_message        : env.GIT_MESSAGE,
                        commit_timestamp      : env.COMMIT_TIME_ISO,
                        branch                : env.GIT_BRANCH,
                        jira_keys             : env.JIRA_KEYS,
                        status                : 'success',
                        is_failure            : false,
                        environment           : 'prod',
                        deployed_at           : nowIso,
                        timestamp             : nowIso
                    ])
                    echo '📤 Enviando deployment metric...'
                    bat "curl -s -X POST ${env.APP_URL}/api/metrics/deployment " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${deploymentData.replace('"', '\\"')}\""

                    // ── 2. LEAD TIME FOR CHANGES ─────────────────────────────
                    // Lead Time técnico: desde commit hasta deploy
                    long leadTimeSeconds = nowEpoch - env.COMMIT_TIME_EPOCH.toLong()
                    def leadTimeData = JsonOutput.toJson([
                        tool                 : env.TOOL_NAME,
                        commit_sha           : env.GIT_COMMIT_SHA,
                        commit_timestamp     : env.COMMIT_TIME_ISO,
                        deployment_timestamp : nowIso,
                        lead_time_seconds    : leadTimeSeconds,
                        lead_time_minutes    : (leadTimeSeconds / 60).toInteger(),
                        jira_keys            : env.JIRA_KEYS,
                        timestamp            : nowIso
                    ])
                    echo '📤 Enviando lead time metric...'
                    bat "curl -s -X POST ${env.APP_URL}/api/metrics/leadtime " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${leadTimeData.replace('"', '\\"')}\""

                    // ── 3. CHANGE FAILURE RATE ───────────────────────────────
                    def resultData = JsonOutput.toJson([
                        tool         : env.TOOL_NAME,
                        build_number : env.BUILD_NUMBER,
                        commit_sha   : env.GIT_COMMIT_SHA,
                        status       : 'success',
                        is_failure   : false,
                        timestamp    : nowIso
                    ])
                    echo '📤 Enviando deployment result metric...'
                    bat "curl -s -X POST ${env.APP_URL}/api/metrics/deployment-result " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${resultData.replace('"', '\\"')}\""

                    // ── 4. MTTR — resolver incidentes abiertos ───────────────
                    def resolveData = JsonOutput.toJson([
                        tool            : env.TOOL_NAME,
                        resolved_by     : 'jenkins-automation',
                        resolution_time : nowIso,
                        timestamp       : nowIso
                    ])
                    echo '📤 Resolviendo incidentes previos...'
                    bat "curl -s -X POST ${env.APP_URL}/api/metrics/incident/resolve " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${resolveData.replace('"', '\\"')}\""
                }
            }
        }

        // ============================================================
        // STAGE 5: JIRA INTEGRATION
        // Registra actividad del issue + consulta API de Jira
        // para obtener datos reales (created_at, story_points, status)
        // ============================================================
        stage('Jira Integration') {
            when {
                // Solo corre si se detectaron tickets en el commit/branch
                expression { return env.JIRA_KEYS != null && env.JIRA_KEYS.trim() != '' }
            }
            steps {
                script {
                    echo "🔗 Integrando con Jira — tickets: ${env.JIRA_KEYS}"

                    def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                    // env.JIRA_KEYS = "KAN-1,KAN-2" → split seguro, sin Matcher
                    env.JIRA_KEYS.split(',').each { rawKey ->
                        def issueKey = rawKey.trim()
                        echo "🎫 Procesando ticket: ${issueKey}"

                        // ── 5a. Registrar actividad del issue en tu BD ────────
                        def jiraMetric = JsonOutput.toJson([
                            type      : 'jira-issue',
                            tool      : env.TOOL_NAME,
                            timestamp : nowIso,
                            data      : [
                                issue_key  : issueKey,
                                summary    : env.GIT_MESSAGE,
                                assignee   : env.GIT_AUTHOR,
                                branch     : env.GIT_BRANCH,
                                commit_sha : env.GIT_COMMIT_SHA,
                                status     : 'In Progress'
                            ]
                        ])
                        bat "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue " +
                            '-H \"Content-Type: application/json\" ' +
                            "-d \"${jiraMetric.replace('"', '\\"')}\""

                        // ── 5b. Consultar API real de Jira ────────────────────
                        // Obtenemos: created_at (Lead Time negocio), story_points,
                        // status real, tipo de issue, prioridad
                        echo "📋 Consultando Jira API para datos reales de ${issueKey}..."
                        def jiraApiResponse = bat(
                            script: '@echo off & curl -s ' +
                                    "-u \"${env.JIRA_USERNAME}:${env.JIRA_API_TOKEN}\" " +
                                    '-H \"Accept: application/json\" ' +
                                    "\"${env.JIRA_URL}/rest/api/3/issue/${issueKey}?fields=summary,status,assignee,created,updated,priority,issuetype,customfield_10016\"",
                            returnStdout: true
                        ).trim()

                        // Enviar datos crudos de la API de Jira a tu endpoint
                        // Laravel los parsea y extrae: created_at, story_points, status real
                        bat "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue/from-api " +
                            '-H \"Content-Type: application/json\" ' +
                            "-d \"${jiraApiResponse.replace('"', '\\"')}\""

                        echo "✅ Jira data sincronizada para: ${issueKey}"
                    }
                }
            }
        }

        // ============================================================
        // STAGE 6: GITHUB PR DATA
        // Captura Pull Requests asociados al commit actual
        // Útil para medir tiempo de code review dentro del Lead Time
        // ============================================================
        stage('Capture GitHub PR Data') {
            when {
                expression { return env.GITHUB_TOKEN != null && env.GITHUB_TOKEN.length() > 0 }
            }
            steps {
                script {
                    echo '🔍 Buscando Pull Requests asociados al commit...'

                    def prResponse = bat(
                        script: '@echo off & curl -s ' +
                                "-H \"Authorization: Bearer ${env.GITHUB_TOKEN}\" " +
                                '-H \"Accept: application/vnd.github+json\" ' +
                                '-H \"X-GitHub-Api-Version: 2022-11-28\" ' +
                                "\"https://api.github.com/repos/GAMR11/ecommerce-online/commits/${env.GIT_COMMIT_SHA}/pulls\"",
                        returnStdout: true
                    ).trim()

                    bat "curl -s -X POST ${env.APP_URL}/api/metrics/github-pr-raw " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${prResponse.replace('"', '\\"')}\""

                    echo '📤 PR data enviada'
                }
            }
        }

        // ============================================================
        // STAGE 7: GITHUB COMMIT DATA
        // ============================================================
        stage('Capture GitHub Data') {
            when {
                expression { return env.GITHUB_TOKEN != null && env.GITHUB_TOKEN.length() > 0 }
            }
            steps {
                script {
                    echo '🔍 Capturando datos de GitHub...'

                    def githubData = JsonOutput.toJson([
                        tool       : 'github',
                        commit_sha : env.GIT_COMMIT_SHA,
                        branch     : env.GIT_BRANCH,
                        author     : env.GIT_AUTHOR,
                        message    : env.GIT_MESSAGE,
                        jira_keys  : env.JIRA_KEYS,
                        timestamp  : env.COMMIT_TIME_ISO
                    ])

                    echo '📤 Enviando GitHub commit data...'
                    bat "curl -s -X POST ${env.APP_URL}/api/metrics/github-commit " +
                        '-H \"Content-Type: application/json\" ' +
                        "-d \"${githubData.replace('"', '\\"')}\""
                }
            }
        }
    }

    // ============================================================
    // POST — Acciones según resultado del pipeline
    // ============================================================
    post {
        success {
            script {
                echo '✅ Pipeline exitoso — Comentando en Jira...'

                // Agregar comentario automático en cada ticket detectado
                if (env.JIRA_KEYS && env.JIRA_KEYS.trim() != '') {
                    env.JIRA_KEYS.split(',').each { rawKey ->
                        def issueKey = rawKey.trim()
                        // buildJiraSuccessComment es @NonCPS — seguro, sin Matcher
                        def comment  = buildJiraSuccessComment(
                            env.BUILD_NUMBER, env.GIT_BRANCH, env.GIT_MESSAGE
                        )
                        bat 'curl -s -X POST ' +
                            "-u \"${env.JIRA_USERNAME}:${env.JIRA_API_TOKEN}\" " +
                            '-H \"Content-Type: application/json\" ' +
                            "\"${env.JIRA_URL}/rest/api/3/issue/${issueKey}/comment\" " +
                            "-d \"${comment.replace('"', '\\"')}\""
                        echo "💬 Comentario agregado en Jira: ${issueKey}"
                    }
                }
            }
        }

        failure {
            script {
                echo '❌ Pipeline FALLÓ — Registrando incidente y notificando Jira...'
                def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                // ── Registrar deployment fallido (Change Failure Rate) ────────
                def failData = JsonOutput.toJson([
                    tool         : env.TOOL_NAME,
                    build_number : env.BUILD_NUMBER,
                    commit_sha   : env.GIT_COMMIT_SHA ?: 'unknown',
                    status       : 'failure',
                    is_failure   : true,
                    timestamp    : nowIso
                ])
                bat "curl -s -X POST ${env.APP_URL}/api/metrics/deployment-result " +
                    '-H \"Content-Type: application/json\" ' +
                    "-d \"${failData.replace('"', '\\"')}\""

                // ── Crear incidente abierto (MTTR) ────────────────────────────
                def incidentData = JsonOutput.toJson([
                    tool         : env.TOOL_NAME,
                    build_number : env.BUILD_NUMBER,
                    commit_sha   : env.GIT_COMMIT_SHA ?: 'unknown',
                    status       : 'open',
                    severity     : 'high',
                    start_time   : nowIso,
                    description  : "Build #${env.BUILD_NUMBER} failed on branch ${env.GIT_BRANCH ?: 'unknown'}",
                    timestamp    : nowIso
                ])
                bat "curl -s -X POST ${env.APP_URL}/api/metrics/incident " +
                    '-H \"Content-Type: application/json\" ' +
                    "-d \"${incidentData.replace('"', '\\"')}\""

                // ── Comentar en Jira con el fallo ─────────────────────────────
                if (env.JIRA_KEYS && env.JIRA_KEYS.trim() != '') {
                    env.JIRA_KEYS.split(',').each { rawKey ->
                        def issueKey = rawKey.trim()
                        def comment  = buildJiraFailureComment(env.BUILD_NUMBER, env.GIT_BRANCH ?: 'unknown')
                        bat 'curl -s -X POST ' +
                            "-u \"${env.JIRA_USERNAME}:${env.JIRA_API_TOKEN}\" " +
                            '-H \"Content-Type: application/json\" ' +
                            "\"${env.JIRA_URL}/rest/api/3/issue/${issueKey}/comment\" " +
                            "-d \"${comment.replace('"', '\\"')}\""
                        echo "💬 Notificación de fallo enviada a Jira: ${issueKey}"
                    }
                }
            }
        }
    }
}
