#!/bin/bash

# Script de medición de Lead Time for Changes
# ADAPTADO PARA TU PROYECTO EXISTENTE
# Docker vs Podman - SOLO tus contenedores específicos

set -e

TOOL=$1
ITERATIONS=${2:-5}
PROJECT_NAME="ecommerce-online"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

if [ "$TOOL" != "docker" ] && [ "$TOOL" != "podman" ]; then
    echo -e "${RED}Error: Herramienta no válida${NC}"
    echo "Uso: $0 <docker|podman> [iteraciones]"
    exit 1
fi

echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}Midiendo Lead Time for Changes con $TOOL${NC}"
echo -e "${BLUE}==========================================${NC}"
echo -e "Proyecto: ${YELLOW}$PROJECT_NAME${NC}"
echo -e "Iteraciones: ${YELLOW}$ITERATIONS${NC}"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}Error: No se encuentra docker-compose.yml${NC}"
    echo "Ejecuta este script desde el directorio raíz del proyecto"
    exit 1
fi

# Crear directorio de resultados
RESULTS_DIR="benchmark/results"
mkdir -p "$RESULTS_DIR"

# Determinar qué archivo compose usar y nombres de contenedores
if [ "$TOOL" = "docker" ]; then
    COMPOSE_CMD="docker-compose"
    COMPOSE_FILE="docker-compose.yml"
    CONTAINER_PREFIX="${PROJECT_NAME}"
    PORT="3306"  # Tu puerto actual de la app
else
    COMPOSE_CMD="podman-compose"
    COMPOSE_FILE="podman-compose.yml"
    CONTAINER_PREFIX="${PROJECT_NAME}-podman"
    PORT="3307"  # Puerto alternativo para Podman
fi

echo -e "${YELLOW}Configuración:${NC}"
echo "  - Comando: $COMPOSE_CMD"
echo "  - Archivo: $COMPOSE_FILE"
echo "  - Puerto App: $PORT"
echo ""

# Verificar que el archivo compose existe
if [ "$TOOL" = "podman" ] && [ ! -f "$COMPOSE_FILE" ]; then
    echo -e "${RED}Error: No existe $COMPOSE_FILE${NC}"
    echo "Necesitas crear un podman-compose.yml basado en tu docker-compose.yml"
    echo "O ejecuta: cp docker-compose.yml podman-compose.yml"
    exit 1
fi

# Archivo de resultados
RESULT_FILE="$RESULTS_DIR/${TOOL}_results_$(date +%Y%m%d_%H%M%S).json"

echo "{" > "$RESULT_FILE"
echo "  \"tool\": \"$TOOL\"," >> "$RESULT_FILE"
echo "  \"timestamp\": \"$(date -Iseconds)\"," >> "$RESULT_FILE"
echo "  \"project\": \"$PROJECT_NAME\"," >> "$RESULT_FILE"
echo "  \"iterations\": [" >> "$RESULT_FILE"

for i in $(seq 1 "$ITERATIONS"); do
    echo ""
    echo -e "${BLUE}--- Iteración $i/$ITERATIONS ---${NC}"
    
    # IMPORTANTE: Solo detener NUESTROS contenedores específicos
    echo -e "${YELLOW}Deteniendo contenedores del proyecto...${NC}"
    $COMPOSE_CMD -f "$COMPOSE_FILE" -p "$PROJECT_NAME" down 2>/dev/null || true
    
    sleep 3
    
    # 1. Medir tiempo de BUILD
    echo -e "${GREEN}[1/3] Construyendo imágenes...${NC}"
    BUILD_START=$(date +%s.%N)
    
    $COMPOSE_CMD -f "$COMPOSE_FILE" -p "$PROJECT_NAME" build --no-cache 2>&1 | grep -v "WARNING" || true
    
    BUILD_END=$(date +%s.%N)
    BUILD_TIME=$(awk "BEGIN {print $BUILD_END - $BUILD_START}")
    
    echo -e "${GREEN}✓ Tiempo de build: ${BUILD_TIME}s${NC}"
    
    # 2. Medir tiempo de START
    echo -e "${GREEN}[2/3] Iniciando contenedores...${NC}"
    START_START=$(date +%s.%N)
    
    $COMPOSE_CMD -f "$COMPOSE_FILE" -p "$PROJECT_NAME" up -d 2>&1 | grep -v "WARNING" || true
    
    START_END=$(date +%s.%N)
    START_TIME=$(awk "BEGIN {print $START_END - $START_START}")
    
    echo -e "${GREEN}✓ Tiempo de inicio: ${START_TIME}s${NC}"
    
    # 3. Medir tiempo hasta HEALTH CHECK OK
    echo -e "${GREEN}[3/3] Esperando que la app responda...${NC}"
    HEALTH_START=$(date +%s.%N)
    
    # Intentar varios endpoints
    MAX_WAIT=120
    ELAPSED=0
    HEALTH_OK=false
    
    while [ $ELAPSED -lt $MAX_WAIT ]; do
        # Intentar conectar al puerto de la app
        if curl -sf "http://localhost:$PORT" > /dev/null 2>&1; then
            HEALTH_OK=true
            break
        fi
        
        # También verificar si los contenedores están corriendo
        if [ "$TOOL" = "docker" ]; then
            if docker ps | grep -q "laravel_app"; then
                CONTAINER_RUNNING=true
            fi
        else
            if podman ps | grep -q "laravel_app"; then
                CONTAINER_RUNNING=true
            fi
        fi
        
        sleep 2
        ELAPSED=$((ELAPSED + 2))
    done
    
    HEALTH_END=$(date +%s.%N)
    HEALTH_TIME=$(awk "BEGIN {print $HEALTH_END - $HEALTH_START}")
    
    if [ "$HEALTH_OK" = true ]; then
        echo -e "${GREEN}✓ Aplicación respondiendo en puerto $PORT${NC}"
    else
        echo -e "${YELLOW}⚠ Timeout esperando respuesta (contenedores iniciados pero app puede estar inicializando)${NC}"
    fi
    
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
    echo "      \"total_lead_time_seconds\": $TOTAL_TIME," >> "$RESULT_FILE"
    echo "      \"health_check_successful\": $([ \"$HEALTH_OK\" = true ] && echo \"true\" || echo \"false\")" >> "$RESULT_FILE"
    
    if [ "$i" -lt "$ITERATIONS" ]; then
        echo "    }," >> "$RESULT_FILE"
    else
        echo "    }" >> "$RESULT_FILE"
    fi
    
    # Dejar los contenedores corriendo después de la última iteración
    if [ "$i" -lt "$ITERATIONS" ]; then
        echo -e "${YELLOW}Deteniendo contenedores para próxima iteración...${NC}"
        $COMPOSE_CMD -f "$COMPOSE_FILE" -p "$PROJECT_NAME" down 2>/dev/null || true
        sleep 5
    else
        echo -e "${GREEN}Dejando contenedores corriendo (última iteración)${NC}"
    fi
done

echo "  ]" >> "$RESULT_FILE"
echo "}" >> "$RESULT_FILE"

echo ""
echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}✓ Benchmark completado${NC}"
echo -e "${BLUE}==========================================${NC}"
echo -e "Resultados guardados en: ${YELLOW}$RESULT_FILE${NC}"
echo ""
echo -e "${GREEN}Tus contenedores siguen corriendo.${NC}"
echo -e "Para verlos: ${YELLOW}$COMPOSE_CMD -f $COMPOSE_FILE ps${NC}"
echo ""
