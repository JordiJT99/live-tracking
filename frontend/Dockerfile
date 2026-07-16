# --- Stage 1: build the Vue app to static files ---
FROM node:22-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Vite bakes env vars at build time. Hit the real API (not mocks); the
# baseURL stays the relative /api/v1, which nginx proxies to the backend.
ENV VITE_USE_MOCK=false
RUN npm run build-only

# --- Stage 2: serve the static build with nginx ---
FROM nginx:alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
