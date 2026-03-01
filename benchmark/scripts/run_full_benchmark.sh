#!/bin/bash

# Script de ejecución completa del benchmark
# Ejecuta Docker, luego Podman, y finalmente genera el análisis

set -e

ITERATIONS=${1:-5}

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   BENCHMARK COMPLETO: DOCKER VS PODMAN                     ║"
echo "║   Métrica DORA: Lead Time for Changes                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "Iteraciones por herramienta: $ITERATIONS"
echo ""

# Verificar que las herramientas estén instaladas
echo "🔍 Verificando instalaciones..."

if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado"
    exit 1
fi

if ! command -v podman &> /dev/null; then
    echo "❌ Podman no está instalado"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose no está instalado"
    exit 1
fi

if ! command -v podman-compose &> /dev/null; then
    echo "❌ podman-compose no está instalado"
    exit 1
fi

echo "✅ Todas las herramientas están instaladas"
echo ""

# Ejecutar benchmark de Docker
echo "═══════════════════════════════════════════════════════════"
echo "FASE 1: Benchmark Docker"
echo "═══════════════════════════════════════════════════════════"
./benchmark/scripts/measure_leadtime.sh docker "$ITERATIONS"

echo ""
echo "⏳ Esperando 10 segundos antes de Podman..."
sleep 10

# Ejecutar benchmark de Podman
echo "═══════════════════════════════════════════════════════════"
echo "FASE 2: Benchmark Podman"
echo "═══════════════════════════════════════════════════════════"
./benchmark/scripts/measure_leadtime.sh podman "$ITERATIONS"

echo ""
echo "⏳ Esperando 5 segundos antes del análisis..."
sleep 5

# Generar análisis comparativo
echo "═══════════════════════════════════════════════════════════"
echo "FASE 3: Análisis Comparativo"
echo "═══════════════════════════════════════════════════════════"
python3 benchmark/scripts/analyze_results.py

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              BENCHMARK COMPLETADO CON ÉXITO                ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📁 Revisa los resultados en: benchmark/results/"
echo ""
