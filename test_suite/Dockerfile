FROM ubuntu:18.04

ENV PHP_SUBVERSION 7.3.29
ENV PHPBREW_PHP php-$PHP_SUBVERSION

RUN apt-get update
RUN apt-get install -y gcc git wget autoconf automake bison build-essential curl flex \
                                      libtool libssl-dev libcurl4-openssl-dev libxml2-dev libreadline7 \
                                      libreadline-dev libsqlite3-dev libzip-dev libzip4 nginx openssl \
                                      pkg-config re2c sqlite3 zlib1g-dev libbz2-dev libxslt-dev libgmp-dev libsodium-dev
RUN apt-get clean -y && apt-get autoclean -y && apt-get autoremove -y


RUN mkdir ~/apps && cd ~/apps/ && wget https://www.php.net/distributions/php-7.3.29.tar.gz \
    && tar -xzf php-7.3.29.tar.gz && cd php-7.3.29 && ./buildconf --force \
    && ./configure --with-fpm-user=www-data \
           --with-fpm-group=www-data \
           --disable-cgi \
           --enable-fpm \
           --enable-sockets \
           --enable-sysvmsg \
           --enable-sysvsem \
           --enable-sysvshm \
           --enable-pcntl \
           --enable-bcmath \
           --enable-mbstring \
           --enable-phpdbg \
           --enable-shmop \
           --enable-simplexml \
           --enable-json \
           --enable-hash \
           --enable-session \
           --enable-xml \
           --enable-wddx \
           --enable-opcache \
           --enable-maintainer-zts \
           --enable-debug \
           --enable-zip \
           --with-zlib \
           --with-readline \
           --with-curl \
           --with-pear \
           --with-openssl \
           --with-sodium \
    && make && make install \
    && ln -s /usr/local/bin/php /usr/bin/php \
    && mkdir -p /usr/local/lib && cp php.ini-development /usr/local/lib/php.ini \
    && wget -q -O /usr/bin/composer https://getcomposer.org/composer.phar && chmod +x /usr/bin/composer

RUN cd ~/apps && git clone https://github.com/krakjoe/pthreads.git \
    && cd pthreads && phpize \
    && ./configure \
       --with-libdir='/lib/x86_64-linux-gnu' \
       --enable-pthreads=shared \
    && make && make install \
    && pecl install xdebug-2.9.8 && pecl install pecl_http

RUN echo 'extension = pthreads' >> /usr/local/lib/php.ini \
    && echo 'extension = raphf'  >> /usr/local/lib/php.ini \
    && echo 'zend_extension = xdebug' >> /usr/local/lib/php.ini \
    && echo 'xdebug.remote_enable = on'  >> /usr/local/lib/php.ini \
    && echo 'xdebug.remote_autostart = off' >> /usr/local/lib/php.ini
