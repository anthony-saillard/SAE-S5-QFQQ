# Base stage
FROM node:18-alpine AS base
ARG DOMAIN_NAME
ENV DOMAIN_NAME=$DOMAIN_NAME

# Install Quasar CLI globally first
RUN npm install -g @quasar/cli

# Define working directory
WORKDIR /app

# Copy only package files first
COPY package*.json ./

# Remove postinstall script temporarily
RUN npm pkg delete scripts.postinstall

# Install dependencies
RUN npm install

# Copy project files
COPY . .

# Run Quasar prepare manually
RUN quasar prepare

# Development stage
FROM base AS dev
EXPOSE 8080
ENV NODE_ENV=development
CMD ["npm", "run", "dev"]

# Production build stage
FROM base AS builder
ARG DOMAIN_NAME
ENV DOMAIN_NAME=$DOMAIN_NAME
RUN quasar build

# Production stage
FROM nginx:alpine AS prod
COPY --from=builder /app/dist/spa /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 8080
CMD ["nginx", "-g", "daemon off;"]