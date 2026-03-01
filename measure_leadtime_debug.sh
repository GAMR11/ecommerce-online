#!/bin/bash

# Script de medición de Lead Time for Changes
# Docker vs Podman - VERSION CON DEBUG MEJORADO
# Uso: ./measure_leadtime_debug.sh <docker|podman> [iteraciones]

set -e

TOOL=$1
ITERATIONS=${2:-5}
RESULTS_DIR="benchmark/results"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

if [ "$TOOL" != "docker" ] && [ "$TOOL" != "podman" ]; then
    echo -e "${RED}Error: Herramienta no válida${NC}"
    echo "Uso: $0 <docker|podman> [iteraciones]"
    exit 1
fi

echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}Midiendo Lead Time for Changes con $TOOL${NC}"
echo -e "${BLUE}==========================================${NC}"
echo -e "Iteraciones: ${YELLOW}$ITERATIONS${NC}"
echo ""

# Crear directorio de resultados
mkdir -p "$RESULTS_DIR"

# Definir nombres de contenedores específicos del benchmark
if [ "$TOOL" = "docker" ]; then
    COMPOSE_FILE="docker-compose-benchmark.yml"
    COMPOSE_CMD="docker-compose"
    APP_CONTAINER="laravel_app_docker_benchmark"
    DB_CONTAINER="mysql_db_docker_benchmark"
    PORT="8080"
    NETWORK_NAME="docker_laravel_network"
    TOOL_CMD="docker"
else
    COMPOSE_FILE="podman-compose-benchmark.yml"
    COMPOSE_CMD="podman-compose"
    APP_CONTAINER="laravel_app_podman_benchmark"
    DB_CONTAINER="mysql_db_podman_benchmark"
    PORT="8081"
    NETWORK_NAME="podman_laravel_network"
    TOOL_CMD="podman"
fi

# Función para limpiar SOLO los contenedores del benchmark
cleanup_benchmark() {
    echo -e "${YELLOW}Limpiando contenedores del benchmark...${NC}"
    
    # Detener y eliminar SOLO los contenedores del benchmark
    $TOOL_CMD stop $APP_CONTAINER 2>/dev/null || true
    $TOOL_CMD stop $DB_CONTAINER 2>/dev/null || true
    $TOOL_CMD rm -f $APP_CONTAINER 2>/dev/null || true
    $TOOL_CMD rm -f $DB_CONTAINER 2>/dev/null || true
    
    # Eliminar red del benchmark si existe
    $TOOL_CMD network rm $NETWORK_NAME 2>/dev/null || true
    
    # Eliminar imagen del benchmark (para forzar rebuild)
    $TOOL_CMD rmi -f benchmark-${TOOL}-app 2>/dev/null || true
    
    sleep 2
}

# Función para verificar logs
check_logs() {
    local container=$1
    echo -e "\n${BLUE}>>> Últimas líneas de logs de $container:${NC}"
    $TOOL_CMD logs --tail 10 $container 2>&1 || echo "No hay logs disponibles"
}

# Función para verificar estado de contenedor
check_container_status() {
    local container=$1
    echo -e "${BLUE}>>> Estado de $container:${NC}"
    $TOOL_CMD ps -a | grep $container || echo "Contenedor no encontrado"
}

# Limpiar contenedores previos del benchmark
echo -e "${YELLOW}Limpiando entorno previo del benchmark...${NC}"
cleanup_benchmark

echo ""

# Archivo de resultados
RESULT_FILE="$RESULTS_DIR/${TOOL}_results_$(date +%Y%m%d_%H%M%S).json"

echo "{" > "$RESULT_FILE"
echo "  \"tool\": \"$TOOL\"," >> "$RESULT_FILE"
echo "  \"timestamp\": \"$(date -Iseconds)\"," >> "$RESULT_FILE"
echo "  \"hostname\": \"$(hostname)\"," >> "$RESULT_FILE"
echo "  \"iterations\": [" >> "$RESULT_FILE"

for i in $(seq 1 "$ITERATIONS"); do
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}    Iteración $i/$ITERATIONS${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    # Limpiar antes de cada iteración
    cleanup_benchmark
    
    sleep 3
    
    # 1. Medir tiempo de BUILD
    echo ""
    echo -e "${GREEN}[1/4] Construyendo imágenes...${NC}"
    BUILD_START=$(date +%s.%N)
    
    if [ "$TOOL" = "docker" ]; then
        cd benchmark/docker
        $COMPOSE_CMD -f $COMPOSE_FILE build --no-cache
        BUILD_STATUS=$?
        cd ../..
    else
        cd benchmark/podman
        $COMPOSE_CMD -f $COMPOSE_FILE build --no-cache
        BUILD_STATUS=$?
        cd ../..
    fi
    
    if [ $BUILD_STATUS -ne 0 ]; then
        echo -e "${RED}✗ Error en build${NC}"
        continue
    fi
    
    BUILD_END=$(date +%s.%N)
    BUILD_TIME=$(awk "BEGIN {print $BUILD_END - $BUILD_START}")
    
    echo -e "${GREEN}✓ Tiempo de build: ${BUILD_TIME}s${NC}"
    
    # 2. Medir tiempo de START
    echo ""
    echo -e "${GREEN}[2/4] Iniciando contenedores...${NC}"
    START_START=$(date +%s.%N)
    
    if [ "$TOOL" = "docker" ]; then
        cd benchmark/docker
        $COMPOSE_CMD -f $COMPOSE_FILE up -d
        START_STATUS=$?
        cd ../..
    else
        cd benchmark/podman
        $COMPOSE_CMD -f $COMPOSE_FILE up -d
        START_STATUS=$?
        cd ../..
    fi
    
    if [ $START_STATUS -ne 0 ]; then
        echo -e "${RED}✗ Error al iniciar contenedores${NC}"
        continue
    fi
    
    START_END=$(date +%s.%N)
    START_TIME=$(awk "BEGIN {print $START_END - $START_START}")
    
    echo -e "${GREEN}✓ Tiempo de inicio: ${START_TIME}s${NC}"
    
    # Esperar 10 segundos para que los contenedores se estabilicen
    echo ""
    echo -e "${YELLOW}Esperando 10s para estabilización...${NC}"
    sleep 10
    
    # Verificar estado de contenedores
    check_container_status $APP_CONTAINER
    check_container_status $DB_CONTAINER
    
    # 3. Medir tiempo hasta HEALTH CHECK OK
    echo ""
    echo -e "${GREEN}[3/4] Esperando que la aplicación responda...${NC}"
    echo -e "${BLUE}Verificando: http://localhost:$PORT${NC}"
    HEALTH_START=$(date +%s.%N)
    
    MAX_WAIT=180  # Aumentado a 3 minutos
    ELAPSED=0
    SUCCESS=false
    
    while [ $ELAPSED -lt $MAX_WAIT ]; do
        # Intentar curl al puerto
        if curl -sf -m 5 "http://localhost:$PORT" > /dev/null 2>&1; then
            echo -e "\n${GREEN}✓ Aplicación respondiendo en puerto $PORT${NC}"
            SUCCESS=true
            break
        fi
        
        # Mostrar progreso cada 10 segundos
        if [ $((ELAPSED % 10)) -eq 0 ] && [ $ELAPSED -gt 0 ]; then
            echo -e "${YELLOW}⏳ Esperando... ${ELAPSED}s / ${MAX_WAIT}s${NC}"
            
            # Verificar si los contenedores siguen corriendo
            if ! $TOOL_CMD ps | grep -q $APP_CONTAINER; then
                echo -e "${RED}⚠️  El contenedor $APP_CONTAINER se detuvo${NC}"
                check_logs $APP_CONTAINER
                break
            fi
        fi
        
        sleep 2
        ELAPSED=$((ELAPSED + 2))
    done
    
    HEALTH_END=$(date +%s.%N)
    HEALTH_TIME=$(awk "BEGIN {print $HEALTH_END - $HEALTH_START}")
    
    if [ "$SUCCESS" = false ]; then
        echo -e "${RED}✗ Timeout esperando respuesta de la aplicación${NC}"
        echo ""
        echo -e "${YELLOW}Debug Info:${NC}"
        check_logs $APP_CONTAINER
        check_logs $DB_CONTAINER
        
        # Intentar ver el puerto
        echo -e "\n${BLUE}>>> Verificando puerto $PORT:${NC}"
        netstat -tuln | grep $PORT || echo "Puerto $PORT no está en uso"
        
        # Marcar como fallido
        HEALTH_TIME=999999
    else
        echo -e "${GREEN}✓ Tiempo de health check: ${HEALTH_TIME}s${NC}"
    fi
    
    # 4. Verificación final
    echo ""
    echo -e "${GREEN}[4/4] Verificación final...${NC}"
    
    # Tiempo total
    TOTAL_TIME=$(awk "BEGIN {print $BUILD_TIME + $START_TIME + $HEALTH_TIME}")
    
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
    if [ "$SUCCESS" = true ]; then
        echo -e "${GREEN}✓ TOTAL Lead Time: ${YELLOW}${TOTAL_TIME}s${NC}"
    else
        echo -e "${RED}✗ ITERACIÓN FALLIDA${NC}"
    fi
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
    echo -e "  Build:       ${BUILD_TIME}s"
    echo -e "  Start:       ${START_TIME}s"
    echo -e "  Health Check: ${HEALTH_TIME}s"
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
    
    # Guardar resultados en JSON
    echo "    {" >> "$RESULT_FILE"
    echo "      \"iteration\": $i," >> "$RESULT_FILE"
    echo "      \"build_time_seconds\": $BUILD_TIME," >> "$RESULT_FILE"
    echo "      \"start_time_seconds\": $START_TIME," >> "$RESULT_FILE"
    echo "      \"health_check_time_seconds\": $HEALTH_TIME," >> "$RESULT_FILE"
    echo "      \"total_lead_time_seconds\": $TOTAL_TIME," >> "$RESULT_FILE"
    echo "      \"success\": $([ "$SUCCESS" = true ] && echo 'true' || echo 'false')" >> "$RESULT_FILE"
    
    if [ "$i" -lt "$ITERATIONS" ]; then
        echo "    }," >> "$RESULT_FILE"
    else
        echo "    }" >> "$RESULT_FILE"
    fi
    
    # Preguntar si continuar si falló
    if [ "$SUCCESS" = false ]; then
        echo ""
        echo -e "${YELLOW}La iteración falló. ¿Deseas continuar? (s/n)${NC}"
        read -t 10 -n 1 CONTINUE || CONTINUE="s"
        echo ""
        if [ "$CONTINUE" != "s" ] && [ "$CONTINUE" != "S" ]; then
            echo -e "${RED}Benchmark interrumpido por el usuario${NC}"
            break
        fi
    fi
    
    # Limpiar después de cada iteración
    cleanup_benchmark
    
    sleep 5
done

echo "  ]" >> "$RESULT_FILE"
echo "}" >> "$RESULT_FILE"

echo ""
echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}✓ Benchmark completado${NC}"
echo -e "${BLUE}==========================================${NC}"
echo -e "Resultados guardados en: ${YELLOW}$RESULT_FILE${NC}"
echo ""
echo -e "${GREEN}✓ Tus otros contenedores NO fueron afectados${NC}"
echo ""
