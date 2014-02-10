#!/bin/sh
if [ "$(id -u)" != "0" ]; then
    echo "This script must be run as root" 1>&2
    exit 1
fi

# Instal required software.
echo "Install required software."
apt-get install -y apache2 libapache2-mod-php5 php5-cli php5-mcrypt php5-mhash curl php5-curl
apt-get install -y php5-json

# Setup required variables.
echo "Setup required variables."
PHPMINER_PATH=`readlink -f $0`
PHPMINER_PATH=`dirname $PHPMINER_PATH`

HOSTNAME=`hostname`
DOMAIN="phpminer.$HOSTNAME"
APACHE_USER=`cat /etc/apache2/envvars | grep APACHE_RUN_USER | awk -F= '{print $2}'`
APACHE_GROUP=`cat /etc/apache2/envvars | grep APACHE_RUN_GROUP | awk -F= '{print $2}'`
APACHE_PORT=`cat /etc/apache2/ports.conf | grep NameVirtualHost | grep -v "add NameVirtualHost" | awk -F : '{print $2}'`

# Install cronjob
echo "Install cronjob."
echo "# /etc/cron.d/phpminer: crontab fragment for phpminer" > /etc/cron.d/phpminer
echo "#  This will run the cronjob script for phpminer to send notifications and other periodic tasks." >> /etc/cron.d/phpminer
echo "* * * * * $APACHE_USER php -f $PHPMINER_PATH/cron.php" >>  /etc/cron.d/phpminer

# Install apache vhost
echo "Install apache2 vhost."
echo "<VirtualHost *:$APACHE_PORT>
        ServerAdmin webmaster@$DOMAIN
        ServerName $DOMAIN

        DocumentRoot $PHPMINER_PATH
        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory $PHPMINER_PATH/>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
        </Directory>

        ErrorLog \${APACHE_LOG_DIR}/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>" > /etc/apache2/sites-available/phpminer
a2ensite phpminer
a2enmod rewrite
service apache2 restart

echo "chown $APACHE_USER:$APACHE_GROUP $PHPMINER_PATH/config" | sh
sed -i "s/localhost/localhost $DOMAIN/g" /etc/hosts


echo "PHPMiner installation finshed."
echo ""
echo "Now you have to install phpminer_rpcclient on each rig you want to control."
echo ""
echo "If you are running phpminer webinterface from a different machine than your normal pc"
echo "then add the network ip-address (the one you got from DHCP or which you manually configurated) from the phpminer webinterface server to your hostfile"
echo ""
echo "if phpminer webinterface runs on your normal pc then add 127.0.0.1 to your host file"
echo ""
echo "The domain for the hostfile is $DOMAIN"
echo ""
echo "example entry for hostfile is '127.0.0.1 $DOMAIN'"
echo ""
echo "After installing all phpminer_rpclient's you can open phpminer within a browser at http://$DOMAIN:$APACHE_PORT"