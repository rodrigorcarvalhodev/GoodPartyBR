---
description: Scaffold completo de Laravel 11 + Docker (Octane/Swoole, Nginx, MySQL, Redis) com Alpine
autonomy: turbo-all
finalize: generate-report-artifacts
---
// turbo-all

# 🚀 Laravel Docker Octane Starter

Scaffold um projeto Laravel 11 com Docker, Octane (Swoole), Nginx (reverse proxy), MySQL 8, e Redis.
Todos os containers usam timezone America/Sao_Paulo.

---

## Pré-requisitos

- Docker e Docker Compose instalados
- Nenhuma porta em uso: 80, 3306, 6379

---

## Passo 1 — Estrutura de diretórios

Criar a seguinte estrutura no diretório do projeto:

```
projeto/
├── Docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── Dockerfile
├── application/        ← Laravel será instalado aqui
├── logs/
│   └── nginx/
├── compose.yml
└── .gitignore
```

Comando:
```bash
mkdir -p Docker/nginx Docker/php application logs/nginx
```

---

## Passo 2 — Dockerfile (PHP 8.3 CLI Alpine + Swoole)

Criar `Docker/php/Dockerfile`:

```dockerfile
FROM php:8.3-cli-alpine

# -------------------------------------------------------
# Timezone: America/Sao_Paulo
# -------------------------------------------------------
ENV TZ=America/Sao_Paulo
RUN apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo "$TZ" > /etc/timezone

# -------------------------------------------------------
# Ferramentas extras: bash, vim, composer
# -------------------------------------------------------
RUN apk add --no-cache \
    bash \
    vim \
    git \
    curl \
    unzip

# -------------------------------------------------------
# Dependências de compilação para extensões PHP + Swoole
# -------------------------------------------------------
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    openssl-dev \
    curl-dev \
    $PHPIZE_DEPS

# -------------------------------------------------------
# Extensões PHP exigidas pelo Laravel 11
# -------------------------------------------------------
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        opcache \
        sockets

# -------------------------------------------------------
# Swoole (Laravel Octane)
# -------------------------------------------------------
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# -------------------------------------------------------
# Composer (última versão estável)
# -------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -------------------------------------------------------
# Configuração do PHP para produção
# -------------------------------------------------------
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Ajuste de timezone no php.ini
RUN sed -i "s|;date.timezone =|date.timezone = America/Sao_Paulo|" /usr/local/etc/php/php.ini

# -------------------------------------------------------
# Configuração do OPcache (otimizado para Swoole)
# -------------------------------------------------------
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# -------------------------------------------------------
# Diretório de trabalho
# -------------------------------------------------------
WORKDIR /var/www/html

# -------------------------------------------------------
# Usuário não-root para segurança
# -------------------------------------------------------
RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/bash -D laravel

RUN chown -R laravel:laravel /var/www/html

USER laravel

EXPOSE 8000
```

---

## Passo 3 — Nginx (Reverse Proxy para Swoole)

Criar `Docker/nginx/default.conf`:

```nginx
upstream swoole {
    server app:8000;
}

server {
    listen 80;
    server_name localhost;

    root /var/www/html/public;

    charset utf-8;

    # -------------------------------------------------------
    # Logs
    # -------------------------------------------------------
    access_log /var/log/nginx/access.log;
    error_log  /var/log/nginx/error.log;

    # -------------------------------------------------------
    # Arquivos estáticos servidos diretamente pelo nginx
    # -------------------------------------------------------
    location /build/ {
        expires max;
        access_log off;
    }

    location /vendor/ {
        expires max;
        access_log off;
    }

    # -------------------------------------------------------
    # index.php NUNCA deve ser servido como arquivo estático
    # Sempre redireciona para o Swoole
    # -------------------------------------------------------
    location /index.php {
        try_files /not_exists @swoole;
    }

    # -------------------------------------------------------
    # Requisições gerais: tenta servir arquivo estático,
    # caso contrário envia para o Swoole
    # -------------------------------------------------------
    location / {
        try_files $uri @swoole;
    }

    # -------------------------------------------------------
    # Swoole – proxy reverso HTTP para o container app:8000
    # -------------------------------------------------------
    location @swoole {
        proxy_pass              http://swoole;
        proxy_http_version      1.1;
        proxy_set_header        Host $host;
        proxy_set_header        Upgrade $http_upgrade;
        proxy_set_header        Connection "upgrade";
        proxy_set_header        X-Real-IP $remote_addr;
        proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header        X-Forwarded-Proto $scheme;
        proxy_read_timeout      300;
        proxy_connect_timeout   300;
        proxy_send_timeout      300;
        proxy_buffering         on;
        proxy_buffer_size       128k;
        proxy_buffers           4 256k;
    }

    # -------------------------------------------------------
    # Bloquear acesso a arquivos ocultos (.env, .git, etc.)
    # -------------------------------------------------------
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Passo 4 — Docker Compose

Criar `compose.yml` na raiz do projeto. Substituir `PROJECT_NAME` pelo nome do projeto:

```yaml
services:
  # -------------------------------------------------
  # Laravel Octane + Swoole
  # -------------------------------------------------
  app:
    build:
      context: .
      dockerfile: Docker/php/Dockerfile
    container_name: PROJECT_NAME-app
    restart: unless-stopped
    command: ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]
    volumes:
      - ./application:/var/www/html
    depends_on:
      - mysql
      - redis
    networks:
      - PROJECT_NAME-network

  # -------------------------------------------------
  # Nginx (Proxy Reverso)
  # -------------------------------------------------
  nginx:
    image: nginx:alpine
    container_name: PROJECT_NAME-nginx
    restart: unless-stopped
    environment:
      TZ: America/Sao_Paulo
    ports:
      - "80:80"
    volumes:
      - ./application:/var/www/html
      - ./Docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./logs/nginx:/var/log/nginx
    depends_on:
      - app
    networks:
      - PROJECT_NAME-network

  # -------------------------------------------------
  # MySQL
  # -------------------------------------------------
  mysql:
    image: mysql:8.0
    container_name: PROJECT_NAME-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: PROJECT_NAME
      MYSQL_USER: PROJECT_NAME
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
      TZ: America/Sao_Paulo
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - PROJECT_NAME-network

  # -------------------------------------------------
  # Redis
  # -------------------------------------------------
  redis:
    image: redis:alpine
    container_name: PROJECT_NAME-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - PROJECT_NAME-network

networks:
  PROJECT_NAME-network:
    driver: bridge

volumes:
  mysql-data:
    driver: local
```

---

## Passo 5 — .env.example

Criar `application/.env.example`:

```env
APP_NAME=PROJECT_NAME
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=PROJECT_NAME
DB_USERNAME=PROJECT_NAME
DB_PASSWORD=secret

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=redis
CACHE_PREFIX=

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

OCTANE_SERVER=swoole

VITE_APP_NAME="${APP_NAME}"
```

> **IMPORTANTE:** `DB_HOST=mysql` e `REDIS_HOST=redis` usam o nome do serviço no Docker, NÃO localhost.

---

## Passo 6 — Instalar Laravel e Octane

```bash
# Subir somente mysql e redis primeiro
docker compose up -d mysql redis

# Build da imagem PHP (primeira vez demora ~7min por causa do Swoole)
docker compose build app

# Instalar Laravel dentro do container (se projeto novo)
docker compose run --rm app composer create-project laravel/laravel .

# Copiar .env.example para .env
docker compose run --rm app cp .env.example .env

# Gerar APP_KEY
docker compose run --rm app php artisan key:generate

# Instalar Laravel Octane
docker compose run --rm app composer require laravel/octane

# Configurar Octane com Swoole
docker compose run --rm app php artisan octane:install --server=swoole

# Rodar migrations
docker compose run --rm app php artisan migrate

# Subir tudo
docker compose up -d
```

---

## Passo 7 — Validação (14 testes de infraestrutura)

Rodar os seguintes comandos para validar a infraestrutura:

```bash
# 1. Containers rodando
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# 2. PHP version
docker exec PROJECT_NAME-app php -v

# 3. Swoole instalado
docker exec PROJECT_NAME-app php -r "echo 'Swoole ' . swoole_version();"

# 4. Extensões PHP carregadas
docker exec PROJECT_NAME-app php -m

# 5. Composer instalado
docker exec PROJECT_NAME-app composer --version

# 6. Bash instalado
docker exec PROJECT_NAME-app bash --version

# 7. Timezone do app (sistema)
docker exec PROJECT_NAME-app date

# 8. Timezone do PHP
docker exec PROJECT_NAME-app php -r "echo date_default_timezone_get();"

# 9. Timezone do nginx
docker exec PROJECT_NAME-nginx date

# 10. Timezone do mysql
docker exec PROJECT_NAME-mysql date

# 11. MySQL conectividade
docker exec PROJECT_NAME-app php artisan db:monitor

# 12. Redis conectividade
docker exec PROJECT_NAME-redis redis-cli ping

# 13. Octane status
docker exec PROJECT_NAME-app php artisan octane:status

# 14. HTTP response
# PowerShell:
Invoke-WebRequest -Uri http://localhost -UseBasicParsing | Select-Object StatusCode
# Linux/Mac:
curl -s -o /dev/null -w "%{http_code}" http://localhost
```

**Resultados esperados:**
- Testes 1–6: versões instaladas corretamente
- Testes 7–10: todos mostram `-03` (BRT / São Paulo)
- Teste 11: `mysql OK`
- Teste 12: `PONG`
- Teste 13: `Octane server is running`
- Teste 14: Status `200`

---

## Passo 8 — InfraTest (PHPUnit)

Criar `application/tests/Feature/InfraTest.php` com o conteúdo documentado abaixo para automatizar os testes de infraestrutura via `php artisan test --filter=InfraTest`.

---

## Arquitetura final

```
Browser :80
    │
    ▼
┌──────────┐    arquivos estáticos
│  Nginx   │───────────────────────► /var/www/html/public/build/, /vendor/
│  :80     │
│          │    proxy_pass (HTTP)
│          │───────────────────────►┌──────────────┐
└──────────┘                       │ Swoole/Octane │
                                   │ :8000         │
                                   │               │
                                   │  app:8000     │
                                   └──────┬────────┘
                                          │
                          ┌───────────────┼───────────────┐
                          ▼                               ▼
                   ┌──────────┐                    ┌──────────┐
                   │  MySQL   │                    │  Redis   │
                   │  :3306   │                    │  :6379   │
                   └──────────┘                    └──────────┘
```

---

## Dicas para desenvolvimento

- **Hot reload:** Comente o `command` no compose.yml, suba os containers, e rode manualmente:
  ```bash
  docker exec -it PROJECT_NAME-app bash
  php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000 --watch
  ```
- **Logs do nginx:** Disponíveis em `./logs/nginx/`
- **MySQL GUI:** Conecte em `localhost:3306` com user/pass do compose
- **Redis CLI:** `docker exec -it PROJECT_NAME-redis redis-cli`
