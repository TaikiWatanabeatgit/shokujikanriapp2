FROM php:7.3-apache

# php.iniをコピー
COPY ./docker/php.ini /usr/local/etc/php/

# 必要なパッケージと拡張機能をインストール
RUN apt-get update && \
    apt-get install -y \
    git \
    unzip \
    zlib1g-dev \
    libgmp-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libgd-dev \
    cron \
    vim \
    libonig-dev \
    libzip-dev \
    locales \
    nodejs \
    npm && \
    ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/include/gmp.h && \
    docker-php-ext-configure gd --with-freetype-dir --with-jpeg-dir && \
    docker-php-ext-install mbstring pdo_mysql mysqli zip gd gmp bcmath

# 日本語ロケールの生成
RUN locale-gen ja_JP.UTF-8 && \
    echo "LANG=ja_JP.UTF-8" > /etc/default/locale && \
    echo "LANGUAGE=ja_JP:ja" >> /etc/default/locale && \
    echo "LC_ALL=ja_JP.UTF-8" >> /etc/default/locale

# Apacheのユーザーとグループの権限を更新
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Apacheモジュールを有効化
RUN a2enmod rewrite headers

# メイン設定でAllowOverrideを許可し、サイト設定でDocumentRootを変更
RUN sed -i '\#<Directory /var/www/>#,\#</Directory># s#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf && \
    sed -i 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/my_fuel_project/public!' /etc/apache2/sites-available/000-default.conf

# FuelPHPのログディレクトリとキャッシュディレクトリを作成し、権限を設定
RUN mkdir /var/log/fuel /var/cache/fuel && \
    chmod 777 /var/log/fuel /var/cache/fuel

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

# Oilをインストール
RUN mkdir /var/www/html/my_fuel_project && \
    cd /var/www/html/my_fuel_project && \
    curl https://get.fuelphp.com/oil | sh

EXPOSE 80

# Apacheとcronをフォアグラウンドで起動
CMD ["apache2-foreground"]
