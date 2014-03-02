    Description
===========
PHPMiner is a nice looking web interface for cgminer/sgminer in conjunction with a graphiccard.
If you running your mining machine under Linux or Windows, this is probably the system you want.

PHPMiner has multi rig support, so you can add all your rig's to PHPMiner and controll them via one interface.

How does it work?
===========
PHPMiner connects through the API from CGMiner/SGMiner, there you will be able to get the current status of your devices.
Also you can set monitor values for temperature, load and hashrate. PHPMiner is able to check those values periodicly and can send notifications to different systems if 
for example the GPU Temerature is too high.
PHPMiner can also check if CGMiner/SGMiner is running and if not it can automatically try to restart it.
If a GPU Died and a CGMiner "defunc" process exist, PHPMiner can check those issues too and can reboot your machine.
This will prevent you machine from long mining timeouts.
.... but this is not all.
The CGminer/SGMiner API provides also the possibility to overclock your GPU live, so you don't have to set the values in the config and restart CGMiner/SGMiner.
You can set the fan speed, gpu engine clock, gpu memory clock, gpu intensity and the gpu voltage (*on Linux it seems setting voltage has no effect*)

Quick install for debian based linux system
============
I have provided 2 shell scripts which installs phpminer and/or phpminer_rpcclient on your machines.

The machine on which phpminer webinterface should run type:

    sh linux_setup.sh

After successfull installation copy the directory **phpminer_rpcclient** to all your rigs and on that rig type:

    sh linux_rpcclient_setup.sh

Requirements for PHPMiner
============
* Apache2 with enabled mod_rewrite
 * libapache2-mod-php5
* php5-cli
* PHP 5.3+
 * php5-curl (for notifications with rapidpush and/or custom post)
 * php5-json
* mysql-server

The original CGMiner v3.7.2 or SGMiner >= 4.1.0 is required. 
However some API commands are slowing down PHPMiner experience like switching pools. With the original CGMiner/SGMiner, PHPMiner have to wait that each device send a accepted share to the new pool after switching pool groups, else it can not determine that the pool switch succeed.
In a fork of cgminer v3.7.2 I implented some more API Commands including to retrieve the current mining pool directly.
To have the best experience get the forked version and use them instead of the original, I only improved the API commands, the rest works as the original.
Just a notice:
No worry about updates. you will not miss updates from cgminer, because cgminer v3.7.2 is the last versions with scrypt support and the author will not update it, so you just benefit from my repo.
https://github.com/prdatur/cgminer

Requirements for rebooting machine on defunc CGMiner/SGMiner process.
===========
If you want to allow PHPMiner to reboot your machine after it detects a defunced CGMiner/SGMiner script. 
You either need to run the phpminer_rpcclient/index.php as root or the user which runs the phpminer_rcpclient needs sudo access to reboot without password.

With normal user:

Please install **sudo** (http://www.sudo.ws/sudo/)

With debian/ubuntu, this is normally already installed. if not install it via:

    apt-get install sudo

For this example the user **www-data** will run the cron.

You have to add the following line into: **/etc/sudoers**
Please use the command **visudo** to edit this file. Just type **visudo**.
Then add these lines at the bottom of the file:

    %reboot ALL=(root) NOPASSWD: /sbin/reboot
    %reboot ALL=(root) NOPASSWD: /sbin/shutdown

This will allow all users which are within the group **reboot** to reboot the machine.

Now you have to add the group **reboot**:

    groupadd reboot

And finally add the user which runs 

    adduser www-data reboot

NOTE: Replace the user **www-data** with your user which runs the phpminer_rpcclient.

Donations
============
Donations are welcome

BTC: 17dbqTnhn2qPLdSjaT7w2SkPLnCSMH4xFh

LTC: Lh5sjSpN88N3PeG3vyQD9h6bz2jV4tdoke

However you can also let the setting "Enable donation" enabled.

What does this mean?

To support further updates and help to improve PHPMiner, I decided to implement an auto donation system **which you can disable at any time**. 

So what is auto-donation? 

PHPMiner will detect when your workers have mined 24 hours, then PHPMiner will switch to donation pools where your workers will mine for me for 15 Minuntes. 

After this time PHPMiner will switch back to your previous pool group. 15 Minutes within 24 Hours are just 1% if the hole mining time. 

So this will not have a real effect of your profit. 

It's just a little help to let me know that you want updates in the future and this tells me that my work with PHPMiner was useful.

How to setup.
============

## Creating database.

Please create a database and a user which has access to read / write data and create tables.
After you have created them, copy the file **config/config.php.dist** to **config/config.php** and fill in your database credentials.

## Installing cronjob


### Linux
Please add a file named **phpminer** to **/etc/cron.d/** with the following contents:

    # /etc/cron.d/phpminer: crontab fragment for phpminer
    #  This will run the cronjob script for phpminer to send notifications and other periodic tasks.
    * * * * * {user} php -f {/path/to/phpminer}/cron.php

Please replace:

**{/path/to/phpminer}** with the path to your phpminer directory.

**{user}** with the user which runs also apache, normally this is **www-data**

### Windows

You can start the above command

    php -f {/path/to/phpminer}/cron.php

with sheduled tasks, or you can use cronjob software nncron:

http://www.nncron.ru/ - lightweight and free


## Pre-requirements within the config of CGMiner/SGMiner.

API needs to be enabled:

    "api-listen": true

Please make sure api-allow allows priviledge access from the host which act's as the "master" (on which you host phpminer website). (The below example would allow the complete subnet from 10.10.10.1 - 10.10.10.254)

    "api-allow": "W:10.10.10.0/24"

Also make sure the API-Port is configurated (default is: 4028)

    "api-port": "4028"

If you use another port, PHPMiner will ask for it.

## For nice looking urls

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

**{DOCUMENT_ROOT_TO_PHPMINER}** - With the path where the index.php of PHPMiner is located.

**{YOUR_DOMAIN}** - The domain to the machine. If you don't have a domain you can set it to what ever you want, just make sure you edit your local hosts file to point the fantasy domain to the ip-address if the mining machine.

Save the file and enable the vhost.

## Required setup's

Copy the directory **phpminer_rpcclient** to **each** mining rig which you want to connect.
Open the file **config.php** within this directory on each rig and change the config to your needs.

### Running RPC Client on system start

#### Linux

##### Debian based

Within the phpminer_rpcclient directory, there exists a file **phpminer_rpc**.

Open this file and replace:

    **{USER}** - With a user which can reboot your machine (config from above) and can start CGMiner/SGMiner
    **{/PATH/TO/phpminer_rpcclient}** to the path where you copied the phpminer_rpcclient.

Now copy the file into **/etc/init.d/**

Make it executeable

    chmod +x /etc/init.d/phpminer_rpcclient

Then type

    sudo update-rc.d phpminer_rpcclient defaults 98

If this output something like 

    update-rc.d: using dependency based boot sequencing

then type 
    
    insserv phpminer_rpcclient

Since debian 6.0 **insserv** is required to use. And just a hint. **bamt** and also **smos* run on Debian 6.x so **insserv** is the command you need.
To find out on which debian version you are type: (This works on ubuntu too)

    lsb_release -a


Finally start the service the first time

    service phpminer_rpcclient start

##### Other

You should get the information for your distribution to run a script on system startup.

The command which needs to be executed is:

   php -f {/PATH/TO/phpminer_rpcclient}/index.php

Replace **{/PATH/TO/phpminer_rpcclient}** to the path where you copied the phpminer_rpcclient.

### Installing RPC-Client cronjob 

The cronjob will make sure that phpminer_rpcclient is up and running.

#### Linux
Please add a file named **phpminer_rpcclient** to **/etc/cron.d/** with the following contents:

    # /etc/cron.d/phpminer_rpcclient: crontab fragment for phpminer_rpcclient
    #  This will run the cronjob script for phpminer to send notifications and other periodic tasks.
    * * * * * root sh {/path/to/phpminer_rpcclient}/rpcclient_cron.sh

Please replace:

**{/path/to/phpminer_rpcclient}** with the path to your phpminer rpcclient directory.

### Windows

For now this check script only exist for linux, if any one want to provide a script for Windows, let me know.

### Finish configuration

Make config directory writeable:

    chown {user}:{group} {DOCUMENT_ROOT_TO_PHPMINER}/config

Replace:

    **{user}** - The user of the webserver, this is normally **www-data**
    **{group}** - The group of the webserver, this is normally **www-data**
    **{DOCUMENT_ROOT_TO_PHPMINER}** - With the path where the index.php of PHPMiner is located.

Now point your browser to http://{YOUR_DOMAIN} or if you have phpminer in a subdirectory browse to http://{YOUR_DOMAIN}/{SUB_DIRECTORY}

All other required settings are explained within PHPMiner.

License and Author
==================
A license file is included when you download this software from github (https://github.com/prdatur/phpminer)

Warranty
============
This material is provided "as is", with absolutely no warranty expressed or implied. Any use is at your own risk.
Use of the software in such an environment occurs at your own risk. No liability is assumed for damages or losses.
