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

// Here you can define the start and stop command.
// This is optional, if you let it empty it will call the following command:
// Stop:
//   Linux:
//     kill -9 {process_id}
//   Windows
//     taskkill /F /PID {process_id}
//
// Start:
//   Linux:
//     #!/bin/bash
//     export GPU_MAX_ALLOC_PERCENT=100;
//     export GPU_USE_SYNC_OBJECTS=1;
//     export DISPLAY=:0;
//     export LD_LIBRARY_PATH={amd_sdk}; # Only if configurated.
//     cd {cgminer_path};
//     screen -d -m -S {miner} ./{miner_binary} -c {cgminer_config_path}; # If {miner_binary} is empty it will use {miner}
//   Windows
//     setx GPU_MAX_ALLOC_PERCENT 100
//     setx GPU_USE_SYNC_OBJECTS 1
//     cd {cgminer_path}
//     {miner_binary} -c {cgminer_config_path}; # If {miner_binary} is empty it will use {miner}.exe
//
// You can include the process id with %pid% within the stop request.
//
// For linux users:
//   You can provide any command which is available under bash
// For windows users:
//   You can provide any command which is available within a .bat file.
//
// Example 1: Interupt instead of kill on linux machine
//
// $config['commands'] = array(
//   'start' => null,
//   'stop' => 'kill -15 %pid%',
// );
//
// Example 2: Use smos/bamt mine start / mine stop
//
// $config['commands'] = array(
//   'start' => 'mine start',
//   'stop' => 'mine stop',
// );
$config['commands'] = array(
    'start' => null,
    'stop' => null,
);

// Here you can define custom commands which you can execute from the webinterface on this rig.
$config['custom_commands'] = array(
    'get_date' => array(
        'title' => 'Get date',
        'command' => 'date',
        'confirmation_required' => false,
        'has_response' => true,
    )
);

/* * ********* CONFIG END ************* */
