#!/bin/sh
if [ "$(id -u)" != "0" ]; then
    echo "This script must be run as root" 1>&2
    exit 1
fi

# Setup required variables.
echo "Setup required variables."
PHPMINER_PATH=`readlink -f $0`
PHPMINER_PATH=`dirname $PHPMINER_PATH`
SERVICE_SCRIPT="$PHPMINER_PATH/phpminer_rpcclient"
PHPMINER_PATH_CONFIG="$PHPMINER_PATH/config.php"

# Get reuired user info
echo "Please enter the user on which cgminer should run. This user need access to start cgminer:"
read USER

USER_EXISTS=`grep "$USER:x:" /etc/passwd`

if [ -n "$USER_EXISTS" ]; then
    echo "Check if user exist: OK"
else
    echo "The provided user does not exists within your system."; 
    exit 0
fi

# Get reuired ip info
POSSIBLE_IPS=$(ip addr |grep "inet" |grep -v "inet6" |cut -d"/" -f1 |cut -d" " -f6)
POSSIBLE_IPS="$POSSIBLE_IPS 0.0.0.0"
echo "Please enter the IP-Address on which the service should listen (Default: 127.0.0.1):"
echo "Possible IP-Address:"
echo $POSSIBLE_IPS
read IP

if [ -n "$IP" ]; then
    echo "Using ip: $IP"
else
    IP="127.0.0.1"
    echo "Using ip: $IP"
fi

echo "Please enter the Port on which the service should listen (Default: 11111):"
read PORT
if [ -n "$PORT" ]; then
    echo "Using port: $PORT"
else
    PORT="11111"
    echo "Using port: $PORT"
fi

echo "Miner, can be cgminer or sgminer (Default: cgminer):"
read MINER
if [ -n "$MINER" ]; then
    echo "Using miner: $MINER"
else
    MINER="cgminer"
    echo "Using miner: $MINER"
fi

echo "Miner binary, this can be left empty if the binary is the same as the miner. For example miner = cgminer, miner_binary = cgminer (Default: empty):"
read MINER_BINARY
if [ -n "$MINER_BINARY" ]; then
    echo "Using miner binary: $MINER_BINARY"
else
    MINER_BINARY=""
    echo "Using miner binary: $MINER_BINARY"
fi

echo "Miner API-IP (Default: 127.0.0.1):"
read MINER_IP
if [ -n "$MINER_IP" ]; then
    echo "Using miner api ip: $MINER_IP"
else
    MINER_IP="127.0.0.1"
    echo "Using miner api ip: $MINER_IP"
fi

echo "Miner API-Port (Default: 4028):"
read MINER_PORT
if [ -n "$MINER_PORT" ]; then
    echo "Using miner api pöort: $MINER_PORT"
else
    MINER_PORT="4028"
    echo "Using miner api ip: $MINER_PORT"
fi

echo "Please enter the the RPC-Key, this key is used to make sure that no other client than phpminer can operate with this service. THIS CAN NOT BE EMPTY"
read RPCKEY
if [ -n "$RPCKEY" ]; then
    echo "Using rpckey: $RPCKEY"
else
    echo "RPC-Key can not be empty"
    exit 0
fi

echo "Please provide the path to cgminer.conf/sgminer.conf. If you don't have a config file yet, provide a directory where '$USER' has read AND write access (Default: /opt/cgminer):"
read CGMINER_PATH
if [ -n "$CGMINER_PATH" ]; then
    echo "Using CGMiner/SGMiner path: $CGMINER_PATH"
else
    CGMINER_PATH="/opt/cgminer"
    echo "Using CGMiner/SGMiner path: $CGMINER_PATH"
fi
CGMINER_CONFIG="$CGMINER_PATH/cgminer.conf"
echo "Using CGMiner/SGMiner config file: $CGMINER_CONFIG"

echo "Please provide the amd sdk path. At some machines this is required to enable overclocking. (Default: empty):"
read AMD_SDK
if [ -n "$AMD_SDK" ]; then
    echo "Using amd sdk: $AMD_SDK"
else
    echo "Will not use amd sdk."
fi

echo "Use alternative service installation method (Use this only if you come from a test reboot after installation and phpminer_rpcclient didn't started) To use alternative method provide any value except: 0, no, n, or false, empty value will use normal method. (Default: no):"
SERVICE_ALTERNATIVE=""
read SERVICE_ALTERNATE
if [ -n "$SERVICE_ALTERNATE" ] && [ "$SERVICE_ALTERNATE" != "no" ] && [ "$SERVICE_ALTERNATE" != "n" ] && [ "$SERVICE_ALTERNATE" != "0" ] && [ "$SERVICE_ALTERNATE" != "false" ]; then
    SERVICE_ALTERNATIVE="1"
    echo "Using alternative service installation method."
else
    echo "Using normal service installation method."
fi

echo "
<?php
/* * ********* CONFIG ***************** */

// Service IP.
// This address is used to bind the service.
// If you provide 0.0.0.0 all interface are bound, this means that the api is connectable at any ip-address on this machine.
// Provide 127.0.0.1 to only allow localhost.
// If your rig is within your local network, provide the ip address which you eather configurated by your self or got from your router per DHCP.
\$config['ip'] = '$IP';

// Service port, change it to your needs, please keep in mind, in Linux ports lower 1000 can only be created by user root.
\$config['port'] = $PORT;

// Miner, can be cgminer or sgminer
\$config['miner'] = '$MINER';

// The miner api ip
\$config['miner_api_ip'] = '$MINER_IP';

// The port of the miner api
\$config['miner_api_port'] = $MINER_PORT;

// Miner binary, this can be left empty if the binary is the same as the miner. For example miner = cgminer, miner_binary = cgminer or on windows cgminer.exe
\$config['miner_binary'] = '$MINER_BINARY';

// RPC Secret key.
\$config['rpc_key'] = '$RPCKEY';

// The path + file where the cgminer.conf is.
// Please make sure that the user which run's this script has the permission to edit this file.
\$config['cgminer_config_path'] = '$CGMINER_CONFIG';

// The path where the cgminer executable is.
// Please make sure that the user which run's this script has the permission to start cgminer.
\$config['cgminer_path'] = '$CGMINER_PATH';

// Path to AMD SDK if available (Normally this is only needed within Linux)
\$config['amd_sdk'] = '$AMD_SDK';

/* * ********* CONFIG END ************* */" > $PHPMINER_PATH_CONFIG;

# Install required software.
echo "Install required software."
apt-get install -y sudo php5-cli php5-mcrypt php5-mhash curl php5-curl screen
apt-get install -y chkconfig 
# Setup sudo entries
echo "Setup sudo entries"
groupadd reboot
echo "adduser $USER reboot" | sh

echo "# PHPMiner RPCClient" >> /etc/sudoers
echo "%reboot ALL=(root) NOPASSWD: /sbin/reboot" >> /etc/sudoers
echo "%reboot ALL=(root) NOPASSWD: /sbin/shutdown" >> /etc/sudoers

# Install cronjob
echo "Install cronjob."
echo "# /etc/cron.d/phpminer_rpcclient: crontab fragment for phpminer_rpcclient" > /etc/cron.d/phpminer_rpcclient
echo "#  This will run the cronjob script for phpminer to send notifications and other periodic tasks." >> /etc/cron.d/phpminer_rpcclient
echo "* * * * * root sh $PHPMINER_PATH/rpcclient_cron.sh" >>  /etc/cron.d/phpminer_rpcclient

# Install service script
echo "Config service script."

SEARCH=$(echo "{/PATH/TO/phpminer_rpcclient}" | sed -e 's/[]\/()$*.^|[]/\\&/g')
REPLACE=$(echo "$PHPMINER_PATH" | sed -e 's/[\/&]/\\&/g')
echo "sed -i -e \"s/$SEARCH/$REPLACE/g\" $SERVICE_SCRIPT" | sh
echo "sed -i -e \"s/{USER}/$USER/g\" $SERVICE_SCRIPT" | sh
echo "cp $SERVICE_SCRIPT /etc/init.d/" | sh
chmod +x /etc/init.d/phpminer_rpcclient
echo "sed -i s/\\\'127.0.0.1\\\'/\\\'$IP\\\'/g $PHPMINER_PATH/config.php" | sh

if [ -n "$SERVICE_ALTERNATIVE" ]; then
    echo "Using alternative service installation method."
    RCDIR=`find /etc/ -iname "rc2.d" | sed s/rc2\.d//g`
    echo "ln -s /etc/init.d/phpminer_rpcclient ${RCDIR}rc2.d/S90phpminer-rpcclient" | sh
    echo "ln -s /etc/init.d/phpminer_rpcclient ${RCDIR}rc3.d/S90phpminer-rpcclient" | sh
    echo "ln -s /etc/init.d/phpminer_rpcclient ${RCDIR}rc4.d/S90phpminer-rpcclient" | sh
    echo "ln -s /etc/init.d/phpminer_rpcclient ${RCDIR}rc5.d/S90phpminer-rpcclient" | sh

else
   echo "Install service script for autostart."
    update-rc.d phpminer_rpcclient defaults 98

    echo "Try installing phpminer rpc service with new debian method."
    insserv phpminer_rpcclient

    echo "Try installing phpminer rpc service with old debian method."
    chkconfig phpminer_rpcclient on
fi

echo "Starting phpminer rpc client"
/etc/init.d/phpminer_rpcclient start


echo "PHPMiner rig installation finshed."
echo "Please reboot the rig now. After rebooting wait 1 minute, then type 'ps auxf | grep phpminer | grep -v grep' and check if phpminer_rpcclient is started successfully (one line should appear)"
echo "If it doesn't exist, please re-run the setup script and choose 'yes' when it asks for 'Use alternative service installation method ...'";
