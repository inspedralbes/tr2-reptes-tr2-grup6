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

# 2.# Instal·lar Docker Compose V2 (Plugin) i esborrar l'antic
if command -v docker-compose &> /dev/null; then
    echo -e "${YELLOW}🗑️  Esborrant versió antiga de Docker Compose...${NC}"
    apt-get remove -y docker-compose
    rm -f /usr/local/bin/docker-compose
fi

echo -e "${YELLOW}📦 Instal·lant Docker Compose V2...${NC}"
apt-get update
if ! apt-get install -y docker-compose-plugin; then
    echo -e "${YELLOW}⚠️  No s'ha trobat el paquet al repositori. Instal·lant binari manualment...${NC}"
    
    # 1. Instal·lar com a plugin de Docker CLI
    mkdir -p /usr/local/lib/docker/cli-plugins
    curl -SL https://github.com/docker/compose/releases/download/v2.29.1/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
    chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
    
    # 2. Instal·lar com a commanda standalone (backup)
    curl -SL https://github.com/docker/compose/releases/download/v2.29.1/docker-compose-linux-x86_64 -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    ln -sf /usr/local/bin/docker-compose /usr/bin/docker-compose
fi

# Verificar instal·lació
docker compose version

# 3. Clonar o Actualitzar Repositori
if [ -d ".git" ]; then
    echo -e "${YELLOW}🔄 Ja estàs dins del repositori. Actualitzant codi...${NC}"
    git pull origin main
else
    REPO_URL="https://github.com/inspedralbes/tr2-reptes-tr2-grup6.git"
    PROJECT_DIR="kairos"

    if [ -d "$PROJECT_DIR" ]; then
        echo -e "${YELLOW}🔄 La carpeta $PROJECT_DIR ja existeix. Entrant i actualitzant...${NC}"
        cd $PROJECT_DIR
        git pull origin main
    else
        echo -e "${YELLOW}⬇️  Clonant repositori...${NC}"
        git clone $REPO_URL
        cd $PROJECT_DIR
    fi
fi

# 4. Configuració inicial
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚙️  Configurant .env...${NC}"
    cp .env.example .env
fi

# 5. Generar SSL (Dockeritzat per compatibilitat)
echo -e "${YELLOW}🔒 Generant certificats SSL...${NC}"
bash init-ssl.sh || {
    mkdir -p docker/certs
    docker run --rm -v "${PWD}/docker/certs:/certs" alpine/openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /certs/server.key -out /certs/server.crt -subj "/C=ES/ST=Barcelona/L=Barcelona/O=InsPedralbes/OU=DAW/CN=kairos.daw.inspedralbes.cat"
}

# 6. Neteja i Desplegament
echo -e "${YELLOW}🧹 Aturant contenidors antics...${NC}"
docker compose down --remove-orphans || docker-compose down --remove-orphans

echo -e "${YELLOW}🐳 Aixecant nous serveis de producció...${NC}"
docker compose up -d --build || docker-compose up -d --build

echo -e "${GREEN}✅ Desplegament completat!${NC}"
echo -e "🌍 Pots accedir a: https://<IP-DEL-SERVIDOR>"
