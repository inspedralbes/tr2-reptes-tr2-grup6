FROM node:22-bullseye-slim

WORKDIR /app

# Copy node config files first for better caching
COPY package.json package-lock.json* ./

# Clean install with forced rebuild of optional dependencies
RUN npm install --force --verbose 2>&1 | tail -20

# Copy application source
COPY . .

# Expose Vite port
EXPOSE 5173

# Start Vite and bind to all interfaces
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
