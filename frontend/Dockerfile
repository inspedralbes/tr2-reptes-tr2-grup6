FROM node:22-alpine

WORKDIR /app

# Copiar fitxers de paquets
COPY package.json package-lock.json* ./

# Instalar dependencies
RUN npm install

# Copiar la resta de la aplicacio
COPY . .

# exposar la aplicacio al port
EXPOSE 5173

# executar el server host
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
