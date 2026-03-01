#!/bin/bash

# Script de Diagnóstico Rápido
# Verifica el estado actual de los contenedores del benchmark

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   DIAGNÓSTICO RÁPIDO - BENCHMARK      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Verificar qué contenedores del benchmark están corriendo
echo -e "${YELLOW}1. Contenedores del Benchmark:${NC}"
echo ""
docker ps -a | grep "benchmark" || echo "  No hay contenedores del benchmark"
echo ""

# Verificar puertos
echo -e "${YELLOW}2. Puertos 8080 y 8081:${NC}"
echo ""
netstat -tuln 2>/dev/null | grep -E ":(8080|8081)" || echo "  Puertos 8080 y 8081 libres"
echo ""

# Si hay contenedores del benchmark, mostrar logs
echo -e "${YELLOW}3. Logs de Contenedores:${NC}"
echo ""

if docker ps -a | grep -q "laravel_app_docker_benchmark"; then
    echo -e "${BLUE}>>> Logs de laravel_app_docker_benchmark:${NC}"
    docker logs --tail 30 laravel_app_docker_benchmark 2>&1
    echo ""
fi

if docker ps -a | grep -q "mysql_db_docker_benchmark"; then
    echo -e "${BLUE}>>> Logs de mysql_db_docker_benchmark:${NC}"
    docker logs --tail 20 mysql_db_docker_benchmark 2>&1
    echo ""
fi

# Verificar si los contenedores están "healthy"
echo -e "${YELLOW}4. Estado de Salud:${NC}"
echo ""
docker ps -a --format "table {{.Names}}\t{{.Status}}" | grep benchmark || echo "  No hay contenedores"
echo ""

# Intentar conectar al puerto
echo -e "${YELLOW}5. Prueba de Conectividad:${NC}"
echo ""

if docker ps | grep -q "laravel_app_docker_benchmark"; then
    echo -e "${BLUE}Intentando conectar a http://localhost:8080...${NC}"
    if curl -sf -m 5 "http://localhost:8080" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Puerto 8080 responde correctamente${NC}"
    else
        echo -e "${RED}✗ Puerto 8080 no responde${NC}"
        
        # Verificar si nginx/apache están corriendo dentro del contenedor
        echo -e "\n${BLUE}Verificando procesos dentro del contenedor...${NC}"
        docker exec laravel_app_docker_benchmark ps aux 2>/dev/null | head -20 || echo "No se puede acceder al contenedor"
    fi
else
    echo -e "${YELLOW}El contenedor no está corriendo${NC}"
fi
echo ""

# Verificar archivo .env
echo -e "${YELLOW}6. Variables de Entorno:${NC}"
echo ""
if [ -f ".env" ]; then
    echo -e "${GREEN}✓ Archivo .env existe${NC}"
    echo -e "${BLUE}Variables DB:${NC}"
    grep -E "^DB_" .env 2>/dev/null || echo "  No se encontraron variables DB_*"
else
    echo -e "${RED}✗ Archivo .env no encontrado${NC}"
fi
echo ""

# Verificar Dockerfile
echo -e "${YELLOW}7. Dockerfile:${NC}"
echo ""
if [ -f "Dockerfile" ]; then
    echo -e "${GREEN}✓ Dockerfile existe${NC}"
    echo -e "${BLUE}Primeras líneas:${NC}"
    head -10 Dockerfile
else
    echo -e "${RED}✗ Dockerfile no encontrado${NC}"
fi
echo ""

# Recomendaciones
echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║          RECOMENDACIONES               ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

if docker ps -a | grep -q "laravel_app_docker_benchmark"; then
    if ! docker ps | grep -q "laravel_app_docker_benchmark"; then
        echo -e "${YELLOW}⚠️  El contenedor existe pero no está corriendo${NC}"
        echo -e "   Intenta: docker start laravel_app_docker_benchmark"
        echo ""
    fi
    
    if ! curl -sf -m 5 "http://localhost:8080" > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  El contenedor está corriendo pero no responde${NC}"
        echo -e "   Posibles causas:"
        echo -e "   1. La aplicación Laravel no se inició correctamente"
        echo -e "   2. Nginx/Apache no están configurados"
        echo -e "   3. Falta el archivo .env o tiene errores"
        echo -e "   4. Problemas con permisos de archivos"
        echo ""
        echo -e "   Revisa los logs: docker logs laravel_app_docker_benchmark"
        echo ""
    fi
else
    echo -e "${GREEN}✓ No hay contenedores del benchmark (estado limpio)${NC}"
    echo -e "   Puedes ejecutar el benchmark normalmente"
    echo ""
fi

echo -e "${BLUE}════════════════════════════════════════${NC}"
