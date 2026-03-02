import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        APP_URL         = 'http://localhost:8000'
        METRICS_API_KEY = 'tu_clave_aqui' 
        TOOL_NAME       = 'jenkins'
        JIRA_SITE       = 'API TOKEN JIRA' // Asegúrate de que este nombre coincida con el de la config global de Jenkins
    }

    stages {
        stage('Checkout & Info') {
            steps {
                script {
                    echo '🔍 Obteniendo información del Git...'
                    checkout scm

                    env.GIT_COMMIT_SHA = bat(script: '@echo off & git rev-parse HEAD', returnStdout: true).trim()
                    def commitTimestamp = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()

                    env.COMMIT_TIME_EPOCH = commitTimestamp
                    env.PIPELINE_START_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()
                    env.COMMIT_TIME_ISO = new Date(commitTimestamp.toLong() * 1000).format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️ Commit Time: ${env.COMMIT_TIME_ISO}"
                }
            }
        }

        stage('Fetch Jira Info') {
            steps {
                script {
                    def jiraTicket = env.BRANCH_NAME.find(/KAN-\d+/)
                    if (jiraTicket) {
                        try {
                            def issue = jiraGetIssue(idOrKey: jiraTicket, site: env.JIRA_SITE)
                            env.JIRA_CREATED_AT = issue.data.fields.created.replace('T', ' ').substring(0, 19)
                            env.JIRA_ISSUE_KEY = jiraTicket
                            echo "✅ Jira Ticket: ${env.JIRA_ISSUE_KEY} creado el ${env.JIRA_CREATED_AT}"
                        } catch (e) {
                            echo "⚠️ No se pudo obtener info de Jira: ${e.message}"
                        }
                    } else {
                        echo "ℹ️ No se detectó ticket de Jira en el nombre de la rama."
                    }
                }
            }
        }

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

        stage('Run Tests') {
            steps {
                script {
                    echo '🧪 Ejecutando tests unitarios...'
                    bat 'docker compose exec -T app php artisan test'
                }
            }
        }

        stage('Track DORA Metrics') {
            steps {
                script {
                    echo '📊 Procesando métricas integrales...'
                    def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))
                    
                    // En Jenkins Declarative, si llegamos aquí es que los stages previos fueron SUCCESS
                    def currentStatus = 'SUCCESS'

                    def baseData = [
                        tool: env.TOOL_NAME,
                        timestamp: nowIso,
                        commit: env.GIT_COMMIT_SHA,
                        jira_key: env.JIRA_ISSUE_KEY ?: null,
                        jira_created_at: env.JIRA_CREATED_AT ?: null,
                        commit_at: env.COMMIT_TIME_ISO,
                        build_number: env.BUILD_NUMBER,
                        status: currentStatus,
                        is_failure: false
                    ]

                    echo "📤 Enviando Deployment Result..."
                    bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(baseData).replace('"', '\\"')}\""

                    echo "📤 Enviando Lead Time..."
                    bat "curl -X POST ${env.APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(baseData).replace('"', '\\"')}\""

                    echo "📤 Resolviendo incidentes previos (MTTR)..."
                    def resolveData = [tool: env.TOOL_NAME, resolution_time: nowIso]
                    bat "curl -X POST ${env.APP_URL}/api/metrics/incident/resolve -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(resolveData).replace('"', '\\"')}\""
                }
            }
        }
    }

    post {
        failure {
            script {
                echo '❌ Pipeline fallido - Registrando Incidente...'
                def nowIso = new Date().format('yyyy-MM-dd HH:mm:ss', TimeZone.getTimeZone('UTC'))

                def failData = [
                    tool: env.TOOL_NAME,
                    timestamp: nowIso,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
                    is_failure: true,
                    status: 'FAILURE'
                ]
                
                // Registrar fallo para Change Failure Rate
                bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(failData).replace('"', '\\"')}\""

                // Crear incidente para MTTR
                def incidentData = failData + [
                    start_time: nowIso,
                    description: "Build #${env.BUILD_NUMBER} failed"
                ]
                bat "curl -X POST ${env.APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(incidentData).replace('"', '\\"')}\""
            }
        }
    }
}