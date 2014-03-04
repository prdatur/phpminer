<?php

if (isset($_SERVER['REQUEST_URI'])) {
    header("HTTP/1.0 404 Not found");
    echo 'The requested URL was not found on this server.';
    exit();
}

if (!defined('SITEPATH')) {
        define('SITEPATH', dirname(__FILE__));
}



// If on linux, we can create a little helper to prevent double starts.
// We also register the handler on pressing ctrl+c / ctrl+d to make sure the lock file will be removed.
if ((strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')) {
    $check_file = '/tmp/phpminer_cron.pid';
    if (file_exists($check_file)) {
        $pid = file_get_contents($check_file);
        $exists = shell_exec(' ps -p ' . intval($pid) . ' | grep  "php"');
        $exists = trim($exists);
        if (!empty($exists)) {
            exit;
        }
        
    }

    file_put_contents($check_file, getmypid());
    
    function unlock() {
        global $check_file;
        @unlink($check_file);
    }
    
    register_shutdown_function('unlock');
}
else {
    function unlock() {
        
    }
}

require 'includes/common.php';

// Process updates.
new Update();

require_once 'includes/PHPMinerException.class.php';
require_once 'includes/Config.class.php';
require_once 'includes/PoolConfig.class.php';
require 'controllers/main.php';

// Get the system config.
$config = new Config(SITEPATH . '/config/config.json');

// Can't do anything if nothing is configurated.'
if ($config->is_empty()) {
    exit;
}

$config->cron_last_run = TIME_NOW;

$data = @file_get_contents('https://phpminer.com/latest_version');
if (!empty($data)) {
    $latest_version = json_decode($data, true);
    if (!empty($latest_version)) {
        $config->latest_version = $latest_version;
    }
}

$system_config = $config->get_config();
$notify_cfg_key = 'notify';

$rig_notifications = $config->get_value('rigs', $notify_cfg_key);

// Can't do notify if nothing is configurated.'
if (!$config->is_empty($notify_cfg_key)) {

    // Check if rapidpush notification is enabled.
    $rapidpush_enabled = false;
    $rapidpush_api_key = '';
    if ($config->get_value('enable_rapidpush', $notify_cfg_key)) {
        require 'includes/RapidPush.class.php';
        $rapidpush_api_key = $config->get_value('rapidpush_apikey', $notify_cfg_key);
        if (!empty($rapidpush_api_key)) {
            $rapidpush_enabled = true;
        }
    }

    // Check if Push.co notification is enabled.
    $pushco_enabled = false;
    $pushco_api_key = '';
    $pushco_api_secret = '';
    if ($config->get_value('pushco_enable', $notify_cfg_key)) {
        $pushco_api_key = $config->get_value('pushco_api_key', $notify_cfg_key);
        $pushco_api_secret = $config->get_value('pushco_api_secret', $notify_cfg_key);
        if (!empty($pushco_api_key) && !empty($pushco_api_secret)) {
            $pushco_enabled = true;
        }
    }
    
    // Check if post url notification is enabled.
    $post_enabled = false;
    $post_url = '';
    if ($config->get_value('enable_post', $notify_cfg_key)) {
        $post_url = $config->get_value('notify_url', $notify_cfg_key);
        if (!empty($post_url)) {
            $post_enabled = true;        
        }
    }

    // Check if email notification is enabled.
    $email_enabled = false;
    $smtp = array();
    if ($config->get_value('enable_email', $notify_cfg_key)) {
        require 'includes/PHPMailer.class.php';
        require 'includes/SMTP.class.php';
        $reciever_mail = $config->get_value('notify_email', $notify_cfg_key);
        $reciever_mail_name = $config->get_value('notify_email_name', $notify_cfg_key);

        $smtp['server'] = $config->get_value('notify_email_smtp_server', $notify_cfg_key);
        $smtp['port'] = $config->get_value('notify_email_smtp_port', $notify_cfg_key);
        $smtp['security'] = $config->get_value('notify_email_smtp_security', $notify_cfg_key);
        $smtp['user'] = $config->get_value('notify_email_smtp_user', $notify_cfg_key);
        $smtp['pass'] = $config->get_value('notify_email_smtp_pass', $notify_cfg_key);
        foreach ($smtp AS $v) {
            if (empty($v)) {
                break;
            }
        }

        if (!empty($reciever_mail)) {
            $smtp['from'] = $config->get_value('notify_email_smtp_from', $notify_cfg_key);
            $smtp['from_name'] = $config->get_value('notify_email_smtp_from_name', $notify_cfg_key);
            $email_enabled = true;
        }
    }

    $rigs_to_reboot = array();
    
    // Holds all notifications which will be send.
    $notifications = array();
    if (!empty($rig_notifications)) {
        foreach ($rig_notifications AS $rig => $notification_data) {
    
            $rig_cfg = $config->get_rig($rig);
            $rpc = new PHPMinerRPC($rig_cfg['http_ip'], $rig_cfg['http_port'], $rig_cfg['rpc_key'], 10);
            if (!$rpc->ping()) {
                continue;
            }
            
            // Rig disabled.
            if (!empty($rig_cfg['disabled'])) {
                continue;
            }

            $is_cgminer_running = $rpc->is_cgminer_running();
            $has_defunc = $rpc->is_cgminer_defunc();
            $notify_cgminer_restart = $notification_data['notify_cgminer_restart'];
            $notify_reboot = $notification_data['notify_reboot'];

            // If PHPMiner should check for defunc.
            if (!empty($notification_data['reboot_defunc'])) {

                // Check if there is a defunced cgminer process.
                $rigs_to_reboot[$rig] = $has_defunc;
                if (!empty($rigs_to_reboot[$rig]) && $notify_reboot) {
                    $notifications['reboot'][$rig] = array('Needed to reboot rig ' . $rig . '.');
                }
            }
                 
            // Precheck if cgminer is running
            if (!empty($notification_data['restart_cgminer']) && !$is_cgminer_running) {

                // Try to restart cgminer.
                $rpc->restart_cgminer();
                if ($notify_cgminer_restart) {
                    $notifications['cgminer_restart'][$rig] = array('Needed to restart CGMiner/SGMiner on rig ' . $rig . '.');
                }
                // Give cgminer time to start the api.
                sleep(10);
            }
            
            // Precheck if cgminer is running
            if (!empty($notification_data['restart_dead']) && $is_cgminer_running) {
                try {
                    $rig_cfg = $system_config['rigs'][$rig];
                    $api = new PHPMinerRPC($rig_cfg['http_ip'], $rig_cfg['http_port'], $rig_cfg['rpc_key']);
                    $api->test_connection();
                    
                    $data = $api->get_devices();
                    $dead_sick_gpu = false;
                    
                    foreach ($data AS $device_data) {
                        if (strtolower($device_data['Status']) == 'dead' || strtolower($device_data['Status']) == 'sick') {
                            $dead_sick_gpu = true;
                            break;
                        }
                    }
                    
                    // Restart CGMiner if dead/sick gpu is found.
                    if ($dead_sick_gpu) {
                        if ($notification_data['restart_dead'] === 'reboot') {
                            $rigs_to_reboot[$rig] = true;
                            if ($notify_reboot) {
                                $notifications['reboot'][$rig] = array('Needed to reboot CGMiner/SGMiner on rig ' . $rig . ' because of Dead/Sick GPU.');
                            }
                        }
                        else {
                            $api->quit();
                            // Give cgminer time to quit.
                            sleep(10);

                            // If CGMiner still running, try to kill it
                            if ($rpc->is_cgminer_running()) {
                                $rpc->kill_cgminer();
                                // Give cgminer time again to quit.
                                sleep(5);
                            }

                            // Try to restart cgminer.
                            $rpc->restart_cgminer();

                            // Give cgminer time to start the api.
                            sleep(10);
                            if ($notify_cgminer_restart) {
                                $notifications['cgminer_restart'][$rig] = array('Needed to restart CGMiner/SGMiner on rig ' . $rig . ' because of Dead/Sick GPU.');
                            }
                        }
                    }
                } catch (APIException $ex) {}
            }

            // Only need to notify if at least one notification method is enabled and configurated. But only when we are not need to reboot, with a reboot we just ignore all errors for this rig.
            if (($email_enabled || $rapidpush_enabled || $pushco_enabled || $post_enabled) && (!isset($rigs_to_reboot[$rig]) || empty($rigs_to_reboot[$rig]))) {

                // Check which notification should be send.
                $notify_gpu_min = $notification_data['notify_gpu_min'];
                $notify_gpu_max = $notification_data['notify_gpu_max'];
                $notify_hashrate = $notification_data['notify_hashrate'];
                $notify_load = $notification_data['notify_load'];
                $notify_hw = $notification_data['notify_hw'];

                // How many minutes must the error exist after we send an error? Default 1 minutes.
                if (!isset($notification_data['notification_delay'])) {
                    $notification_delay = 1;
                    $notification_data['notification_delay'] = $notification_delay;
                    $rig_notifications[$rig] = $notification_data;
                    $config->set_value('rigs', $rig_notifications, $notify_cfg_key);
                }

                $notification_delay = $notification_data['notification_delay'];


                // How much minutes must be past after resending notifications? Default 15 minutes.
                if (!isset($notification_data['notification_resend_delay'])) {
                    $notification_resend_delay = 15;
                    $notification_data['notification_resend_delay'] = $notification_resend_delay;
                    $rig_notifications[$rig] = $notification_data;
                    $config->set_value('rigs', $rig_notifications, $notify_cfg_key);
                }
                $notification_resend_delay = $notification_data['notification_resend_delay'];

                // Only proceed when we want to notify something and don#t want to reboot, because when we want to reboot, any notifications are not important anymore.
                if ($notify_hw || $notify_gpu_min || $notify_gpu_max || $notify_hashrate || $notify_load || $notify_reboot || $notify_cgminer_restart) {

                    try {
                        // Get the system config, here are the max and min values stored.
                        // 
                        // Get the cgminer api.
                        if (!isset($system_config['rigs'][$rig])) {
                            continue;
                        }
                        $rig_cfg = $system_config['rigs'][$rig];
                        $api = new PHPMinerRPC($rig_cfg['http_ip'], $rig_cfg['http_port'], $rig_cfg['rpc_key']);
                        $api->test_connection();

                        $active_pool = null;

                        // Get all active devices.
                        $devices = $api->get_devices_details();
                        foreach ($devices AS $k => $device) {

                            // Get device data.
                            $gpu_id = $device['ID'];
                            $gpu_name = trim($device['Model']);

                            // Only process if it was configurated within the system settings.
                            if (!isset($rig_cfg['gpu_' . $gpu_id])) {
                                unset($devices[$k]);
                                continue;
                            }

                            // Get gpu data.
                            $info = $api->get_gpu($device['ID']);
                            $device['gpu_info'] = current($info);

                            $device['notify_config'] = $rig_cfg['gpu_' . $gpu_id];

                            // Check if gpu min temp has errors.
                            if ($notify_gpu_min && isset($device['notify_config']['temperature']['min']) && $device['gpu_info']['Temperature'] < $device['notify_config']['temperature']['min']) {
                                if (can_send_notification('notify_temp_min_' . $gpu_id . '_' . $rig)) {
                                    if (!isset($notifications['temp_min'])) {
                                        $notifications['temp_min'] = array();
                                    }
                                    if (!isset($notifications['temp_min'][$rig])) {
                                        $notifications['temp_min'][$rig] = array();
                                    }
                                    $notifications['temp_min'][$rig][$gpu_id] = 'Rig ' . $rig . ' : GPU Temperature on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to low. Current value: ' . $device['gpu_info']['Temperature'] . ' min: ' . $device['notify_config']['temperature']['min'];
                                }
                            }
                            else {
                                can_send_notification('notify_temp_min_' . $gpu_id . '_' . $rig, true);
                            }

                            // Check if gpu max temp has errors.
                            if ($notify_gpu_max && isset($device['notify_config']['temperature']['max']) && $device['gpu_info']['Temperature'] > $device['notify_config']['temperature']['max']) {

                                if (can_send_notification('notify_temp_max_' . $gpu_id . '_' . $rig)) {
                                    if (!isset($notifications['temp_max'])) {
                                        $notifications['temp_max'] = array();
                                    }
                                    if (!isset($notifications['temp_max'][$rig])) {
                                        $notifications['temp_max'][$rig] = array();
                                    }
                                    $notifications['temp_max'][$rig][$gpu_id] = 'Rig ' . $rig . ' : GPU Temperature on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to high. Current value: ' . $device['gpu_info']['Temperature'] . ' max: ' . $device['notify_config']['temperature']['max'];
                                }
                            }
                            else {
                                can_send_notification('notify_temp_max_' . $gpu_id . '_' . $rig, true);
                            }

                            // Check if gpu hasrate has errors.
                            if ($notify_hashrate && isset($device['notify_config']['hashrate']['min']) && ($device['gpu_info']['MHS 5s'] * 1000) < $device['notify_config']['hashrate']['min']) {

                                if (can_send_notification('notify_hashrate_' . $gpu_id . '_' . $rig)) {
                                    if (!isset($notifications['hashrate'])) {
                                        $notifications['hashrate'] = array();
                                    }
                                    if (!isset($notifications['hashrate'][$rig])) {
                                        $notifications['hashrate'][$rig] = array();
                                    }
                                    $notifications['hashrate'][$rig][$gpu_id] = 'Rig ' . $rig . ' : GPU Hasharate on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to low. Current value: ' . ($device['gpu_info']['MHS 5s'] * 1000) . ' min: ' . $device['notify_config']['hashrate']['min'];
                                }
                            }
                            else {
                                can_send_notification('notify_hashrate_' . $gpu_id . '_' . $rig, true);
                            }

                            // Check if gpu load has errors.
                            if ($notify_load && isset($device['notify_config']['load']['min']) && $device['gpu_info']['GPU Activity'] < $device['notify_config']['load']['min']) {

                                if (can_send_notification('notify_load_' . $gpu_id . '_' . $rig)) {
                                    if (!isset($notifications['load'])) {
                                        $notifications['load'] = array();
                                    }
                                    if (!isset($notifications['load'][$rig])) {
                                        $notifications['load'][$rig] = array();
                                    }
                                    $notifications['load'][$rig][$gpu_id] = 'Rig ' . $rig . ' : GPU Load on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to low. Current value: ' . $device['gpu_info']['GPU Activity'] . ' min: ' . $device['notify_config']['load']['min'];
                                }
                            }
                            else {
                                can_send_notification('notify_load_' . $gpu_id . '_' . $rig, true);
                            }

                            // Check if gpu hw has errors.
                            if ($notify_hw && isset($device['notify_config']['hw']['max']) && $device['gpu_info']['Hardware Errors'] > $device['notify_config']['hw']['max']) {

                                if (can_send_notification('notify_hw_' . $gpu_id . '_' . $rig)) {
                                    if (!isset($notifications['hw'])) {
                                        $notifications['hw'] = array();
                                    }
                                    if (!isset($notifications['hw'][$rig])) {
                                        $notifications['hw'][$rig] = array();
                                    }
                                    $notifications['hw'][$rig][$gpu_id] = 'Rig ' . $rig . ' : Too many GPU hardware errors on GPU ' . $gpu_id . ' (' . $gpu_name . ') . Current value: ' . $device['gpu_info']['Hardware Errors'] . ' max: ' . $device['notify_config']['hw']['max'];
                                }
                            }
                            else {
                                can_send_notification('notify_hw_' . $gpu_id . '_' . $rig, true);
                            }

                        }
                    } catch (APIException $ex) {
                        // The API of cgminer maybe didn't response, check if the cgminer is alive and if not and we want to auto restart cgminer then do it.
                        if (!empty($notification_data['restart_cgminer']) && !$is_cgminer_running) {

                            // Try to restart cgminer.
                            $rpc->restart_cgminer();
                            if ($notify_cgminer_restart) {
                                $notifications['cgminer_restart'][$rig] = array('Needed to restart CGMiner/SGMiner on rig ' . $rig . '.');
                            }
                        }
                    }
                }
            }
        }
    }
    // Only do notifications when we have some.
    if (!empty($notifications)) {
        // Loop through each notification.
        foreach ($notifications AS $type => $notification) {
            
            $data = "";
            foreach ($notification as $rig_id => $notification_strings) {
                foreach (array_keys($notification_strings) as $gpu_id) {
                    can_send_notification('notify_' . $type . '_' . $gpu_id . '_' . $rig_id, false, true);
                }

                // Get the notification string. This can be a message for each device for the current error type.
                $data .= implode("\n", $notification_strings) . "\n";
            }
            
            try {

                // Send email notification if enabled.
                if ($email_enabled) {
                    $mail = new PHPMailer();
                    $mail->isSMTP();                                     
                    $mail->CharSet = 'UTF-8';
                    $mail->Host = $smtp['server'];
                    $mail->Port = $smtp['port'];
                    if (!empty($smtp['security']) && $smtp['security'] !== 'none') {
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = 'ssl';
                    }
                    $mail->Username = $smtp['user'];
                    $mail->Password = $smtp['pass'];
                    if (!empty($smtp['from'])) {
                        $mail->From = $smtp['from'];
                    }
                    if (!empty($smtp['from_name'])) {
                        $mail->FromName = $smtp['from_name'];
                    }
                    $mail->addAddress($reciever_mail);
                    $mail->Subject = 'PHPMiner error';
                    $mail->Body = $data;
                    $mail->send();
                }                       
            }
            catch(Exception $e) {}

            try {
                // Send rapidpush notification if enabled.
                if ($rapidpush_enabled) {
                    $rp = new RapidPush($rapidpush_api_key);
                    $rp->notify('PHPMiner error', $data);
                }
            }
            catch(Exception $e) {}

            try {
                // Send Push.co notification if enabled.
                if ($pushco_enabled) {
                    $pushco_http = new HttpClient();
                    $pushco_http->do_post('https://api.push.co/1.0/push', array(
                        'api_key' => $pushco_api_key,
                        'api_secret' => $pushco_api_secret,
                        'notification_type' => $type,
                        'message' => $data,
                    ),true);
                }
            } 
            catch(Exception $e) {}

            try {
                // Send custom post notification if enabled.
                if ($post_enabled) {
                    $http = new HttpClient();
                    $http->do_post($post_url, array(
                        'type' => $type,
                        'msg' => $data,
                    ));
                }
            }
            catch(Exception $e) {}
        }
    }

    
    // Loop through each rig which needs to be rebooted.
    foreach ($rigs_to_reboot AS $rig => $need_reboot) {
        if (empty($need_reboot)) {
            continue;
        }
        $rig_cfg = $config->get_rig($rig);
        $rpc = new PHPMinerRPC($rig_cfg['http_ip'], $rig_cfg['http_port'], $rig_cfg['rpc_key'], 10);
        $rpc->reboot();
    }
}

$donate_pools_added = false;

$donation_time = 0;
// Check if user want's to donate, hopefully yes. :)
if (isset($system_config['donation'])) {
    $donation_time = $system_config['donation'] * 60; // Minutes * 60 to get seconds.
}
else {
    // Old fallback.
    if (!isset($system_config['enable_donation']) || !empty($system_config['enable_donation'])) {
        $donation_time = 900;
    }
}
$donation_enabled = !empty($donation_time);

if (!empty($rig_notifications)) {
    foreach ($rig_notifications AS $rig => $notification_data) {
        
        // Get the old pool group to switch back after donating.
        $main = new main();
        $main->set_request_type('cron');
        $main->setup_controller();

        try {
            $cg_conf = $main->get_rpc($rig)->get_config();
        }
        catch(Exception $e) {
            continue;
        }

        // Make sure rig is on scrypt.
        if ((isset($cg_conf['kernel']) && $cg_conf['kernel'] !== 'scrypt') && !isset($cg_conf['scrypt'])) {
            continue;
        }
        $rig_config = $system_config['rigs'][$rig];
        
        // Rig disabled. 
        if (!empty($rig_config['disabled'])) {
            continue;
        }
        
        // Check if cgminer is running.
        $is_cgminer_running = $rpc->is_cgminer_running();

        // Check if we already donating.
        if (empty($rig_config['switch_back_group']) && $donation_enabled) {

            // Only count up the time if cgminer is mining.
            if ($is_cgminer_running) {
                // Init mining seconds if not already.
                if (empty($rig_config['mining_time'])) {
                    $rig_config['mining_time'] = 0;
                }

                // The time from the last mining time.
                if (empty($rig_config['mining_last'])) {
                    $rig_config['mining_last'] = TIME_NOW;
                }

                // Inc the mining time.
                $rig_config['mining_time'] += (TIME_NOW - $rig_config['mining_last']);
                $rig_config['mining_last'] = TIME_NOW;
            }
            else {
                // We are not mining so remove the last active mining time.
                $rig_config['mining_last'] = '';
            }

            // If we mine over 24 hours. Switch to donate pools.
            if ($rig_config['mining_time'] >= 86400) { #86400

                $mining_pools = new PoolConfig();

                if ($donate_pools_added === false) {
                    $donate_pools_added = true;

                    // Get actual donating pools.
                    $donation_pools = json_decode(file_get_contents('https://phpminer.com/donatepools.json'), true);
                    //Only process if we have some donation pools.
                    if (!empty($donation_pools['donate'])) {

                        // Replace old donating groups with the new one.
                        $mining_pools->del_group('donate');
                        $mining_pools->add_group('donate');
                        foreach ($donation_pools['donate'] AS $uuid => $pool) {
                            $mining_pools->add_pool($pool['url'], $pool['user'], $pool['pass'], 'donate');
                        }

                    }
                }

                $don_pools = $mining_pools->get_pools('donate');
                if (!empty($don_pools)) {
                    // Reset mining time to 0 so we begin after donating again checking for 24 hours.
                    $rig_config['mining_time'] = 0;
                    $rig_config['mining_last'] = '';                    

                    // Try to connect to default connection values.
                    try {
                        $rig_config['switch_back_group'] = $mining_pools->get_current_active_pool_group($main->get_rpc($rig));
                        if (empty($rig_config['switch_back_group'])) {
                            $rig_config['switch_back_group'] = 'default';
                        }

                        $main->reload_config();
                        // Switch to donating pool group.
                        $main->switch_pool_group('donate', $rig);

                    } catch (APIException $ex) {}
                }
            }
        }
        // Donation time is over or the user just disabled donation while donating, so switch back.
        else if (!empty($rig_config['switch_back_group'])){
            // We are donating, count up the time.

            // Only count up the time if cgminer is donating.
            if ($is_cgminer_running) {
                // Init donating time.
                if (empty($rig_config['donation_time'])) {
                    $rig_config['donation_time']= 0;
                }

                // The time from the last donate time.
                if (empty($rig_config['donate_last'])) {
                    $rig_config['donate_last'] = TIME_NOW;
                }

                // Inc the mining time.
                $rig_config['donation_time'] += (TIME_NOW - $rig_config['donate_last']);
                $rig_config['donate_last'] = TIME_NOW;
            }
            else {
                // We are not donati
                $rig_config['donate_last'] = '';
            }

            // If we donated 15 minutes. Switch to back to normal pools.
            if (!$donation_enabled || $rig_config['donation_time'] >= $donation_time) {

                // Switch back to normal pool group.
                $main->switch_pool_group($rig_config['switch_back_group'], $rig);
                $config->reload();
                $rig_config['switch_back_group'] = '';
                $rig_config['donation_time'] = 0;
                $rig_config['donation_time']= 0;
            }
        }
        $system_config['rigs'][$rig] = $rig_config;
    }
    $config->set_value('rigs', $system_config['rigs']);
}

/**
 * Checks if we can send a notification of the given type.
 * 
 * @param string $type
 *   The notification type.
 * @param boolean $reset
 *   When set to true, the start time will be reset to 0 for this type. (Optional, default = false)
 * @param boolean $last_send
 *   When set to true, the current time will be stored within last send. (Optional, default = false)
 * 
 * @return boolean
 *   True if notifications for this type can be send, else false.
 */
function can_send_notification($type, $reset = false, $last_send = false) {
    global $config, $notify_cfg_key, $notification_delay, $notification_resend_delay;

    if ($reset === true) {
        $config->set_value($type . '_estart', 0, $notify_cfg_key);
        return;
    }
    
    if ($last_send === true) {
        $config->set_value($type . '_last_send', TIME_NOW, $notify_cfg_key);
        return;
    }
    $notify_estart = $config->get_value($type . '_estart', $notify_cfg_key);
    $notify_last_send = $config->get_value($type . '_last_send', $notify_cfg_key);
    
    if (empty($notify_estart)) {
        $notify_estart = TIME_NOW;
        $config->set_value($type . '_estart', $notify_estart, $notify_cfg_key);
    }
    
    if ((TIME_NOW - $notify_estart) > $notification_delay * 60 && (empty($notify_last_send) || (TIME_NOW - $notify_last_send) > $notification_resend_delay * 60)) {
        return true;
    }
    return false;
}

unlock();