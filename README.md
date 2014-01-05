Description
===========
PHPMiner is a nice looking web interface for cgminer in conjunction with a graphiccard.
If you running your mining machine under linux, this is probably the system you want.

What does PHPMiner can do?
===========
PHPMiner connections through the API from CGMiner, there you will be able to get the current status of your devices.
Also you can set minitor values for temperature, load and hashrate. PHPMiner is able to check those values periodicly and can send notifications to different systems if 
for example the GPU Temerature is to high.
PHPMiner can also check if CGMiner is running and if not it can automatically try to restart it.
If a GPU Died and a CGMiner "defunc" process exist, PHPMiner can check those issues too and can reboot your machine.
This will prevent you machine from long mining timeouts.
.... but this is not all.
The CGminer API provides also the possibility to overclock your GPU live, so you don't have to set the values in the config and restart cgminer.
You can set the fan speed, gpu engine clock, gpu memory clock, gpu intensity and the gpu voltage (*on Linux it seems setting voltage has no effect*)

Requirements for PHPMiner
============
* Apache2 with enabled mod_rewrite
 * libapache2-mod-php5
* PHP 5.3+
 * php5-curl (for notifications with rapidpush and/or custom post)

The original CGMiner v3.7.2 is required. 
However some API commands are slowing down PHPMiner experience like switching pools. With the original CGMiner, PHPMiner have to wait that each device send a accepted share to the new pool after switching pool groups, else it can not determine that the pool switch succeed.
In a fork of cgminer v3.7.2 I implented some more API Commands including to retrieve the current mining pool directly.
To have the best experience get the forked version and use them instead of the original, I only improved the API commands, the rest works as the original.
Just a notice:
No worry about updates. you will not miss updates from cgminer, because cgminer v3.7.2 is the last versions with scrypt support and the author will not update it, so you just benefit from my repo.
https://github.com/prdatur/cgminer

Requirements for rebooting machine on defunc cgminer process.
===========
If you want to allow PHPMiner to reboot your machine after it detects a defunced cgminer script. 
You either need to run the cron.php as root or the user which runs the cron needs sudo access to reboot without password.

With normal user:

Please install **sudo** (http://www.sudo.ws/sudo/)

With debian/ubuntu, this is normally already installed. if not install it via:

    apt-get install sudo

For this example the user **www-data** will run the cron.

You have to add the following line into: **/etc/sudoers**
Please use the command **visudo** to edit this file. Just type **visudo**.
Then add these lines at the bottom of the file:

    %reboot ALL=(root) NOPASSWD: /sbin/reboot

This will allow all users which are within the group **reboot** to reboot the machine. (Only reboot, not shutdown)

Now you have to add the group **reboot**:

    groupadd reboot

And finally add the user which runs 

    adduser www-data reboot

NOTE: Replace the user **www-data** with your user which runs the php cron.

How to setup.
============

Installing cronjob:

Please add a file named **phpminer** to **/etc/cron.d/** with the following contents:

    # /etc/cron.d/phpminer: crontab fragment for phpminer
    #  This will run the cronjob script for phpminer to send notifications and other periodic tasks.
    * * * * * {user} {/path/to/phpminer}/cron.php


Please replace:

**{/path/to/phpminer}** with the path to your phpminer directory.

**{user}** with the user which runs also apache, normally this is **www-data**

Pre-requirements within the config of CGMiner.

API needs to be enabled:

    "api-listen": true

Please make sure api-allow allows priviledge access from localhost.

    "api-allow": "W:127.0.0.1"

Also make sure the API-Port is default to 4028

    "api-mcast-port": "4028"
    "api-port": "4028"

If you use another port, PHPMiner will ask for it.

Create a new virtual host in apache:

    <VirtualHost *:80>
            ServerAdmin webmaster@localhost
            ServerName {YOUR_DOMAIN}
    
            DocumentRoot {DOCUMENT_ROOT_TO_PHPMINER}
            <Directory />
                    Options FollowSymLinks
                    AllowOverride None
            </Directory>
            <Directory {DOCUMENT_ROOT_TO_PHPMINER}>
                    Options Indexes FollowSymLinks MultiViews
                    AllowOverride All
            </Directory>
    
            ErrorLog ${APACHE_LOG_DIR}/error.log
        
            # Possible values include: debug, info, notice, warn, error, crit,
            # alert, emerg.
            LogLevel warn
    
            CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>

Replace:

{DOCUMENT_ROOT_TO_PHPMINER} - With the path where the index.php of PHPMiner is located.

{YOUR_DOMAIN} - The domain to the machine. If you don't have a domain you can set it to what ever you want, just make sure you edit your local hosts file to point the fantasy domain to the ip-address if the mining machine.

Save the file and enable the vhost.

Now point your browser to http://{YOUR_DOMAIN}
It will try to connect to CGMiner API.
All other required settings are explained within PHPMiner.

License and Author
==================
A license file is included when you download this software from github (https://github.com/prdatur/phpminer)

Warranty
============
This material is provided "as is", with absolutely no warranty expressed or implied. Any use is at your own risk.
Use of the software in such an environment occurs at your own risk. No liability is assumed for damages or losses.
