#!/bin/bash

# Script de verificación de requisitos
# Verifica e instala dependencias necesarias para el benchmark

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Verificación de Requisitos${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Función para verificar comando
check_command() {
    if command -v "$1" &> /dev/null; then
        echo -e "✅ $1: ${GREEN}Instalado${NC} ($(command -v $1))"
        return 0
    else
        echo -e "❌ $1: ${RED}No encontrado${NC}"
        return 1
    fi
}

# Función para instalar bc si falta
install_bc() {
    echo ""
    echo -e "${YELLOW}Instalando 'bc'...${NC}"
    
    if [ -f /etc/debian_version ]; then
        sudo apt-get update && sudo apt-get install -y bc
    elif [ -f /etc/redhat-release ]; then
        sudo yum install -y bc
    elif [ -f /etc/arch-release ]; then
        sudo pacman -S bc
    else
        echo -e "${RED}No se pudo detectar el gestor de paquetes${NC}"
        echo "Instala 'bc' manualmente: sudo apt-get install bc"
        return 1
    fi
}

ALL_OK=true

# Verificar Docker
echo -e "${BLUE}1. Docker${NC}"
if ! check_command docker; then
    ALL_OK=false
    echo -e "   ${YELLOW}Instalar:${NC} sudo apt-get install docker.io"
fi

if ! check_command docker-compose; then
    ALL_OK=false
    echo -e "   ${YELLOW}Instalar:${NC} sudo apt-get install docker-compose"
fi

echo ""

# Verificar Podman
echo -e "${BLUE}2. Podman${NC}"
if ! check_command podman; then
    ALL_OK=false
    echo -e "   ${YELLOW}Instalar:${NC} sudo apt-get install podman"
fi

if ! check_command podman-compose; then
    ALL_OK=false
    echo -e "   ${YELLOW}Instalar:${NC} sudo apt-get install podman-compose"
    echo -e "   ${YELLOW}O con pip:${NC} pip3 install podman-compose"
fi

echo ""

# Verificar Python
echo -e "${BLUE}3. Python${NC}"
if ! check_command python3; then
    ALL_OK=false
    echo -e "   ${YELLOW}Instalar:${NC} sudo apt-get install python3"
else
    PYTHON_VERSION=$(python3 --version 2>&1 | awk '{print $2}')
    echo -e "   Versión: ${GREEN}$PYTHON_VERSION${NC}"
fi

echo ""

# Verificar herramientas auxiliares
echo -e "${BLUE}4. Herramientas Auxiliares${NC}"

if ! check_command curl; then
    ALL_OK=false
    echo -e "   ${YELLOW}Instalar:${NC} sudo apt-get install curl"
fi

if ! check_command awk; then
    echo -e "⚠️  awk: ${YELLOW}No encontrado${NC} (requerido para cálculos)"
    ALL_OK=false
fi

# bc es opcional ahora que usamos awk, pero lo verificamos
if ! check_command bc; then
    echo -e "⚠️  bc: ${YELLOW}No encontrado (opcional, pero recomendado)${NC}"
    read -p "¿Deseas instalar 'bc' ahora? (s/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        install_bc
    fi
fi

echo ""

# Verificar permisos de Docker
echo -e "${BLUE}5. Permisos Docker${NC}"
if groups | grep -q docker; then
    echo -e "✅ Usuario en grupo docker: ${GREEN}Sí${NC}"
else
    echo -e "⚠️  Usuario en grupo docker: ${YELLOW}No${NC}"
    echo -e "   ${YELLOW}Agregar:${NC} sudo usermod -aG docker \$USER && newgrp docker"
    ALL_OK=false
fi

echo ""

# Verificar servicios
echo -e "${BLUE}6. Servicios${NC}"

# Docker daemon
if sudo systemctl is-active --quiet docker 2>/dev/null; then
    echo -e "✅ Docker daemon: ${GREEN}Activo${NC}"
elif docker info &>/dev/null; then
    echo -e "✅ Docker daemon: ${GREEN}Activo${NC}"
else
    echo -e "❌ Docker daemon: ${RED}No activo${NC}"
    echo -e "   ${YELLOW}Iniciar:${NC} sudo systemctl start docker"
    ALL_OK=false
fi

echo ""

# Verificar espacio en disco
echo -e "${BLUE}7. Espacio en Disco${NC}"
AVAILABLE_GB=$(df -BG . | tail -1 | awk '{print $4}' | sed 's/G//')
if [ "$AVAILABLE_GB" -lt 10 ]; then
    echo -e "⚠️  Espacio disponible: ${YELLOW}${AVAILABLE_GB}GB${NC} (recomendado: 10GB+)"
else
    echo -e "✅ Espacio disponible: ${GREEN}${AVAILABLE_GB}GB${NC}"
fi

echo ""

# Resumen
echo -e "${BLUE}========================================${NC}"
if [ "$ALL_OK" = true ]; then
    echo -e "${GREEN}✅ TODOS LOS REQUISITOS CUMPLIDOS${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "Puedes ejecutar el benchmark:"
    echo -e "${YELLOW}./benchmark/scripts/run_full_benchmark.sh${NC}"
    exit 0
else
    echo -e "${RED}❌ FALTAN ALGUNOS REQUISITOS${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "Instala las herramientas faltantes y vuelve a ejecutar:"
    echo -e "${YELLOW}./benchmark/scripts/check_requirements.sh${NC}"
    exit 1
fi
