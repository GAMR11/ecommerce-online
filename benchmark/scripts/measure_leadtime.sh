#!/bin/bash

# Script de medición de Lead Time for Changes
# Docker vs Podman - VERSION SEGURA (no afecta otros contenedores)
# Uso: ./measure_leadtime.sh <docker|podman> [iteraciones]

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
else
    COMPOSE_FILE="podman-compose-benchmark.yml"
    COMPOSE_CMD="podman-compose"
    APP_CONTAINER="laravel_app_podman_benchmark"
    DB_CONTAINER="mysql_db_podman_benchmark"
    PORT="8081"
    NETWORK_NAME="podman_laravel_network"
fi

# Función para limpiar SOLO los contenedores del benchmark
cleanup_benchmark() {
    echo -e "${YELLOW}Limpiando contenedores del benchmark...${NC}"
    
    if [ "$TOOL" = "docker" ]; then
        # Detener y eliminar SOLO los contenedores del benchmark
        docker stop $APP_CONTAINER 2>/dev/null || true
        docker stop $DB_CONTAINER 2>/dev/null || true
        docker rm $APP_CONTAINER 2>/dev/null || true
        docker rm $DB_CONTAINER 2>/dev/null || true
        
        # Eliminar red del benchmark si existe
        docker network rm $NETWORK_NAME 2>/dev/null || true
        
        # Eliminar imagen del benchmark (para forzar rebuild)
        docker rmi benchmark-docker-app 2>/dev/null || true
    else
        # Detener y eliminar SOLO los contenedores del benchmark
        podman stop $APP_CONTAINER 2>/dev/null || true
        podman stop $DB_CONTAINER 2>/dev/null || true
        podman rm $APP_CONTAINER 2>/dev/null || true
        podman rm $DB_CONTAINER 2>/dev/null || true
        
        # Eliminar red del benchmark si existe
        podman network rm $NETWORK_NAME 2>/dev/null || true
        
        # Eliminar imagen del benchmark (para forzar rebuild)
        podman rmi benchmark-podman-app 2>/dev/null || true
    fi
    
    sleep 2
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
    echo -e "${BLUE}--- Iteración $i/$ITERATIONS ---${NC}"
    
    # Limpiar antes de cada iteración
    cleanup_benchmark
    
    sleep 3
    
    # 1. Medir tiempo de BUILD
    echo -e "${GREEN}[1/3] Construyendo imágenes...${NC}"
    BUILD_START=$(date +%s.%N)
    
    if [ "$TOOL" = "docker" ]; then
        cd benchmark/docker
        $COMPOSE_CMD -f $COMPOSE_FILE build --no-cache 2>&1 | grep -E "(Building|built|Successfully|Step)" | tail -10 || true
        cd ../..
    else
        cd benchmark/podman  
        $COMPOSE_CMD -f $COMPOSE_FILE build --no-cache 2>&1 | grep -E "(Building|built|Successfully|Step)" | tail -10 || true
        cd ../..
    fi
    
    BUILD_END=$(date +%s.%N)
    BUILD_TIME=$(awk "BEGIN {print $BUILD_END - $BUILD_START}")
    
    echo -e "${GREEN}✓ Tiempo de build: ${BUILD_TIME}s${NC}"
    
    # 2. Medir tiempo de START
    echo -e "${GREEN}[2/3] Iniciando contenedores...${NC}"
    START_START=$(date +%s.%N)
    
    if [ "$TOOL" = "docker" ]; then
        cd benchmark/docker
        $COMPOSE_CMD -f $COMPOSE_FILE up -d 2>&1 | grep -E "(Creating|Created|Started|Starting)" || true
        cd ../..
    else
        cd benchmark/podman
        $COMPOSE_CMD -f $COMPOSE_FILE up -d 2>&1 | grep -E "(Creating|Created|Started|Starting)" || true
        cd ../..
    fi
    
    START_END=$(date +%s.%N)
    START_TIME=$(awk "BEGIN {print $START_END - $START_START}")
    
    echo -e "${GREEN}✓ Tiempo de inicio: ${START_TIME}s${NC}"
    
    # 3. Medir tiempo hasta HEALTH CHECK OK
    echo -e "${GREEN}[3/3] Esperando health check...${NC}"
    HEALTH_START=$(date +%s.%N)
    
    MAX_WAIT=120
    ELAPSED=0
    
    while [ $ELAPSED -lt $MAX_WAIT ]; do
        # Intentar curl al puerto
        if curl -sf "http://localhost:$PORT" > /dev/null 2>&1; then
            echo -e "\n${GREEN}✓ Aplicación respondiendo en puerto $PORT${NC}"
            break
        fi
        sleep 2
        ELAPSED=$((ELAPSED + 2))
        echo -ne "${YELLOW}  Esperando... ${ELAPSED}s${NC}\r"
    done
    
    HEALTH_END=$(date +%s.%N)
    HEALTH_TIME=$(awk "BEGIN {print $HEALTH_END - $HEALTH_START}")
    
    echo -e "${GREEN}✓ Tiempo de health check: ${HEALTH_TIME}s${NC}"
    
    # Tiempo total
    TOTAL_TIME=$(awk "BEGIN {print $BUILD_TIME + $START_TIME + $HEALTH_TIME}")
    
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
    echo -e "${GREEN}TOTAL Lead Time: ${YELLOW}${TOTAL_TIME}s${NC}"
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
    
    # Guardar resultados en JSON
    echo "    {" >> "$RESULT_FILE"
    echo "      \"iteration\": $i," >> "$RESULT_FILE"
    echo "      \"build_time_seconds\": $BUILD_TIME," >> "$RESULT_FILE"
    echo "      \"start_time_seconds\": $START_TIME," >> "$RESULT_FILE"
    echo "      \"health_check_time_seconds\": $HEALTH_TIME," >> "$RESULT_FILE"
    echo "      \"total_lead_time_seconds\": $TOTAL_TIME" >> "$RESULT_FILE"
    
    if [ "$i" -lt "$ITERATIONS" ]; then
        echo "    }," >> "$RESULT_FILE"
    else
        echo "    }" >> "$RESULT_FILE"
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
