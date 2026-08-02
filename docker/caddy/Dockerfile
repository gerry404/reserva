# ─────────────────────────────────────────────────────────────────────────────
# Edge container: builds the Vue bundle, then serves it alongside Caddy.
#
# The frontend ships inside the proxy image rather than as a mounted volume, so
# `docker compose up --build` always serves the bundle that matches the commit
# being deployed — there is no way to end up with a stale dist/ on disk.
# ─────────────────────────────────────────────────────────────────────────────

FROM node:20-alpine AS build

WORKDIR /build

# Lockfile first: dependencies only reinstall when they actually change.
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./

# Vite inlines these at build time — they cannot be changed at runtime.
ARG VITE_SITE_URL
ARG VITE_API_URL=/api
ARG VITE_GOOGLE_CLIENT_ID=""

ENV VITE_SITE_URL=$VITE_SITE_URL \
    VITE_API_URL=$VITE_API_URL \
    VITE_GOOGLE_CLIENT_ID=$VITE_GOOGLE_CLIENT_ID

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
FROM caddy:2-alpine

COPY --from=build /build/dist /srv/www
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
