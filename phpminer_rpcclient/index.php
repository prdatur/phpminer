<?php
declare(ticks = 1);

set_time_limit (0);
ob_implicit_flush ();

// Load default config.
$config = array();
include dirname(__FILE__) . '/config.dist.php';
$dist_config = $config;

// Load user config.
$config = array();
include dirname(__FILE__) . '/config.php';

// Extend config with not existing dist config's.
foreach ($dist_config AS $k => $v) {
    if (!isset($config[$k])) {
        $config[$k] = $v;
    }
}

define('SITEPATH', dirname(__FILE__));

include dirname(__FILE__) . '/includes/common.php';
include dirname(__FILE__) . '/includes/CGMinerAPI.class.php';
include dirname(__FILE__) . '/includes/RPCClientApi.class.php';
include dirname(__FILE__) . '/includes/RPCClientConnection.class.php';
include dirname(__FILE__) . '/includes/RPCClientServer.class.php';
include dirname(__FILE__) . '/includes/Config.class.php';

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');

// Generate miner array when having old single miner version.
if (!isset($config['miners'])) {
    $config['miners'] = array();
    $config['miners'][$config['miner']] = array(
        'ip' => $config['miner_api_ip'],
        'port' => $config['miner_api_port'],
        'miner' => $config['miner'],
        'miner_binary' => $config['miner_binary'],
        'cgminer_config_path' => $config['cgminer_config_path'],
        'cgminer_path' => $config['cgminer_path'],
        'amd_sdk' => $config['amd_sdk'],
    );
}

foreach ($config['miners'] AS $k => $miner_entry) {
    // When no miner is configurated, fallback to cgminer.
    if (empty($miner_entry['miner'])) {
        $miner_entry['miner'] = 'cgminer';
    }

    // When no miner binaray is configurated or invalid one, fallback to .exe on windows
    if (empty($miner_entry['miner_binary'])) {
        $miner_entry['miner_binary'] = $miner_entry['miner'];
        if ($is_windows) {
            $miner_entry['miner_binary'] .= '.exe';
        }
    }
    $config['miners'][$k] = $miner_entry;
}

$rpc_config = new Config(SITEPATH . '/config.json');
$current_miner = $rpc_config->current_miner;
if (empty($current_miner)) {
    reset($config['miners']);
    $current_miner = key($config['miners']);
}
$rpc_config->current_miner = $current_miner;

$config['rpc_config'] = $rpc_config;

// Make sure array index keys exists.
if (!isset($config['commands'])) {
    $config['commands'] = array();
}

if (!isset($config['commands']['start'])) {
    $config['commands']['start'] = null;
}

if (!isset($config['commands']['stop'])) {
    $config['commands']['stop'] = null;
}

if (!isset($config['commands']['reboot'])) {
    $config['commands']['reboot'] = null;
}

// Create server, we need this here in order to close all sockets when pressing ctrl+c
$rpc_server = new RPCClientServer($config);

// If on linux, we can create a little helper to prevent double starts.
// We also register the handler on pressing ctrl+c / ctrl+d to make sure the lock file will be removed.
if (function_exists('pcntl_signal')) {
    $check_file = '/tmp/phpminer_rpcclient.pid';
    function sig_handler($signo) {
        global $check_file;
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                unlock();
                exit;
        }
    }

    pcntl_signal(SIGTERM, "sig_handler");
    pcntl_signal(SIGINT, "sig_handler");

    if (file_exists($check_file)) {
        exit;
    }

    file_put_contents($check_file, getmypid());
    
    function unlock() {
        global $check_file, $rpc_server;
        $rpc_server->close();
        @unlink($check_file);
    }
}
else {
    function unlock() {
        global $rpc_server;
        $rpc_server->close();
    }
}

// Start server.
$rpc_server->start();

// Just make sure that tmp pid file is removed after script end. (Linux only)
unlock();
