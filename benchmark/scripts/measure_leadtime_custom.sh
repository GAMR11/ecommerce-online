#!/bin/bash

# Script de medición de Lead Time for Changes - VERSIÓN ADAPTADA
# Usa el docker-compose.yml del usuario, sin tocar otros contenedores
# Uso: ./measure_leadtime_custom.sh <docker|podman> [iteraciones]

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

# Detectar docker-compose.yml del usuario
if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}Error: No se encontró docker-compose.yml en el directorio actual${NC}"
    echo "Ejecuta este script desde el directorio raíz de tu proyecto Laravel"
    exit 1
fi

echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}Midiendo Lead Time for Changes con $TOOL${NC}"
echo -e "${BLUE}==========================================${NC}"
echo -e "Iteraciones: ${YELLOW}$ITERATIONS${NC}"
echo -e "Archivo: ${YELLOW}docker-compose.yml${NC}"
echo ""

# Crear directorio de resultados
mkdir -p "$RESULTS_DIR"

# Obtener nombres de servicios del docker-compose.yml
SERVICES=$(grep -E "^  [a-zA-Z]" docker-compose.yml | sed 's/://g' | tr -d ' ' | tr '\n' ' ')
echo -e "${BLUE}Servicios detectados:${NC} ${GREEN}$SERVICES${NC}"
echo ""

# Preguntar si quiere limpiar antes de cada iteración
echo -e "${YELLOW}IMPORTANTE:${NC} ¿Quieres detener y eliminar los contenedores del proyecto antes de cada iteración?"
echo -e "  ${GREEN}s${NC} = Sí, medir desde cero (más preciso, pero elimina contenedores)"
echo -e "  ${RED}n${NC} = No, mantener contenedores entre iteraciones (más rápido, menos preciso)"
read -p "Tu elección [s/n]: " -n 1 -r
echo
CLEAN_BETWEEN_ITERATIONS=$REPLY

# Archivo de resultados
RESULT_FILE="$RESULTS_DIR/${TOOL}_results_$(date +%Y%m%d_%H%M%S).json"

echo "{" > "$RESULT_FILE"
echo "  \"tool\": \"$TOOL\"," >> "$RESULT_FILE"
echo "  \"timestamp\": \"$(date -Iseconds)\"," >> "$RESULT_FILE"
echo "  \"compose_file\": \"docker-compose.yml\"," >> "$RESULT_FILE"
echo "  \"services\": \"$SERVICES\"," >> "$RESULT_FILE"
echo "  \"clean_between_iterations\": \"$CLEAN_BETWEEN_ITERATIONS\"," >> "$RESULT_FILE"
echo "  \"iterations\": [" >> "$RESULT_FILE"

for i in $(seq 1 "$ITERATIONS"); do
    echo ""
    echo -e "${BLUE}--- Iteración $i/$ITERATIONS ---${NC}"
    
    # Limpiar si el usuario lo solicitó
    if [[ $CLEAN_BETWEEN_ITERATIONS =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}Deteniendo contenedores del proyecto...${NC}"
        if [ "$TOOL" = "docker" ]; then
            docker-compose down 2>/dev/null || true
        else
            podman-compose down 2>/dev/null || true
        fi
        sleep 3
    fi
    
    # 1. Medir tiempo de BUILD
    echo -e "${GREEN}[1/3] Construyendo imágenes...${NC}"
    BUILD_START=$(date +%s.%N)
    
    if [ "$TOOL" = "docker" ]; then
        docker-compose build --no-cache 2>&1 | tail -5
    else
        podman-compose build --no-cache 2>&1 | tail -5
    fi
    
    BUILD_END=$(date +%s.%N)
    BUILD_TIME=$(awk "BEGIN {print $BUILD_END - $BUILD_START}")
    
    echo -e "${GREEN}✓ Tiempo de build: ${BUILD_TIME}s${NC}"
    
    # 2. Medir tiempo de START
    echo -e "${GREEN}[2/3] Iniciando contenedores...${NC}"
    START_START=$(date +%s.%N)
    
    if [ "$TOOL" = "docker" ]; then
        docker-compose up -d 2>&1 | tail -5
    else
        podman-compose up -d 2>&1 | tail -5
    fi
    
    START_END=$(date +%s.%N)
    START_TIME=$(awk "BEGIN {print $START_END - $START_START}")
    
    echo -e "${GREEN}✓ Tiempo de inicio: ${START_TIME}s${NC}"
    
    # 3. Medir tiempo hasta HEALTH CHECK OK
    echo -e "${GREEN}[3/3] Esperando que la aplicación responda...${NC}"
    HEALTH_START=$(date +%s.%N)
    
    # Detectar puerto de la aplicación Laravel del docker-compose.yml
    PORT=$(grep -A 5 "app:" docker-compose.yml | grep "ports:" -A 1 | grep -oE "[0-9]+:[0-9]+" | cut -d':' -f1 | head -1)
    
    if [ -z "$PORT" ]; then
        PORT=8000  # Puerto por defecto si no se detecta
    fi
    
    echo -e "   Probando http://localhost:${PORT}"
    
    MAX_WAIT=120
    ELAPSED=0
    
    while [ $ELAPSED -lt $MAX_WAIT ]; do
        # Intentar varios endpoints comunes de Laravel
        if curl -sf "http://localhost:$PORT" > /dev/null 2>&1 || \
           curl -sf "http://localhost:$PORT/health" > /dev/null 2>&1 || \
           curl -sf "http://localhost:$PORT/api" > /dev/null 2>&1; then
            break
        fi
        sleep 2
        ELAPSED=$((ELAPSED + 2))
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
    
    # Si no limpia entre iteraciones, esperar un poco menos
    if [[ ! $CLEAN_BETWEEN_ITERATIONS =~ ^[Ss]$ ]]; then
        sleep 2
    else
        # Limpiar después de cada iteración
        if [ "$TOOL" = "docker" ]; then
            docker-compose down 2>/dev/null || true
        else
            podman-compose down 2>/dev/null || true
        fi
        sleep 5
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

# Mostrar estado final de contenedores
echo -e "${BLUE}Estado de contenedores del proyecto:${NC}"
if [ "$TOOL" = "docker" ]; then
    docker-compose ps
else
    podman-compose ps
fi
