FROM centos:7

RUN yum update -y && yum install -y wget

RUN wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
    && wget https://rpms.remirepo.net/enterprise/remi-release-7.rpm \
    && rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm

RUN yum install -y php74-php-cli php74-php-gd php74-php-json php74-php-xml php74-php-mbstring \
        php74-php-pecl-zip

RUN curl -sS https://getcomposer.org/installer | php74 -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /opt/app

COPY composer.json composer.json
COPY composer.lock composer.lock
COPY ubuntu.ttf    ubuntu.ttf

RUN php74 /usr/local/bin/composer install --no-dev

COPY main.php main.php

ENTRYPOINT [ "php74", "main.php" ]
