<?php
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

function assertCmdExists($msg, $value, $success = 'OK', $error = 'ERROR') {
    assertTrue($msg, !empty($value) && isset($value[0]) && isset($value[0]['Exists']) && $value[0]['Exists'] === 'Y', $success = 'OK', $error = 'ERROR');
}

function assertTrue($msg, $value, $success = 'OK', $error = 'ERROR') {
    log_console($msg . ': ' . (($value === true) ? $success : $error));
    if ($value !== true) {
        log_console('Value: ' . var_export($value, true));
        log_console('Please fix the error above');
        die();
    }
}


function assertOne($msg, $value, $success = 'OK', $error = 'ERROR') {
    assertTrue($msg, $value === 1, $success, $error);
}
function assertIsset($msg, $value, $key, $success = '', $error = 'ERROR') {
    
    if (!isset($value[$key])) {
        log_console($msg . ': ' . $error);
        log_console('Please fix the error above');
        die();
    }
    else {
        if (empty($success)) {
            $success = "OK value = " . $value[$key];
        }
        log_console($msg . ': ' . $success);
    }
}

$rpc_api = new RPCClientApi($config);
$miner_api = new CGMinerAPI($config['miner_api_ip'], $config['miner_api_port']);

assertTrue('Check for provileged access', $miner_api->is_privileged());


$gpu_device_key_check = array
(
    'GPU',
    'Enabled',
    'Status',
    'Temperature',
    'Fan Speed',
    'Fan Percent',
    'GPU Clock',
    'Memory Clock',
    'GPU Voltage',
    'GPU Activity',
    #'Powertune',
    'MHS av',
    'MHS 5s',
    'Accepted',
    'Rejected',
    'Hardware Errors',
    'Utility',
    'Intensity',
    'Last Share Pool',
    #'Last Share Time',
    #'Total MH',
    #'Diff1 Work',
    #'Difficulty Accepted',
    #'Difficulty Rejected',
    #'Last Share Difficulty',
    #'Last Valid Work',
    #'Device Hardware%',
    #'Device Rejected%',
    #'Device Elapsed',
);

$pool_key_check = Array
(
    'POOL',
    'URL',
    'Status',
    'Priority',
    'Quota',
    #'Long Poll',
    #'Getworks',
    #'Accepted',
    #'Rejected',
    #'Works',
    #'Discarded',
    #'Stale',
    #'Get Failures',
    #'Remote Failures',
    #'User',
    #'Last Share Time',
    #'Diff1 Shares',
    #'Proxy Type',
    #'Proxy',
    #'Difficulty Accepted',
    #'Difficulty Rejected',
    #'Difficulty Stale',
    #'Last Share Difficulty',
    #'Has Stratum',
    #'Stratum Active',
    #'Stratum URL',
    #'Has GBT',
    #'Best Share',
    #'Pool Rejected%',
    #'Pool Stale%',
);

$device_details_key_check = Array
(
    'DEVDETAILS',
    'Name',
    'ID',
    #'Driver',
    'Kernel',
    'Model',
    #'Device Path',
);

log_console('');
$devices = $miner_api->get_devices();
assertTrue("GPU's found", !empty($devices), count($devices) . ' found', 'ERROR: ' . count($devices) . ' found');
foreach ($devices AS $index => $gpu_details) {
    log_console('');
    log_console('Verify GPU ' . ($index + 1));
    log_console('');
    log_console('    Verify response keys for command get_devices');
    foreach ($gpu_device_key_check AS $key) {
        assertIsset("    Search for GPU " . $key, $gpu_details, $key);
    }
    
    $gpu = $miner_api->get_gpu($gpu_details['GPU']);
    log_console('');
    assertTrue('    Check if get_gpu is valid', !empty($gpu) && isset($gpu[0]) && !empty($gpu[0]));
    $gpu = $gpu[0];
    log_console('');
    log_console('    Verify response keys for command get_gpu');
    foreach ($gpu_device_key_check AS $key) {
        assertIsset("    Search for GPU " . $key, $gpu, $key);
    }
    
    log_console('');
    assertOne('    Check set fan speed', $miner_api->set_gpufan($gpu_details['GPU'], $gpu_details['Fan Percent']));
    usleep(500);
    assertOne('    Check set intensity', $miner_api->set_gpuintensity($gpu_details['GPU'], $gpu_details['Intensity']));
    usleep(500);
    assertOne('    Check set voltage', $miner_api->set_gpuvddc($gpu_details['GPU'], $gpu_details['GPU Voltage']));
    usleep(500);
    assertOne('    Check set memory clock', $miner_api->set_gpumem($gpu_details['GPU'], $gpu_details['Memory Clock']));
    usleep(500);
    assertOne('    Check set engine clock', $miner_api->set_gpuengine($gpu_details['GPU'], $gpu_details['GPU Clock']));
    usleep(500);
    assertOne('    Check gpu disable', $miner_api->gpudisable($gpu_details['GPU']));
    usleep(500);
    assertOne('    Check gpu enable', $miner_api->gpuenable($gpu_details['GPU']));
    usleep(500);
    log_console('');
    
    
}

$device_details = $miner_api->get_devices_details();
assertTrue("Devices's found", !empty($device_details), count($device_details) . ' found', 'ERROR: ' . count($device_details) . ' found');
log_console('');
foreach ($device_details AS $index => $dev_details) {
    log_console('Verify device ' . ($index + 1));
    foreach ($device_details_key_check AS $key) {
        assertIsset("    Search for device " . $key, $dev_details, $key);
    }
}


log_console('');
$pools = $miner_api->get_pools();
assertTrue("Pools's found", !empty($pools), count($pools) . ' found', 'ERROR: ' . count($pools) . ' found');
log_console('');
foreach ($pools AS $index => $pool_details) {
    log_console('Verify Pool ' . ($index + 1));
    foreach ($pool_key_check AS $key) {
        assertIsset("    Search for Pool " . $key, $pool_details, $key);
    }
}
log_console('');

assertCmdExists("Check if command switchpool exists", $miner_api->check('switchpool'));
assertCmdExists("Check if command removepool exists", $miner_api->check('removepool'));
assertCmdExists("Check if command addpool exists", $miner_api->check('addpool'));
assertOne("Check command zero", $miner_api->zero());
log_console('The given miner api is compatible with PHPMiner and fully functional');
