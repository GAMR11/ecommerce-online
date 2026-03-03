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
                    echo '🏗️ Levantando contenedores...'
                    bat 'docker compose up -d --build'
                    
                    echo '🔧 Ajustando permisos (modo preventivo)...'
                    // Añadimos || exit 0 para que si chmod falla en algún archivo, el pipeline siga
                    bat 'docker compose exec -T app chmod -R 777 storage bootstrap/cache || exit 0'

                    echo '📝 Verificando/Generando migración...'
                    bat """
                        docker compose exec -T app php -r "if (empty(glob('database/migrations/*_create_jira_issues_table.php'))) { exec('php artisan make:migration create_jira_issues_table'); }"
                    """

                    echo '🛠️ Inyectando estructura de tabla...'
                    def migrationCode = """<?php
                    use Illuminate\\Database\\Migrations\\Migration;
                    use Illuminate\\Database\\Schema\\Blueprint;
                    use Illuminate\\Support\\Facades\\Schema;

                    return new class extends Migration {
                        public function up(): void {
                            if (!Schema::hasTable('jira_issues')) {
                                Schema::create('jira_issues', function (Blueprint \$table) {
                                    \$table->id();
                                    \$table->string('jira_key')->unique();
                                    \$table->string('issue_type')->nullable();
                                    \$table->string('summary')->nullable();
                                    \$table->text('description')->nullable();
                                    \$table->string('status')->nullable();
                                    \$table->string('assignee')->nullable();
                                    \$table->string('reporter')->nullable();
                                    \$table->string('sprint_id')->nullable();
                                    \$table->float('story_points')->nullable();
                                    \$table->timestamp('created_at')->nullable();
                                    \$table->timestamp('completed_at')->nullable();
                                    \$table->timestamp('updated_at')->useCurrent();
                                });
                            }
                        }
                        public function down(): void { Schema::dropIfExists('jira_issues'); }
                    };"""
                        bat "docker compose exec -T app php -r \"\$file = glob('database/migrations/*_create_jira_issues_table.php')[0]; file_put_contents(\$file, base64_decode('" + migrationCode.bytes.encodeBase64().toString() + "'));\""

                        echo '🚀 Ejecutando migración...'
                        bat 'docker compose exec -T app php artisan migrate --force'
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

        stage('Jira Issue Discovery') {
            steps {
                script {
                    echo '🔗 Analizando tickets de Jira...'
                    def jiraPattern = /([A-Z]+-\d+)/
                    def issues = []

                    def msgMatches = (env.GIT_MESSAGE =~ jiraPattern)
                    while (msgMatches.find()) { issues << msgMatches.group(1) }

                    if (issues.isEmpty()) {
                        echo 'ℹ️ No se detectaron issues.'
                    } else {
                        issues.unique().each { issueKey ->
                            echo "🚀 Procesando: ${issueKey}"

                            // Usamos un bloque try/catch de Groovy para que el pipeline NO falle si el CURL da error
                            try {
                                def fetchData = JsonOutput.toJson([tool: env.TOOL_NAME, issue_key: issueKey, timestamp: env.PIPELINE_START_ISO])
                                // El "|| exit 0" al final del comando bat evita que Jenkins marque error si el curl falla
                                bat "curl -s -X POST ${env.APP_URL}/api/metrics/jira-issue/fetch -H \"Content-Type: application/json\" -d \"${fetchData.replace('"', '\\"')}\" || exit 0"
                            } catch (Exception e) {
                                echo '⚠️ No se pudo conectar con el endpoint de Jira.'
                            }
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
