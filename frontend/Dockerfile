# Etapa de construcció (Build Stage)
FROM node:18 as build-stage

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

# Etapa de producció (Production Stage)
FROM nginx:stable-alpine as production-stage

# Copiar fitxers construïts
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Copiar configuració Nginx personalitzada
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copiar certificats SSL (s'han de generar abans o muntar com volum)
# En aquest cas, els muntarem via docker-compose, però creem el directori
RUN mkdir -p /etc/nginx/ssl

EXPOSE 80
EXPOSE 443

CMD ["nginx", "-g", "daemon off;"]
