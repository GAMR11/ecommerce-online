# 🐳🔷 Docker vs Podman - Análisis de Lead Time for Changes

Herramientas de benchmarking para comparar Docker y Podman usando la métrica DORA **Lead Time for Changes**.

## 📋 Tabla de Contenidos

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Uso Rápido](#uso-rápido)
- [Uso Detallado](#uso-detallado)
- [Interpretación de Resultados](#interpretación-de-resultados)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Troubleshooting](#troubleshooting)

## 🔧 Requisitos

### Software Necesario

- **Docker**: >= 20.10
- **Docker Compose**: >= 1.29
- **Podman**: >= 3.0
- **Podman Compose**: >= 1.0
- **Python**: >= 3.8
- **Bash**: >= 4.0
- **curl**: Para health checks
- **bc**: Para cálculos matemáticos

### Instalación de Dependencias (Ubuntu/Debian)

```bash
# Actualizar repositorios
sudo apt-get update

# Instalar Docker
sudo apt-get install -y docker.io docker-compose

# Instalar Podman
sudo apt-get install -y podman podman-compose

# Herramientas auxiliares
sudo apt-get install -y curl bc

# Añadir usuario al grupo docker (para evitar sudo)
sudo usermod -aG docker $USER
newgrp docker
```

### Verificar Instalaciones

```bash
docker --version
docker-compose --version
podman --version
podman-compose --version
python3 --version
```

## 🚀 Uso Rápido

### Opción 1: Benchmark Completo (Recomendado)

Ejecuta todo el proceso automáticamente (Docker → Podman → Análisis):

```bash
# Clonar tu repositorio
git clone https://github.com/GAMR11/ecommerce-online.git
cd ecommerce-online

# Copiar archivos de benchmark al proyecto
cp -r /ruta/a/benchmark ./

# Ejecutar benchmark completo (5 iteraciones por defecto)
./benchmark/scripts/run_full_benchmark.sh

# O con número personalizado de iteraciones
./benchmark/scripts/run_full_benchmark.sh 10
```

### Opción 2: Paso a Paso

```bash
# 1. Benchmark Docker (5 iteraciones)
./benchmark/scripts/measure_leadtime.sh docker 5

# 2. Benchmark Podman (5 iteraciones)
./benchmark/scripts/measure_leadtime.sh podman 5

# 3. Generar análisis comparativo
python3 benchmark/scripts/analyze_results.py
```

## 📊 Uso Detallado

### Script de Medición Individual

```bash
./benchmark/scripts/measure_leadtime.sh <herramienta> [iteraciones]

# Ejemplos:
./benchmark/scripts/measure_leadtime.sh docker 5    # Docker con 5 iteraciones
./benchmark/scripts/measure_leadtime.sh podman 10   # Podman con 10 iteraciones
```

**¿Qué mide?**

1. **Build Time**: Tiempo de construcción de imágenes (sin caché)
2. **Start Time**: Tiempo de inicio de contenedores
3. **Health Check Time**: Tiempo hasta que la app responde
4. **Total Lead Time**: Suma total del despliegue

**Salida**: Archivo JSON en `benchmark/results/`

### Script de Análisis

```bash
python3 benchmark/scripts/analyze_results.py
```

**¿Qué genera?**

1. Reporte comparativo en Markdown con:
   - Estadísticas descriptivas (media, mediana, desv. estándar)
   - Comparaciones por componente
   - Ganador y porcentaje de mejora
   - Gráficos ASCII
   - Conclusiones y recomendaciones

2. Archivo JSON con estadísticas procesadas

**Salidas**: 
- `benchmark/results/REPORTE_COMPARATIVO_YYYYMMDD_HHMMSS.md`
- `benchmark/results/statistics_YYYYMMDD_HHMMSS.json`

## 📈 Interpretación de Resultados

### Métrica DORA: Lead Time for Changes

**Definición**: Tiempo desde que se hace un cambio en el código hasta que está desplegado y funcionando en producción.

En este benchmark (entorno local):
```
Lead Time = Build Time + Start Time + Health Check Time
```

### Componentes Medidos

| Componente | Qué mide | Importancia |
|------------|----------|-------------|
| **Build Time** | Construcción de imágenes desde cero | Impacta en CI/CD y desarrollo |
| **Start Time** | Arranque de contenedores | Impacta en deployments y escalado |
| **Health Check** | Tiempo hasta app funcional | Impacta en disponibilidad |

### Criterios de Evaluación

1. **Lead Time Total**: Menor es mejor
2. **Consistencia**: Menor desviación estándar es mejor (más predecible)
3. **Componentes Individuales**: Identificar cuellos de botella

### Ejemplo de Reporte

```markdown
## 🏆 Lead Time for Changes TOTAL

| Métrica | Docker | Podman | Diferencia | Mejora % |
|---------|--------|--------|------------|----------|
| **Media Total** | 68.45s | 82.31s | 13.86s | 16.8% |

### Ganador General: 🐳 DOCKER

Docker es 16.8% más rápido que Podman
```

## 📁 Estructura del Proyecto

```
ecommerce-online/
├── benchmark/
│   ├── docker/
│   │   ├── Dockerfile              # Configuración Docker
│   │   ├── docker-compose.yml      # Orquestación Docker
│   │   └── nginx.conf              # Config Nginx
│   ├── podman/
│   │   ├── Containerfile           # Configuración Podman
│   │   ├── podman-compose.yml      # Orquestación Podman
│   │   └── nginx.conf              # Config Nginx
│   ├── scripts/
│   │   ├── measure_leadtime.sh     # Script de medición
│   │   ├── analyze_results.py      # Script de análisis
│   │   └── run_full_benchmark.sh   # Ejecución completa
│   └── results/                    # Resultados generados
│       ├── docker_results_*.json
│       ├── podman_results_*.json
│       ├── REPORTE_COMPARATIVO_*.md
│       └── statistics_*.json
├── public/                         # Tu app Laravel
├── app/
├── config/
└── ...
```

## 🔧 Troubleshooting

### Error: "Cannot connect to Docker daemon"

```bash
# Verificar que Docker esté corriendo
sudo systemctl status docker
sudo systemctl start docker

# Verificar permisos
sudo usermod -aG docker $USER
newgrp docker
```

### Error: "Podman compose not found"

```bash
# Instalar podman-compose
sudo apt-get install -y podman-compose

# O con pip
pip3 install podman-compose
```

### Error: "Port already in use"

```bash
# Ver qué está usando los puertos
sudo lsof -i :8080
sudo lsof -i :8081
sudo lsof -i :3306
sudo lsof -i :3307

# Matar procesos si es necesario
sudo kill -9 <PID>

# O cambiar puertos en docker-compose.yml y podman-compose.yml
```

### Error: "Health check timeout"

```bash
# Ver logs de contenedores
docker-compose -f benchmark/docker/docker-compose.yml logs
podman-compose -f benchmark/podman/podman-compose.yml logs

# Verificar que tu app Laravel tenga un endpoint público
# El health check usa: http://localhost/health
```

### Limpiar todo y empezar de nuevo

```bash
# Docker
docker-compose -f benchmark/docker/docker-compose.yml down -v
docker system prune -af --volumes

# Podman
podman-compose -f benchmark/podman/podman-compose.yml down
podman system prune -af --volumes

# Eliminar resultados anteriores
rm -rf benchmark/results/*
```

## 📝 Notas Adicionales

### Recomendaciones para Resultados Precisos

1. **Cerrar aplicaciones pesadas** antes del benchmark
2. **Ejecutar al menos 5 iteraciones** por herramienta
3. **No usar la máquina** durante el benchmark
4. **Usar el mismo hardware** para ambas mediciones
5. **Ejecutar en horarios similares** para condiciones comparables

### Personalización

#### Cambiar número de iteraciones

```bash
# Más iteraciones = resultados más precisos (pero toma más tiempo)
./benchmark/scripts/run_full_benchmark.sh 10
```

#### Modificar timeouts

Edita `measure_leadtime.sh` línea ~60:

```bash
MAX_WAIT=120  # Aumentar si tu app tarda en iniciar
```

#### Ajustar configuración de contenedores

Edita los archivos `docker-compose.yml` y `podman-compose.yml` para:
- Cambiar versiones de imágenes
- Ajustar recursos (límites de CPU/memoria)
- Modificar variables de entorno

## 📚 Recursos Adicionales

- [Guía completa en Markdown](../GUIA_ANALISIS_DORA_DOCKER_VS_PODMAN.md)
- [DORA Metrics](https://cloud.google.com/blog/products/devops-sre/using-the-four-keys-to-measure-your-devops-performance)
- [Docker Documentation](https://docs.docker.com/)
- [Podman Documentation](https://docs.podman.io/)

## 🤝 Soporte

Si encuentras problemas:

1. Revisa la sección [Troubleshooting](#troubleshooting)
2. Verifica los logs de contenedores
3. Asegúrate de que todas las dependencias estén instaladas

## 📄 Licencia

Este conjunto de scripts de benchmarking es de código abierto y puede ser usado libremente.

---

**¿Listo para empezar?** Ejecuta:

```bash
./benchmark/scripts/run_full_benchmark.sh
```

¡Y obtén tu análisis comparativo en minutos! 🚀
