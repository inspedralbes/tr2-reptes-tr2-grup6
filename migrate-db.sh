#!/bin/bash
set -e

REMOTE_SERVER="65.109.167.111"

echo "📦 Exportant base de dades local..."
# Exportar tot EXCEPTE la base de dades 'information_schema', 'mysql', etc. només la nostra 'kairos_db'
docker exec kairos_db mysqldump -u kairos_user -pkairos_secret_password_change_this kairos_db > dump_local.sql

echo "🚀 Pujant i important al servidor..."

# Enviar i executar al servidor (sense comprimir per compatibilitat Windows)
# 1. Copiem el fitxer sql
scp dump_local.sql root@$REMOTE_SERVER:/root/dump_local.sql

# 2. Executem l'importació remotament
ssh root@$REMOTE_SERVER "docker exec -i kairos_db mysql -u kairos_user -pkairos_secret_password_change_this kairos_db < /root/dump_local.sql && rm /root/dump_local.sql"

echo "✅ Base de dades sincronitzada correctament!"
# Neteja local
rm dump_local.sql
