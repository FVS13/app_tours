#!/bin/bash

function slashes() {
    echo "$1" | sed -e 's/\//\\\//g' -e 's/[ ]/\\ /g'
}

if [[ "--help" == "$1" ]] || [[ "-h" == "$1" ]];
then
    echo 'Входные параметры:';
    echo '  -a    адрес сервера в сети'
    echo '  -d    имя базы данных'
    echo '  -u    имя пользователя базы данных'
    echo '  -p    пароль к базе данных'
    echo '  -r    корневая директория входящих данных'
    echo '  -n    имя приложения'
    echo '  -s    адрес сервера парсеров'
    echo '';
    echo 'При отсутствии адреса сервера конфигурация не будет добавлена или обновлена'
    echo 'Для остальных параметров будет установлено значение по умолчанию'
    exit 0;
fi

while getopts ":d:u:p:a:r:n:s:" optname
do
    case "$optname" in
        a)
            SERVER_DOMAIN="$OPTARG"
            ;;
        d)
            DB_NAME="$OPTARG"
            ;;
        u)
            DB_USER="$OPTARG"
            ;;
        p)
            DB_PASSWORD="$OPTARG"
            ;;
        r)
            ROOT_DATA="$OPTARG"
            ;;
        n)
            APP_NAME="$OPTARG"
            ;;
        s)
            PARSERS_ADDRESS="$OPTARG"
            ;;
        *)
            echo "Неизвестный параметр '$optname'"
            exit 1;
            ;;
    esac
done

DB_NAME=${DB_NAME:-'app_tours'}
DB_USER=${DB_USER:-"$DB_NAME"}
DB_PASSWORD=${DB_PASSWORD:-`pwgen -s 16 1`}
ROOT_DATA=${ROOT_DATA:-'/root'}
APP_NAME=${APP_NAME:-'app_tours'}
PARSERS_ADDRESS=${PARSERS_ADDRESS:-'localhost'}

ABSOLUTE_FILENAME=`readlink -e "$0"`;
DIRECTORY=`dirname "$ABSOLUTE_FILENAME"`;
ROOT=`cd "$DIRECTORY"/.. && pwd`
WEBROOT="$ROOT"/web;


echo 'Значения основных параметров:';

if [[ -z "$SERVER_DOMAIN" ]]
then
    echo -e "\033[33m  Адрес сервера не указан. Конфигурация сервера не создана\033[0m"
else
    echo -e "\033[32m  Адрес сервера: '$SERVER_DOMAIN'\033[0m";
fi

echo -en "\033[32m";
echo "  Имя базы данных: '$DB_NAME'";
echo "  Имя пользователя базы данных: '$DB_USER'";
echo "  Пароль от базы данных: '$DB_PASSWORD'";
echo "  Корневая директория для входящих данных: '$ROOT_DATA'";
echo "  Корневая директория приложения: '$ROOT'";
echo "  Имя приложения: '$APP_NAME'";
echo "  Адрес сервера парсеров: '$PARSERS_ADDRESS'";
echo -en "\033[0m";


cd "$ROOT"
echo -e '\nСоздание необходиных катологов'

mkdir -p "$ROOT"/runtime/logs
mkdir -p "$ROOT"/web/export/archives
mkdir -p "$ROOT"/web/export/minmax
mkdir -p "$ROOT"/web/incorrect_tours
mkdir -p "$ROOT_DATA"/data
mkdir -p "$ROOT_DATA"/incoming_data

echo -e '\nУстановка владельца и прав доспута'

chown -R www-data:www-data "$ROOT"
chown -R www-data:www-data "$ROOT_DATA"/incoming_data

find "$ROOT" -type d -print0 | xargs -0 chmod 0775
find "$ROOT" -type f -print0 | xargs -0 chmod 0664
chmod ug+x "$ROOT"/sh/*
chmod ug+x "$ROOT"/bilet_apptours
# find "$ROOT"/config/ -type d -print0 | xargs -0 chmod 0550
# find "$ROOT"/config/ -type f -print0 | xargs -0 chmod 0440

echo -e '\nУстановка необходимых пакетов на сервер'
"$ROOT"/sh/init_server.sh

echo -e '\nУстановка приложения'
sudo -u www-data composer install


sed \
        -e 's/%%parsers_address%%/'"$PARSERS_ADDRESS"'/g' \
        -e "s/%%root%%/$(slashes "$ROOT")/g" \
        -e "s/%%root_data%%/$(slashes "$ROOT_DATA")/g" \
        "$ROOT"/config/params_sample.php > "$ROOT"/config/params.php


# Настройка Апача
if ! [[ -z "$SERVER_DOMAIN" ]]; then
    echo -e '\nСоздание конфигурации сервера Апач'

    sed \
        -e 's/%%domain%%/'"$SERVER_DOMAIN"'/g' \
        -e "s/%%abs_webroot%%/$(slashes "$WEBROOT")/g" \
        "$ROOT"/config/apache-config.conf > /etc/apache2/sites-available/"$SERVER_DOMAIN".conf

    a2ensite "$SERVER_DOMAIN".conf

    systemctl restart apache2
fi


# Настройка базы данных

echo -e '\nСоздание конфигурации базы данных MySQL'

sed \
    -e 's/%%local_db_name%%/'"$DB_NAME"'/g' \
    -e 's/%%local_user_name%%/'"$DB_USER"'/g' \
    -e 's/%%local_password%%/'"$DB_PASSWORD"'/g' \
    "$ROOT"/config/db_sample.php > "$ROOT"/config/db.php;

sed \
    -e 's/%%db_name%%/'"$DB_NAME"'/g' \
    -e 's/%%user_name%%/'"$DB_USER"'/g' \
    -e 's/%%password%%/'"$DB_PASSWORD"'/g' \
    "$ROOT"/mysql/scripts/init_sapmle.sql > "$ROOT"/mysql/scripts/init.sql

mysql < "$ROOT"/mysql/scripts/init.sql
"$ROOT"/bilet_apptours migrate --interactive=0

systemctl restart mysql


# Настсройка sudo

echo -e '\nДобавление прав на sudo для приложения'

cp "$ROOT"/config/sudoers /etc/sudoers.d/"$APP_NAME"
