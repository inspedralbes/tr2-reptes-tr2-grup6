#!/bin/bash
set -e

# Colors per output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Iniciant instal·lació de KAIROS al servidor...${NC}"

# 1. Actualitzar sistema i instal·lar eines bàsiques
echo -e "${YELLOW}📦 Actualitzant paquets i instal·lant Docker...${NC}"
apt-get update
apt-get install -y git curl apt-transport-https ca-certificates software-properties-common gnupg lsb-release

# 2. Instal·lar Docker si no hi és
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    echo -e "${GREEN}✅ Docker instal·lat.${NC}"
else
    echo -e "${GREEN}✅ Docker ja estava instal·lat.${NC}"
fi

# Instal·lar Docker Compose si cal
if ! command -v docker-compose &> /dev/null; then
    # Intentar instal·lar plugin compose o binari antic
    apt-get install -y docker-compose-plugin || apt-get install -y docker-compose
fi

# 3. Clonar o Actualitzar Repositori
REPO_URL="https://github.com/inspedralbes/tr2-reptes-tr2-grup6.git"
PROJECT_DIR="kairos"

if [ -d "$PROJECT_DIR" ]; then
    echo -e "${YELLOW}🔄 Actualitzant codi font...${NC}"
    cd $PROJECT_DIR
    git pull origin main
else
    echo -e "${YELLOW}⬇️  Clonant repositori...${NC}"
    git clone $REPO_URL
    cd $PROJECT_DIR
fi

# 4. Configuració inicial
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚙️  Configurant .env...${NC}"
    cp .env.example .env
    # Aquí podries demanar canviar contrasenyes si fos interactiu
fi

# 5. Generar SSL (Dockeritzat per compatibilitat)
echo -e "${YELLOW}🔒 Generant certificats SSL...${NC}"
bash init-ssl.sh || {
    # Fallback si falla localment, usar mètode docker
    mkdir -p docker/certs
    docker run --rm -v "${PWD}/docker/certs:/certs" alpine/openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /certs/server.key -out /certs/server.crt -subj "/C=ES/ST=Barcelona/L=Barcelona/O=InsPedralbes/OU=DAW/CN=kairos.daw.inspedralbes.cat"
}

# 6. Desplegar
echo -e "${YELLOW}🐳 Aixecant serveis...${NC}"
docker compose up -d --build || docker-compose up -d --build

echo -e "${GREEN}✅ Desplegament completat!${NC}"
echo -e "🌍 Pots accedir a: https://<IP-DEL-SERVIDOR>"
