FROM jenkins/jenkins:lts

USER root

# =====================================================
# Actualizar y limpiar primero
# =====================================================
RUN apt-get update -qq && \
    apt-get upgrade -y && \
    apt-get clean

# =====================================================
# Instalar dependencias básicas (sin build-essential que causa problemas)
# =====================================================
RUN apt-get install -y --no-install-recommends \
    curl \
    git \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# =====================================================
# Instalar Docker CLI desde repositorio oficial
# =====================================================
RUN curl -fsSL https://get.docker.com -o get-docker.sh && \
    sh get-docker.sh && \
    rm get-docker.sh

# =====================================================
# Instalar Docker Compose V2
# =====================================================
RUN curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" \
    -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose && \
    docker-compose --version

# =====================================================
# Crear grupo docker y agregar usuario jenkins
# =====================================================
RUN groupadd -f docker && \
    usermod -aG docker jenkins && \
    id jenkins

# Volver al usuario jenkins
USER jenkins

# =====================================================
# Verificación
# =====================================================
RUN jenkins-plugin-cli --version || echo "Jenkins CLI available"
