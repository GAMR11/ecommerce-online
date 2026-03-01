FROM jenkins/jenkins:lts-jdk17

USER root

# Copiamos solo el cliente docker desde la imagen oficial
COPY --from=docker:26-cli /usr/local/bin/docker /usr/local/bin/docker

USER jenkins