FROM python:3.13-alpine AS export
WORKDIR /app
COPY frontend/ frontend/
COPY data/catalog.csv data/catalog.csv
COPY scripts/export_pages.py scripts/export_pages.py
RUN python3 scripts/export_pages.py --output /site

FROM nginx:1.28-alpine
COPY docker/frontend/default.conf /etc/nginx/conf.d/default.conf
COPY --from=export /site/ /usr/share/nginx/html/
