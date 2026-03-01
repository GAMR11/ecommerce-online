#!/usr/bin/env python3

"""
Script de Análisis de Métricas DORA
Compara Docker vs Podman en Lead Time for Changes
"""

import json
import statistics
import sys
from pathlib import Path
from datetime import datetime

def load_results(tool):
    """Carga los resultados más recientes de una herramienta"""
    results_dir = Path("benchmark/results")
    
    if not results_dir.exists():
        print(f"Error: Directorio {results_dir} no existe")
        return None
    
    files = list(results_dir.glob(f"{tool}_results_*.json"))
    
    if not files:
        print(f"❌ No se encontraron resultados para {tool}")
        print(f"   Ejecuta primero: ./benchmark/scripts/measure_leadtime.sh {tool}")
        return None
    
    latest_file = max(files, key=lambda x: x.stat().st_mtime)
    print(f"📂 Cargando {tool}: {latest_file.name}")
    
    with open(latest_file, 'r') as f:
        return json.load(f)

def calculate_stats(data):
    """Calcula estadísticas de los resultados"""
    if not data or 'iterations' not in data:
        return None
    
    iterations = data['iterations']
    
    build_times = [it['build_time_seconds'] for it in iterations]
    start_times = [it['start_time_seconds'] for it in iterations]
    health_times = [it['health_check_time_seconds'] for it in iterations]
    total_times = [it['total_lead_time_seconds'] for it in iterations]
    
    return {
        'tool': data['tool'],
        'timestamp': data['timestamp'],
        'num_iterations': len(iterations),
        'build_time': {
            'mean': statistics.mean(build_times),
            'median': statistics.median(build_times),
            'stdev': statistics.stdev(build_times) if len(build_times) > 1 else 0,
            'min': min(build_times),
            'max': max(build_times)
        },
        'start_time': {
            'mean': statistics.mean(start_times),
            'median': statistics.median(start_times),
            'stdev': statistics.stdev(start_times) if len(start_times) > 1 else 0,
            'min': min(start_times),
            'max': max(start_times)
        },
        'health_check_time': {
            'mean': statistics.mean(health_times),
            'median': statistics.median(health_times),
            'stdev': statistics.stdev(health_times) if len(health_times) > 1 else 0,
            'min': min(health_times),
            'max': max(health_times)
        },
        'total_lead_time': {
            'mean': statistics.mean(total_times),
            'median': statistics.median(total_times),
            'stdev': statistics.stdev(total_times) if len(total_times) > 1 else 0,
            'min': min(total_times),
            'max': max(total_times)
        }
    }

def generate_comparison_report(docker_stats, podman_stats):
    """Genera un reporte comparativo en Markdown"""
    
    # Determinar ganador
    docker_faster = docker_stats['total_lead_time']['mean'] < podman_stats['total_lead_time']['mean']
    winner = "🐳 DOCKER" if docker_faster else "🔷 PODMAN"
    
    diff = abs(docker_stats['total_lead_time']['mean'] - podman_stats['total_lead_time']['mean'])
    improvement_pct = (diff / max(docker_stats['total_lead_time']['mean'], 
                                   podman_stats['total_lead_time']['mean'])) * 100
    
    report = f"""# 📊 Análisis Comparativo: Lead Time for Changes
## Docker vs Podman - Métrica DORA

**Fecha del análisis**: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}

---

## 🎯 Resumen Ejecutivo

### Ganador: {winner}

| Métrica | Docker | Podman | Diferencia |
|---------|--------|--------|------------|
| **Lead Time Promedio** | **{docker_stats['total_lead_time']['mean']:.2f}s** | **{podman_stats['total_lead_time']['mean']:.2f}s** | **{diff:.2f}s ({improvement_pct:.1f}%)** |

{'**Docker es ' + f'{improvement_pct:.1f}% más rápido** que Podman' if docker_faster else '**Podman es ' + f'{improvement_pct:.1f}% más rápido** que Docker'}

---

## 📋 Información General

### Docker
- **Iteraciones ejecutadas**: {docker_stats['num_iterations']}
- **Fecha de medición**: {docker_stats['timestamp']}

### Podman
- **Iteraciones ejecutadas**: {podman_stats['num_iterations']}
- **Fecha de medición**: {podman_stats['timestamp']}

---

## ⏱️ Análisis Detallado por Componente

### 1️⃣ Build Time (Construcción de Imágenes)

| Métrica | Docker | Podman | Ganador |
|---------|--------|--------|---------|
| **Media** | {docker_stats['build_time']['mean']:.2f}s | {podman_stats['build_time']['mean']:.2f}s | {'🐳 Docker' if docker_stats['build_time']['mean'] < podman_stats['build_time']['mean'] else '🔷 Podman'} |
| **Mediana** | {docker_stats['build_time']['median']:.2f}s | {podman_stats['build_time']['median']:.2f}s | {'🐳 Docker' if docker_stats['build_time']['median'] < podman_stats['build_time']['median'] else '🔷 Podman'} |
| **Desv. Estándar** | {docker_stats['build_time']['stdev']:.2f}s | {podman_stats['build_time']['stdev']:.2f}s | {'🐳 Docker (más consistente)' if docker_stats['build_time']['stdev'] < podman_stats['build_time']['stdev'] else '🔷 Podman (más consistente)'} |
| **Mínimo** | {docker_stats['build_time']['min']:.2f}s | {podman_stats['build_time']['min']:.2f}s | - |
| **Máximo** | {docker_stats['build_time']['max']:.2f}s | {podman_stats['build_time']['max']:.2f}s | - |

**Diferencia en Build**: {abs(docker_stats['build_time']['mean'] - podman_stats['build_time']['mean']):.2f}s ({abs((docker_stats['build_time']['mean'] - podman_stats['build_time']['mean']) / max(docker_stats['build_time']['mean'], podman_stats['build_time']['mean']) * 100):.1f}%)

---

### 2️⃣ Container Start Time (Inicio de Contenedores)

| Métrica | Docker | Podman | Ganador |
|---------|--------|--------|---------|
| **Media** | {docker_stats['start_time']['mean']:.2f}s | {podman_stats['start_time']['mean']:.2f}s | {'🐳 Docker' if docker_stats['start_time']['mean'] < podman_stats['start_time']['mean'] else '🔷 Podman'} |
| **Mediana** | {docker_stats['start_time']['median']:.2f}s | {podman_stats['start_time']['median']:.2f}s | {'🐳 Docker' if docker_stats['start_time']['median'] < podman_stats['start_time']['median'] else '🔷 Podman'} |
| **Desv. Estándar** | {docker_stats['start_time']['stdev']:.2f}s | {podman_stats['start_time']['stdev']:.2f}s | {'🐳 Docker (más consistente)' if docker_stats['start_time']['stdev'] < podman_stats['start_time']['stdev'] else '🔷 Podman (más consistente)'} |

**Diferencia en Start**: {abs(docker_stats['start_time']['mean'] - podman_stats['start_time']['mean']):.2f}s ({abs((docker_stats['start_time']['mean'] - podman_stats['start_time']['mean']) / max(docker_stats['start_time']['mean'], podman_stats['start_time']['mean']) * 100):.1f}%)

---

### 3️⃣ Health Check Time (Tiempo hasta App Lista)

| Métrica | Docker | Podman | Ganador |
|---------|--------|--------|---------|
| **Media** | {docker_stats['health_check_time']['mean']:.2f}s | {podman_stats['health_check_time']['mean']:.2f}s | {'🐳 Docker' if docker_stats['health_check_time']['mean'] < podman_stats['health_check_time']['mean'] else '🔷 Podman'} |
| **Mediana** | {docker_stats['health_check_time']['median']:.2f}s | {podman_stats['health_check_time']['median']:.2f}s | {'🐳 Docker' if docker_stats['health_check_time']['median'] < podman_stats['health_check_time']['median'] else '🔷 Podman'} |
| **Desv. Estándar** | {docker_stats['health_check_time']['stdev']:.2f}s | {podman_stats['health_check_time']['stdev']:.2f}s | {'🐳 Docker (más consistente)' if docker_stats['health_check_time']['stdev'] < podman_stats['health_check_time']['stdev'] else '🔷 Podman (más consistente)'} |

**Diferencia en Health Check**: {abs(docker_stats['health_check_time']['mean'] - podman_stats['health_check_time']['mean']):.2f}s

---

## 🏆 Lead Time for Changes - Resultado Final

| Métrica | Docker | Podman | Diferencia | Mejora |
|---------|--------|--------|------------|--------|
| **Media Total** | **{docker_stats['total_lead_time']['mean']:.2f}s** | **{podman_stats['total_lead_time']['mean']:.2f}s** | **{diff:.2f}s** | **{improvement_pct:.1f}%** |
| **Mediana Total** | {docker_stats['total_lead_time']['median']:.2f}s | {podman_stats['total_lead_time']['median']:.2f}s | {abs(docker_stats['total_lead_time']['median'] - podman_stats['total_lead_time']['median']):.2f}s | {abs((docker_stats['total_lead_time']['median'] - podman_stats['total_lead_time']['median']) / max(docker_stats['total_lead_time']['median'], podman_stats['total_lead_time']['median']) * 100):.1f}% |
| **Desv. Estándar** | {docker_stats['total_lead_time']['stdev']:.2f}s | {podman_stats['total_lead_time']['stdev']:.2f}s | - | - |
| **Mejor Tiempo** | {docker_stats['total_lead_time']['min']:.2f}s | {podman_stats['total_lead_time']['min']:.2f}s | - | - |
| **Peor Tiempo** | {docker_stats['total_lead_time']['max']:.2f}s | {podman_stats['total_lead_time']['max']:.2f}s | - | - |

---

## 📈 Visualización de Resultados

### Comparación de Tiempos Totales (segundos)

```
Docker:  {'█' * min(int(docker_stats['total_lead_time']['mean'] / 5), 40)} {docker_stats['total_lead_time']['mean']:.2f}s
Podman:  {'█' * min(int(podman_stats['total_lead_time']['mean'] / 5), 40)} {podman_stats['total_lead_time']['mean']:.2f}s
```

### Desglose por Componente

**Docker:**
```
Build:        {'█' * min(int(docker_stats['build_time']['mean'] / 3), 40)} {docker_stats['build_time']['mean']:.2f}s
Start:        {'█' * min(int(docker_stats['start_time']['mean'] / 1), 40)} {docker_stats['start_time']['mean']:.2f}s
Health Check: {'█' * min(int(docker_stats['health_check_time']['mean'] / 1), 40)} {docker_stats['health_check_time']['mean']:.2f}s
```

**Podman:**
```
Build:        {'█' * min(int(podman_stats['build_time']['mean'] / 3), 40)} {podman_stats['build_time']['mean']:.2f}s
Start:        {'█' * min(int(podman_stats['start_time']['mean'] / 1), 40)} {podman_stats['start_time']['mean']:.2f}s
Health Check: {'█' * min(int(podman_stats['health_check_time']['mean'] / 1), 40)} {podman_stats['health_check_time']['mean']:.2f}s
```

---

## 🎯 Conclusiones y Recomendaciones

### Análisis de Rendimiento

1. **Build Time**: {'Docker' if docker_stats['build_time']['mean'] < podman_stats['build_time']['mean'] else 'Podman'} construye imágenes {abs((docker_stats['build_time']['mean'] - podman_stats['build_time']['mean']) / max(docker_stats['build_time']['mean'], podman_stats['build_time']['mean']) * 100):.1f}% {'más rápido' if (docker_stats['build_time']['mean'] < podman_stats['build_time']['mean']) == docker_faster else 'más lento'}

2. **Container Start**: {'Docker' if docker_stats['start_time']['mean'] < podman_stats['start_time']['mean'] else 'Podman'} inicia contenedores {abs((docker_stats['start_time']['mean'] - podman_stats['start_time']['mean']) / max(docker_stats['start_time']['mean'], podman_stats['start_time']['mean']) * 100):.1f}% {'más rápido' if (docker_stats['start_time']['mean'] < podman_stats['start_time']['mean']) == docker_faster else 'más lento'}

3. **Consistencia**: {'Docker muestra mayor consistencia (menor desviación estándar en tiempo total)' if docker_stats['total_lead_time']['stdev'] < podman_stats['total_lead_time']['stdev'] else 'Podman muestra mayor consistencia (menor desviación estándar en tiempo total)'}

### Recomendación Final

**Se recomienda {'DOCKER' if docker_faster else 'PODMAN'}** para este proyecto basándose en:

✅ **Lead Time for Changes** {improvement_pct:.1f}% {'menor' if docker_faster else 'menor'}
✅ Mejor rendimiento en despliegues
✅ {'Mayor consistencia en tiempos de ejecución' if (docker_stats['total_lead_time']['stdev'] < podman_stats['total_lead_time']['stdev']) == docker_faster else 'Tiempos de ejecución aceptables'}

### Contexto DORA

Según las métricas DORA, un **Lead Time for Changes** más bajo indica:
- ✅ Mayor agilidad en el ciclo de desarrollo
- ✅ Feedback más rápido para desarrolladores  
- ✅ Mayor capacidad de respuesta a cambios
- ✅ Mejor experiencia DevOps general

**{'Docker' if docker_faster else 'Podman'}** proporciona un Lead Time {improvement_pct:.1f}% mejor, lo que se traduce en:
- Deployments más rápidos
- Menor tiempo de espera en CI/CD
- Mayor productividad del equipo

---

## 📝 Notas Metodológicas

- **Número de iteraciones**: {docker_stats['num_iterations']} por herramienta
- **Entorno de prueba**: Local
- **Medición**: Tiempo real desde inicio hasta aplicación funcionando
- **Limpieza**: Sistema limpio entre cada iteración
- **Caché**: Construcción sin caché (--no-cache)
- **Configuración**: Idéntica para ambas herramientas

---

## 🔗 Integración con Análisis Completo

Este análisis forma parte de la investigación comparativa de tecnologías DevOps:

1. ✅ **CI/CD**: Jenkins vs GitHub Actions (completado)
2. ✅ **Contenedores**: Docker vs Podman (este análisis)
3. ⏳ **Visualización**: Grafana vs Metabase (pendiente)

---

*Reporte generado automáticamente por el script de análisis DORA*  
*Herramienta: analyze_results.py v1.0*
"""
    
    return report

def save_stats_json(docker_stats, podman_stats):
    """Guarda las estadísticas en formato JSON para uso posterior"""
    output = {
        'docker': docker_stats,
        'podman': podman_stats,
        'comparison': {
            'winner': 'docker' if docker_stats['total_lead_time']['mean'] < podman_stats['total_lead_time']['mean'] else 'podman',
            'difference_seconds': abs(docker_stats['total_lead_time']['mean'] - podman_stats['total_lead_time']['mean']),
            'improvement_percentage': abs((docker_stats['total_lead_time']['mean'] - podman_stats['total_lead_time']['mean']) / 
                                         max(docker_stats['total_lead_time']['mean'], podman_stats['total_lead_time']['mean']) * 100)
        },
        'analysis_timestamp': datetime.now().isoformat()
    }
    
    stats_file = f"benchmark/results/statistics_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    with open(stats_file, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2)
    
    return stats_file

def main():
    print("=" * 60)
    print("📊 ANÁLISIS DE MÉTRICAS DORA - DOCKER VS PODMAN")
    print("=" * 60)
    print()
    
    # Cargar resultados
    print("🔍 Cargando resultados...")
    docker_data = load_results("docker")
    podman_data = load_results("podman")
    
    if not docker_data or not podman_data:
        print("\n❌ Error: No se encontraron resultados para ambas herramientas")
        print("\nDebes ejecutar primero:")
        print("  ./benchmark/scripts/measure_leadtime.sh docker 5")
        print("  ./benchmark/scripts/measure_leadtime.sh podman 5")
        sys.exit(1)
    
    print()
    
    # Calcular estadísticas
    print("📈 Calculando estadísticas...")
    docker_stats = calculate_stats(docker_data)
    podman_stats = calculate_stats(podman_data)
    
    # Generar reporte
    print("📝 Generando reporte comparativo...")
    report = generate_comparison_report(docker_stats, podman_stats)
    
    # Guardar reporte Markdown
    report_file = f"benchmark/results/REPORTE_COMPARATIVO_{datetime.now().strftime('%Y%m%d_%H%M%S')}.md"
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write(report)
    
    # Guardar estadísticas JSON
    stats_file = save_stats_json(docker_stats, podman_stats)
    
    print()
    print("=" * 60)
    print("✅ ANÁLISIS COMPLETADO")
    print("=" * 60)
    print()
    print(f"📄 Reporte Markdown: {report_file}")
    print(f"📊 Estadísticas JSON: {stats_file}")
    print()
    
    # Mostrar resumen en consola
    print("=" * 60)
    print("🎯 RESUMEN EJECUTIVO")
    print("=" * 60)
    print()
    print(f"Docker  - Lead Time Promedio: {docker_stats['total_lead_time']['mean']:.2f}s")
    print(f"Podman  - Lead Time Promedio: {podman_stats['total_lead_time']['mean']:.2f}s")
    print()
    
    winner = "DOCKER 🐳" if docker_stats['total_lead_time']['mean'] < podman_stats['total_lead_time']['mean'] else "PODMAN 🔷"
    diff = abs(docker_stats['total_lead_time']['mean'] - podman_stats['total_lead_time']['mean'])
    improvement = abs((docker_stats['total_lead_time']['mean'] - podman_stats['total_lead_time']['mean']) / 
                     max(docker_stats['total_lead_time']['mean'], podman_stats['total_lead_time']['mean']) * 100)
    
    print(f"🏆 GANADOR: {winner}")
    print(f"📊 Diferencia: {diff:.2f} segundos ({improvement:.1f}% mejor)")
    print()
    print("=" * 60)

if __name__ == "__main__":
    main()
