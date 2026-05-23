FROM ubuntu:22.04

LABEL maintainer="Polit Team"

ARG WWWGROUP=1000
ARG NODE_VERSION=20
ARG POSTGRES_VERSION=16

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Asia/Seoul

# ─────────────────────────────────────────────
# 시스템 패키지 + PHP 8.2
# ─────────────────────────────────────────────
RUN apt-get update \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /usr/share/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/usr/share/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
        php8.2-cli \
        php8.2-dev \
        php8.2-pgsql \
        php8.2-sqlite3 \
        php8.2-gd \
        php8.2-curl \
        php8.2-memcached \
        php8.2-redis \
        php8.2-imap \
        php8.2-mbstring \
        php8.2-xml \
        php8.2-zip \
        php8.2-bcmath \
        php8.2-soap \
        php8.2-intl \
        php8.2-readline \
        php8.2-ldap \
        php8.2-pcov \
        php8.2-xdebug \
        php8.2-fpm \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -fsSL https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /usr/share/keyrings/yarn.gpg >/dev/null \
    && echo "deb [signed-by=/usr/share/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ─────────────────────────────────────────────
# PHP-FPM 설정
# Ubuntu 22.04 기본값은 Unix 소켓이므로 TCP 9000으로 변경해야
# Nginx가 app:9000 으로 연결 가능 (누락 시 502 Bad Gateway 발생)
# ─────────────────────────────────────────────
RUN sed -i "s/user = www-data/user = sail/"                     /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i "s/group = www-data/group = sail/"                 /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i "s/listen.owner = www-data/listen.owner = sail/"   /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i "s/listen.group = www-data/listen.group = sail/"   /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i "s|^listen = .*|listen = 9000|"                    /etc/php/8.2/fpm/pool.d/www.conf

# ─────────────────────────────────────────────
# 사용자 생성 (sail) + 로그 디렉터리 생성
# ─────────────────────────────────────────────
RUN groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail \
    && mkdir -p /var/log/supervisor

# ─────────────────────────────────────────────
# Supervisor + 컨테이너 시작 스크립트
# start-container: 단독 커맨드(composer install 등) 와 서비스 시작 모두 지원
# ─────────────────────────────────────────────
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/start-container  /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 9000

ENTRYPOINT ["start-container"]
