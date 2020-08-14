apt -y install sudo zip unzip libapache2-mod-php php-{mbstring,gd,zip,bcmath,bz2,intl,mysql,ctype,json,xml,pdo} mariadb-server mariadb-client mariadb-common composer pwgen

a2enmod rewrite
systemctl start apache2
systemctl start mysql
