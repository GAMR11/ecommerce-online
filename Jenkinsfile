import groovy.json.JsonOutput

pipeline {
    agent any

    environment {
        APP_URL         = 'http://localhost:8000'
        METRICS_API_KEY = 'tu_clave_aqui'
        TOOL_NAME       = 'jenkins'
    }

    stages {

        stage('Checkout & Info') {
            steps {
                script {
                    echo "🔍 Obteniendo información del Git..."
                    checkout scm

                    env.GIT_COMMIT_SHA = bat(
                        script: '@echo off & git rev-parse HEAD',
                        returnStdout: true
                    ).trim()

                    def commitTimestamp = bat(
                        script: "@echo off & git show -s --format=%%ct ${env.GIT_COMMIT_SHA}",
                        returnStdout: true
                    ).trim()

                    env.COMMIT_TIME_EPOCH      = commitTimestamp
                    env.PIPELINE_START_EPOCH   = ((long)(System.currentTimeMillis() / 1000)).toString()
                    env.COMMIT_TIME_ISO        = new Date(commitTimestamp.toLong() * 1000)
                                                    .format("yyyy-MM-dd HH:mm:ss", TimeZone.getTimeZone('UTC'))

                    echo "📝 Commit: ${env.GIT_COMMIT_SHA}"
                    echo "⏱️ Commit Time: ${env.COMMIT_TIME_ISO}"
                }
            }
        }

        stage('Build & Deploy (Docker)') {
            steps {
                script {
                    echo "🏗️ Levantando entorno Docker..."
                    bat 'docker compose up -d --build'

                    echo "📊 Migraciones y Limpieza..."
                    bat 'docker compose exec -T app php artisan migrate:fresh --force --seed'
                    bat 'docker compose exec -T app php artisan cache:clear'
                }
            }
        }

        stage('Run Tests') {
            steps {
                script {
                    echo "🧪 Ejecutando tests unitarios..."
                    bat 'docker compose exec -T app php artisan test'
                }
            }
        }

        stage('Track DORA Metrics') {
            steps {
                script {
                    echo "📊 Registrando Métricas DORA..."

                    def nowIso   = new Date().format("yyyy-MM-dd HH:mm:ss", TimeZone.getTimeZone('UTC'))
                    def nowEpoch = (System.currentTimeMillis() / 1000).toLong()

                    /*
                     |--------------------------------------------------
                     | MÉTRICA 1 & 3: Deployment Frequency & Success
                     |--------------------------------------------------
                     */

                    def deploymentData = JsonOutput.toJson([
                        tool      : env.TOOL_NAME,
                        timestamp : nowIso,
                        commit    : env.GIT_COMMIT_SHA,
                        status    : "success",
                        is_failure: false
                    ])

                    writeFile file: 'deployment.json', text: deploymentData

                    bat """
                        curl -X POST ${env.APP_URL}/api/metrics/deployment ^
                        -H "Content-Type: application/json" ^
                        --data @deployment.json
                    """

                    bat """
                        curl -X POST ${env.APP_URL}/api/metrics/deployment-result ^
                        -H "Content-Type: application/json" ^
                        --data @deployment.json
                    """

                    /*
                     |--------------------------------------------------
                     | MÉTRICA 2: Lead Time for Changes
                     |--------------------------------------------------
                     */

                    long leadTimeSeconds = nowEpoch - env.COMMIT_TIME_EPOCH.toLong()

                    def leadTimeData = JsonOutput.toJson([
                        tool              : env.TOOL_NAME,
                        commit            : env.GIT_COMMIT_SHA,
                        lead_time_seconds : leadTimeSeconds,
                        timestamp         : nowIso
                    ])

                    writeFile file: 'leadtime.json', text: leadTimeData

                    bat """
                        curl -X POST ${env.APP_URL}/api/metrics/leadtime ^
                        -H "Content-Type: application/json" ^
                        --data @leadtime.json
                    """

                    /*
                     |--------------------------------------------------
                     | MÉTRICA 4: MTTR (Auto-Resolve)
                     |--------------------------------------------------
                     */

                    def resolveData = JsonOutput.toJson([
                        tool           : env.TOOL_NAME,
                        resolution_time: nowIso
                    ])

                    writeFile file: 'resolve.json', text: resolveData

                    bat """
                        curl -X POST ${env.APP_URL}/api/metrics/incident/resolve ^
                        -H "Content-Type: application/json" ^
                        --data @resolve.json
                    """
                }
            }
        }
    }

    post {
        failure {
            script {
                echo "❌ Pipeline fallido - Registrando Incidente..."

                def nowIso = new Date().format("yyyy-MM-dd HH:mm:ss", TimeZone.getTimeZone('UTC'))

                /*
                 |--------------------------------------------------
                 | Change Failure Rate
                 |--------------------------------------------------
                 */

                def failData = JsonOutput.toJson([
                    tool      : env.TOOL_NAME,
                    timestamp : nowIso,
                    commit    : env.GIT_COMMIT_SHA ?: 'unknown',
                    is_failure: true
                ])

                writeFile file: 'failure.json', text: failData

                bat """
                    curl -X POST ${env.APP_URL}/api/metrics/deployment-result ^
                    -H "Content-Type: application/json" ^
                    --data @failure.json
                """

                /*
                 |--------------------------------------------------
                 | Crear Incidente (MTTR)
                 |--------------------------------------------------
                 */

                def incidentData = JsonOutput.toJson([
                    tool       : env.TOOL_NAME,
                    start_time : nowIso,
                    timestamp  : nowIso,
                    status     : "open",
                    description: "Build #${env.BUILD_NUMBER} failed"
                ])

                writeFile file: 'incident.json', text: incidentData

                bat """
                    curl -X POST ${env.APP_URL}/api/metrics/incident ^
                    -H "Content-Type: application/json" ^
                    --data @incident.json
                """
            }
        }
    }
}