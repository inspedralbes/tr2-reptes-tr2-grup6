#!/bin/bash

# Directori on es guardaran els certificats
CERT_DIR="./docker/certs"
mkdir -p $CERT_DIR

# Noms dels fitxers
KEY_FILE="$CERT_DIR/server.key"
CRT_FILE="$CERT_DIR/server.crt"

# Comprovar si ja existeixen
if [ -f "$KEY_FILE" ] && [ -f "$CRT_FILE" ]; then
    echo "✅ Els certificats SSL ja existeixen a $CERT_DIR"
else
    echo "🔒 Generant certificats SSL auto-firmats..."
    
    # Generar clau privada i certificat (vàlid per 365 dies)
    # IMPORTANT: El Common Name (CN) ha de coincidir amb el domini
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$KEY_FILE" \
        -out "$CRT_FILE" \
        -subj "/C=ES/ST=Barcelona/L=Barcelona/O=InsPedralbes/OU=DAW/CN=kairos.daw.inspedralbes.cat"

    echo "✅ Certificats generats correctament!"
fi
