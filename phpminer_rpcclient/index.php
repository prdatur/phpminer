<?php
declare(ticks = 1);

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

include dirname(__FILE__) . '/includes/common.php';
include dirname(__FILE__) . '/includes/CGMinerAPI.class.php';
include dirname(__FILE__) . '/includes/RPCClientApi.class.php';
include dirname(__FILE__) . '/includes/RPCClientConnection.class.php';
include dirname(__FILE__) . '/includes/RPCClientServer.class.php';


// When no miner is configurated, fallback to cgminer.
if (empty($config['miner'])) {
    $config['miner'] = 'cgminer';
}

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');

// When no miner binaray is configurated or invalid one, fallback to .exe on windows
if (empty($config['miner_binary'])) {
    $config['miner_binary'] = $config['miner'];
    if ($is_windows) {
        $config['miner_binary'] .= '.exe';
    }
}

// If on linux, we can create a little helper to prevent double starts.
// We also register the handler on pressing ctrl+c / ctrl+d to make sure the lock file will be removed.
if (function_exists('pcntl_signal')) {
    $check_file = '/tmp/phpminer_rpcclient.pid';
    function sig_handler($signo) {
        global $check_file;
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                unlink($check_file);
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
        global $check_file;
        @unlink($check_file);
    }
}
else {
    function unlock() {
        
    }
}

// Start server.
$rpc_server = new RPCClientServer($config);
$rpc_server->start();

// Just make sure that tmp pid file is removed after script end. (Linux only)
unlock();
