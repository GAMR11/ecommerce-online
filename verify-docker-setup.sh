#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 Verificando configuración Docker...${NC}\n"

# 1. Verificar estructura del proyecto
echo -e "${YELLOW}1. Verificando estructura del proyecto...${NC}"
if [ -f "docker-compose.yml" ]; then
    echo -e "${GREEN}✓ docker-compose.yml encontrado${NC}"
else
    echo -e "${RED}✗ docker-compose.yml NO encontrado${NC}"
fi

if [ -f "docker/php/Dockerfile" ]; then
    echo -e "${GREEN}✓ docker/php/Dockerfile encontrado${NC}"
else
    echo -e "${RED}✗ docker/php/Dockerfile NO encontrado${NC}"
fi

if [ -f "docker/nginx/default.conf" ]; then
    echo -e "${GREEN}✓ docker/nginx/default.conf encontrado${NC}"
else
    echo -e "${RED}✗ docker/nginx/default.conf NO encontrado${NC}"
fi

if [ -f "Jenkinsfile" ]; then
    echo -e "${GREEN}✓ Jenkinsfile encontrado${NC}"
else
    echo -e "${RED}✗ Jenkinsfile NO encontrado${NC}"
fi

echo ""

# 2. Validar sintaxis docker-compose
echo -e "${YELLOW}2. Validando sintaxis docker-compose.yml...${NC}"
if docker compose config > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Sintaxis válida${NC}"
else
    echo -e "${RED}✗ Sintaxis INVÁLIDA${NC}"
    docker compose config
fi

echo ""

# 3. Verificar permisos de archivos
echo -e "${YELLOW}3. Verificando permisos de archivos...${NC}"
if [ -r "docker/nginx/default.conf" ]; then
    echo -e "${GREEN}✓ docker/nginx/default.conf es legible${NC}"
else
    echo -e "${RED}✗ docker/nginx/default.conf NO es legible${NC}"
    echo "  Ejecuta: chmod 644 docker/nginx/default.conf"
fi

echo ""

# 4. Verificar Docker está corriendo
echo -e "${YELLOW}4. Verificando Docker...${NC}"
if docker ps > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Docker está activo${NC}"
else
    echo -e "${RED}✗ Docker NO está activo${NC}"
fi

echo ""

# 5. Verificar contenedores existentes
echo -e "${YELLOW}5. Estado actual de contenedores...${NC}"
docker compose ps || echo "No hay contenedores en ejecución"

echo ""

# 6. Verificar imágenes disponibles
echo -e "${YELLOW}6. Imágenes requeridas disponibles...${NC}"
docker images | grep -E "php|nginx|mysql|node" || echo "Algunas imágenes aún no están descargadas"

echo ""

# 7. Análisis de volúmenes
echo -e "${YELLOW}7. Volúmenes configurados...${NC}"
docker volume ls 2>/dev/null | grep -E "mysql|ecommerce" || echo "No hay volúmenes específicos aún"

echo ""

# 8. Recomendaciones
echo -e "${BLUE}📋 Próximos pasos recomendados:${NC}"
echo "1. Ejecutar: docker compose up -d --build"
echo "2. Esperar 10-15 segundos para que los servicios inicien"
echo "3. Verificar logs: docker compose logs -f"
echo "4. Verificar conexión a app: docker compose exec app php artisan --version"
echo "5. Verificar conexión a MySQL: docker compose exec mysql mysql -u root -proot -e 'SELECT 1'"

echo ""
echo -e "${GREEN}✅ Verificación completada${NC}"
