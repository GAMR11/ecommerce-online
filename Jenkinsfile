import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        // Usamos localhost:8000 para llegar a la API de Laravel desde el host de Jenkins
        APP_URL         = 'http://localhost:8000'
        METRICS_API_KEY = 'tu_clave_aqui' // Configúrala en el middleware si la activas
        TOOL_NAME       = 'jenkins'
        JIRA_SITE = 'API TOKEN JIRA'
    }

    stages {
        stage('Checkout & Info') {
            steps {
                script {
                    echo "🔍 Obteniendo información del Git..."
                    checkout scm

                    // Capturar SHA del commit y timestamp del commit (epoch)
                    env.GIT_COMMIT_SHA = bat(script: "@echo off & git rev-parse HEAD", returnStdout: true).trim()
                    def commitTimestamp = bat(script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}", returnStdout: true).trim()

                    // Guardar tiempos para cálculos posteriores
                    env.COMMIT_TIME_EPOCH = commitTimestamp
                    env.PIPELINE_START_EPOCH = ((long) (System.currentTimeMillis() / 1000)).toString()

                    // Formato legible para la DB
                    env.COMMIT_TIME_ISO = new Date(commitTimestamp.toLong() * 1000).format("yyyy-MM-dd HH:mm:ss", TimeZone.getTimeZone('UTC'))

                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️ Commit Time: ${env.COMMIT_TIME_ISO}"
                }
            }
        }

        stage('Fetch Jira Info') {
            steps {
                script {
                    // Extraer KAN-X de la rama
                    def jiraTicket = env.BRANCH_NAME.find(/KAN-\d+/)
                    if (jiraTicket) {
                        try {
                            def issue = jiraGetIssue(idOrKey: jiraTicket, site: env.JIRA_SITE)
                            // Jira devuelve ISO8601, lo normalizamos para Laravel
                            env.JIRA_CREATED_AT = issue.data.fields.created.replace('T', ' ').substring(0, 19)
                            env.JIRA_ISSUE_KEY = jiraTicket
                            echo "✅ Jira Ticket: ${env.JIRA_ISSUE_KEY} creado el ${env.JIRA_CREATED_AT}"
                        } catch (e) {
                            echo "⚠️ No se pudo obtener info de Jira: ${e.message}"
                        }
                    }
                }
            }
        }

        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    echo "🏗️ Levantando entorno Docker..."
                    bat 'docker compose up -d --build'

                    echo "📊 Migraciones y Limpieza..."
                    bat 'docker compose exec -T app php artisan migrate --force --seed'
                    bat 'docker compose exec -T app php artisan cache:clear'
                }
            }
        }

        stage('Run Tests') {
            steps {
                script {
                    echo "🧪 Ejecutando tests unitarios..."
                    // Si falla aquí, irá al bloque post { failure }
                    bat 'docker compose exec -T app php artisan test'
                }
            }
        }

    }

        stage('Track DORA Metrics') {
    steps {
        script {
            echo "📊 Procesando métricas integrales..."
            def nowIso = new Date().format("yyyy-MM-dd HH:mm:ss", TimeZone.getTimeZone('UTC'))
            def currentStatus = currentBuild.result ?: 'SUCCESS'

            // Payload base con la trazabilidad completa
            def baseData = [
                tool: env.TOOL_NAME,
                timestamp: nowIso,
                commit: env.GIT_COMMIT_SHA,
                jira_key: env.JIRA_ISSUE_KEY ?: null,
                jira_created_at: env.JIRA_CREATED_AT ?: null, // Dato de negocio
                commit_at: env.COMMIT_TIME_ISO,               // Dato técnico
                build_number: env.BUILD_NUMBER,
                status: currentStatus
            ]

            // CASO 1: Éxito (Deployment Frequency & Lead Time)
            if (currentStatus == 'SUCCESS') {
                baseData.is_failure = false

                // Registro de Despliegue Exitoso
                bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(baseData).replace('"', '\\"')}\""

                // Registro de Lead Time (El controlador calculará técnico vs negocio)
                bat "curl -X POST ${env.APP_URL}/api/metrics/leadtime -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(baseData).replace('"', '\\"')}\""

                // CASO 2: Recuperación (MTTR)
                // Si veníamos de un fallo, este éxito cierra el incidente
                def resolveData = [tool: env.TOOL_NAME, resolution_time: nowIso]
                bat "curl -X POST ${env.APP_URL}/api/metrics/incident/resolve -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(resolveData).replace('"', '\\"')}\""
            }

            // CASO 3: Fallo (Change Failure Rate)
            else {
                baseData.is_failure = true

                // Registro de Despliegue Fallido
                bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(baseData).replace('"', '\\"')}\""

                // Apertura de Incidente para MTTR
                def incidentData = baseData + [status: "open", start_time: nowIso, description: "Pipeline failed in ${env.STAGE_NAME}"]
                bat "curl -X POST ${env.APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -d \"${JsonOutput.toJson(incidentData).replace('"', '\\"')}\""
            }
        }
    }
        }
}

    post {
        failure {
            script {
                echo "❌ Pipeline fallido - Registrando Incidente..."
                def nowIso = new Date().format("yyyy-MM-dd HH:mm:ss", TimeZone.getTimeZone('UTC'))

                // Registrar el fallo para Change Failure Rate
                def failData = JsonOutput.toJson([
                    tool: env.TOOL_NAME,
                    timestamp: nowIso,
                    commit: env.GIT_COMMIT_SHA ?: 'unknown',
                    is_failure: true
                ])
                bat "curl -X POST ${env.APP_URL}/api/metrics/deployment-result -H \"Content-Type: application/json\" -d \"${failData.replace('"', '\\"')}\""

                // Crear un incidente abierto para MTTR
                def incidentData = JsonOutput.toJson([
                    tool: env.TOOL_NAME,
                    start_time: nowIso,
                    timestamp: nowIso,
                    status: "open",
                    description: "Build #${env.BUILD_NUMBER} failed"
                ])
                bat "curl -X POST ${env.APP_URL}/api/metrics/incident -H \"Content-Type: application/json\" -d \"${incidentData.replace('"', '\\"')}\""
            }
        }
    }
}
