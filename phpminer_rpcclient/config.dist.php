<?php
/* * ********* CONFIG ***************** */

// Service IP.
// This address is used to bind the service.
// If you provide 0.0.0.0 all interface are bound, this means that the api is connectable at any ip-address on this machine.
// Provide 127.0.0.1 to only allow localhost.
// If your rig is within your local network, provide the ip address which you eather configurated by your self or got from your router per DHCP.
$config['ip'] = '127.0.0.1';

// Service port, change it to your needs, please keep in mind, in Linux ports lower 1000 can only be created by user root.
$config['port'] = 11111;

// Miner, can be cgminer or sgminer
$config['miner'] = 'cgminer';

// The miner api ip
$config['miner_api_ip'] = '127.0.0.1';

// The port of the miner api
$config['miner_api_port'] = '4028';

// Miner binary, this can be left empty if the binary is the same as the miner. For example miner = cgminer, miner_binary = cgminer or on windows cgminer.exe
$config['miner_binary'] = '';

// RPC Secret key.
$config['rpc_key'] = '3_Kebju-55Xn-EigZb';

// The path + file where the cgminer.conf is.
// Please make sure that the user which run's this script has the permission to edit this file.
$config['cgminer_config_path'] = '/opt/cgminer/cgminer.conf';

// The path where the cgminer executable is.
// Please make sure that the user which run's this script has the permission to start cgminer.
$config['cgminer_path'] = '/opt/cgminer';

// Path to AMD SDK if available (Normally this is only needed within Linux)
$config['amd_sdk'] = '';

/* * ********* CONFIG END ************* */
