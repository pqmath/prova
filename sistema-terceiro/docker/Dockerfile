FROM php:8.2-fpm-alpine

# Instalar dependências do sistema + BASH
RUN apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    nginx \
    supervisor \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        zip \
        mbstring \
        bcmath \
        pcntl

# (Nota: Adicionei sqlite-dev e pdo_sqlite acima por garantia,
# caso sua imagem base não tenha nativo, embora o erro anterior mostre que o driver existe)

# Definir BASH como shell padrão
SHELL ["/bin/bash", "-c"]

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar código da aplicação
COPY ../.. .

# Instalar dependências do Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Criar pastas de log
RUN mkdir -p /var/log/supervisor /var/log/nginx

# Garantir que as pastas do Laravel existam
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    database

# ------------------------------------------------------------------------------
# AJUSTE 1: Criar o arquivo sqlite vazio se não existir para o chmod não falhar
# ------------------------------------------------------------------------------
RUN touch database/database.sqlite

# ------------------------------------------------------------------------------
# AJUSTE 2: Aplicar permissões na imagem (Build Time)
# Adicionei a pasta database e o arquivo sqlite aqui
# ------------------------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database \
    && chmod 664 database/database.sqlite

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expor porta
EXPOSE 80

# ------------------------------------------------------------------------------
# AJUSTE 3 (O TRUQUE FINAL): Adicionar database ao chmod do CMD
# Isso garante que funcione mesmo se um Volume sobrescrever as permissões
# ------------------------------------------------------------------------------
CMD sh -c "chmod -R 777 storage bootstrap/cache database && touch database/database.sqlite && chmod 666 database/database.sqlite && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf"
