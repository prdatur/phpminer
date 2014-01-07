<?php

if (isset($_SERVER['REQUEST_URI'])) {
    header("HTTP/1.0 404 Not found");
    echo 'The requested URL was not found on this server.';
    exit();
}

if (!defined('SITEPATH')) {
        define('SITEPATH', dirname(__FILE__));
}
require 'includes/common.php';
require_once 'includes/PHPMinerException.class.php';
require_once 'includes/Config.class.php';
require_once 'includes/PoolConfig.class.php';

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

// Get the notification config.
$notification_config = new Config(SITEPATH . '/config/notify.json');
        
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');

// Can't do notify if nothing is configurated.'
if (!$notification_config->is_empty()) {

    // Check if rapidpush notification is enabled.
    $rapidpush_enabled = false;
    $api_key = '';
    if ($notification_config->get_value('enable_rapidpush')) {
        require 'includes/RapidPush.class.php';
        $api_key = $notification_config->get_value('rapidpush_apikey');
        if (!empty($api_key)) {
            $rapidpush_enabled = true;
        }
    }

    // Check if post url notification is enabled.
    $post_enabled = false;
    $post_url = '';
    if ($notification_config->get_value('enable_post')) {
        $post_url = $notification_config->get_value('notify_url');
        if (!empty($post_url)) {
            $post_url = true;        
        }
    }

    // Check if email notification is enabled.
    $email_enabled = false;
    $smtp = array();
    if ($notification_config->get_value('enable_email')) {
        require 'includes/PHPMailer.class.php';
        require 'includes/SMTP.class.php';
        $reciever_mail = $notification_config->get_value('notify_email');

        $smtp['server'] = $notification_config->get_value('notify_email_smtp_server');
        $smtp['port'] = $notification_config->get_value('notify_email_smtp_port');
        $smtp['security'] = $notification_config->get_value('notify_email_smtp_security');
        $smtp['user'] = $notification_config->get_value('notify_email_smtp_user');
        $smtp['pass'] = $notification_config->get_value('notify_email_smtp_pass');
        foreach ($smtp AS $v) {
            if (empty($v)) {
                break;
            }
        }

        if (!empty($reciever_mail)) {
            $email_enabled = true;
        }
    }

    // Holds all notifications which will be send.
    $notifications = array();

    $need_reboot = '';
    // If PHPMiner should check for defunc.
    if (!empty($notification_config->reboot_defunc)) {

        // Check if there is a defunced cgminer process.
        $need_reboot = trim(shell_exec("ps a | grep cgminer | grep defunc | grep -v grep | grep -v SCREEN | awk '{print $1'}"));

        $notify_reboot = $notification_config->get_value('notify_reboot');
        if (!empty($need_reboot) && $notify_reboot) {
            $notifications['reboot'][0] = 'Needed to reboot mining machine.';
        }
    }
    
    $notify_cgminer_restart = $notification_config->get_value('notify_cgminer_restart');
    
    // Precheck if cgminer is running
    if (!empty($notification_config->restart_cgminer) && !is_cgminer_running()) {

        // Try to restart cgminer.
        CGMinerAPI::start_cgminer($config->get_value('cgminer_config_path'), $notification_config->get_value('cgminer_path'), $notification_config->get_value('cgminer_amd_sdk_path'));
        if ($notify_cgminer_restart) {
            $notifications['cgminer_restart'][0] = 'Needed to restart cgminer.';
        }
        // Give cgminer time to start the api.
        sleep(10);
    }
    

    // Only need to notify if at least one notification method is enabled and configurated.
    if ($email_enabled || $rapidpush_enabled || $post_enabled) {

        // Check which notification should be send.
        $notify_gpu_min = $notification_config->get_value('notify_gpu_min');
        $notify_gpu_max = $notification_config->get_value('notify_gpu_max');
        $notify_hashrate = $notification_config->get_value('notify_hashrate');
        $notify_load = $notification_config->get_value('notify_load');

        // How many minutes must the error exist after we send an error? Default 1 minutes.
        $notification_delay = $notification_config->get_value('notification_delay');
        if (empty($notification_delay)) {
            $notification_delay = 1;
            $notification_config->set_value('notification_delay', $notification_delay);
        }

        // How much minutes must be past after resending notifications? Default 15 minutes.
        $notification_resend_delay = $notification_config->get_value('notification_resend_delay');
        if (empty($notification_resend_delay)) {
            $notification_resend_delay = 15;
            $notification_config->set_value('notification_resend_delay', $notification_resend_delay);
        }

        // Only proceed when we want to notify something and don#t want to reboot, because when we want to reboot, any notifications are not important anymore.
        if (($notify_gpu_min || $notify_gpu_max || $notify_hashrate || $notify_load || $notify_reboot || $notify_cgminer_restart) && empty($need_reboot)) {

            try {
                // Get the system config, here are the max and min values stored.
                $system_config = $config->get_config();            

                // Get the cgminer api.
                $api = new CGMinerAPI($config->remote_ip, $config->remote_port);
                $api->test_connection();
                $active_pool = null;

                // Get all active devices.
                $devices = $api->get_devices_details();
                foreach ($devices AS $k => $device) {

                    // Get device data.
                    $gpu_id = $device['ID'];
                    $gpu_name = trim($device['Model']);

                    // Only process if it was configurated within the system settings.
                    if (!isset($system_config['gpu_' . $gpu_id])) {
                        unset($devices[$k]);
                        continue;
                    }

                    // Get gpu data.
                    $info = $api->get_gpu($device['ID']);
                    $device['gpu_info'] = current($info);

                    $device['notify_config'] = $system_config['gpu_' . $gpu_id];

                    // Check if gpu min temp has errors.
                    if ($notify_gpu_min && isset($device['notify_config']['temperature']['min']) && $device['gpu_info']['Temperature'] < $device['notify_config']['temperature']['min']) {
                        if (can_send_notification('notify_temp_min_' . $gpu_id)) {
                            if (!isset($notifications['temp_min'])) {
                                $notifications['temp_min'] = array();
                            }
                            $notifications['temp_min'][$gpu_id] = 'GPU Temperatur on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to low. Current value: ' . $device['gpu_info']['Temperature'] . ' min: ' . $device['notify_config']['temperature']['min'];
                        }
                    }
                    else {
                        can_send_notification('notify_temp_min_' . $gpu_id, true);
                    }

                    // Check if gpu max temp has errors.
                    if ($notify_gpu_max && isset($device['notify_config']['temperature']['max']) && $device['gpu_info']['Temperature'] > $device['notify_config']['temperature']['max']) {

                        if (can_send_notification('notify_temp_max_' . $gpu_id)) {
                            if (!isset($notifications['temp_max'])) {
                                $notifications['temp_max'] = array();
                            }
                            $notifications['temp_max'][$gpu_id] = 'GPU Temperatur on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to high. Current value: ' . $device['gpu_info']['Temperature'] . ' max: ' . $device['notify_config']['temperature']['max'];
                        }
                    }
                    else {
                        can_send_notification('notify_temp_max_' . $gpu_id, true);
                    }

                    // Check if gpu hasrate has errors.
                    if ($notify_hashrate && isset($device['notify_config']['hashrate']['min']) && ($device['gpu_info']['MHS 5s'] * 1000) < $device['notify_config']['hashrate']['min']) {

                        if (can_send_notification('notify_hashrate_' . $gpu_id)) {
                            if (!isset($notifications['hashrate'])) {
                                $notifications['hashrate'] = array();
                            }
                            $notifications['hashrate'][$gpu_id] = 'GPU Hasharate on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to low. Current value: ' . ($device['gpu_info']['MHS 5s'] * 1000) . ' min: ' . $device['notify_config']['hashrate']['min'];
                        }
                    }
                    else {
                        can_send_notification('notify_hashrate_' . $gpu_id, true);
                    }

                    // Check if gpu load has errors.
                    if ($notify_load && isset($device['notify_config']['load']['min']) && $device['gpu_info']['GPU Activity'] < $device['notify_config']['load']['min']) {

                        if (can_send_notification('notify_load_' . $gpu_id)) {
                            if (!isset($notifications['load'])) {
                                $notifications['load'] = array();
                            }
                            $notifications['load'][$gpu_id] = 'GPU Load on GPU ' . $gpu_id . ' (' . $gpu_name . ') is to low. Current value: ' . $device['gpu_info']['GPU Activity'] . ' min: ' . $device['notify_config']['load']['min'];
                        }
                    }
                    else {
                        can_send_notification('notify_load_' . $gpu_id, true);
                    }

                }
            } catch (APIException $ex) {
                // The API of cgminer maybe didn't response, check if the cgminer is alive and if not and we want to auto restart cgminer then do it.
                if (!empty($notification_config->restart_cgminer) && !is_cgminer_running()) {

                    // Try to restart cgminer.
                    CGMinerAPI::start_cgminer($config->get_value('cgminer_config_path'), $notification_config->get_value('cgminer_path'), $notification_config->get_value('cgminer_amd_sdk_path'));
                    if ($notify_cgminer_restart) {
                        $notifications['cgminer_restart'][0] = 'Needed to restart cgminer.';
                    }
                }
            }
        }
    }
    
    // Only do notifications when we have some.
    if (!empty($notifications)) {
        // Loop through each notification.
        foreach ($notifications AS $type => $notification_strings) {
            foreach (array_keys($notification_strings) as $gpu_id) {
                can_send_notification('notify_' . $type . '_' . $gpu_id, false, true);
            }

            // Get the notification string. This can be a message for each device for the current error type.
            $data = implode("\n", $notification_strings);
            try {

                // Send email notification if enabled.
                if ($email_enabled) {
                    $mail = new PHPMailer();
                    $mail->isSMTP();                                     
                    $mail->CharSet = 'UTF-8';
                    $mail->Host = $smtp['server'];
                    $mail->Port = $smtp['port'];
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = 'ssl';
                    $mail->Username = $smtp['user'];
                    $mail->Password = $smtp['pass'];
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
                    $rp = new RapidPush($api_key);
                    $rp->notify('PHPMiner error', $data);
                }
            }
            catch(Exception $e) {}

            try {
                // Send custom post notification if enabled.
                if ($post_enabled) {
                    $http = new HttpClient();
                    $http->do_get($post_url, array(
                        'type' => $type,
                        'msg' => $data,
                    ));
                }
            }
            catch(Exception $e) {}
        }
    }

    // Check if we need to reboot.
    if (!empty($need_reboot)) {
        if ($is_windows) {
		exec('shutdown -r NOW');
	}
	else {
            $user = trim(shell_exec("ps uh " . getmypid() . " | awk '{print $1'}"));

            // Any time just try to call "reboot" maybe the user can call it.
            exec('shutdown -r NOW');

            // If the user of the cron.php is root, we can call reboot, so don't try sudo fallback.
            if ($user !== 'root') {

                // Call sudo fallback.
                exec('sudo shutdown -r NOW');
            }
        }
    }
}
// Check if user want's to donate, hopefully yes. :)
If (!empty($config->enable_donation)) {
    // Check if cgminer is running.
    $is_cgminer_running = is_cgminer_running();
    
    // Check if we already donating.
    if (empty($config->switch_back_group)) {
        
        
        // Only count up the time if cgminer is mining.
        if ($is_cgminer_running) {
            // Init mining seconds if not already.
            if (empty($config->mining_time)) {
                $config->mining_time = 0;
            }

            // The time from the last mining time.
            if (empty($config->mining_last)) {
                $config->mining_last = TIME_NOW;
            }

            // Inc the mining time.
            $config->mining_time += (TIME_NOW - $config->mining_last);
            $config->mining_last = TIME_NOW;
        }
        else {
            // We are not mining so remove the last active mining time.
            $config->mining_last = '';
        }

        // If we mine over 24 hours. Switch to donate pools.
        if ($config->mining_time >= 86400) {

            // Get actual donating pools.
            $donation_pools = json_decode(file_get_contents('https://phpminer.com/donatepools.json'), true);
            
            //Only process if we have some donation pools.
            if (!empty($donation_pools['donate'])) {
                // Reset mining time to 0 so we begin after donating again checking for 24 hours.
                $config->mining_time = 0;
                $config->mining_last = '';

                // Init the donating time.
                $config->donation_time = 0;

                
                $mining_pools = new PoolConfig();

                // Replace old donating groups with the new one.
                $mining_pools->del_group('donate');
                $mining_pools->add_group('donate');
                foreach ($donation_pools['donate'] AS $uuid => $pool) {
                    $mining_pools->add_pool($pool['url'], $pool['user'], $pool['pass'], 'donate');
                }

                // Try to connect to default connection values.
                try {
                    // Get the old pool group to switch back after donating.
                    require 'controllers/main.php';
                    $main = new main();
                    $main->set_request_type('cron');
                    $main->setup_controller();

                    $config->switch_back_group = $mining_pools->get_current_active_pool_group($main->getCGMinerAPI());
                    if (empty($config->switch_back_group)) {
                        $config->switch_back_group = 'default';
                    }

                    $main->reload_config();
                    // Switch to donating pool group.
                    $main->switch_pool_group('donate');

                } catch (APIException $ex) {}
            }
        }
    }
    else {
        // We are donating, count up the time.
        
        // Only count up the time if cgminer is donating.
        if ($is_cgminer_running) {
            // Init donating time.
            if (empty($config->donation_time)) {
                $config->donation_time = 0;
            }

            // The time from the last donate time.
            if (empty($config->donate_last)) {
                $config->donate_last = TIME_NOW;
            }

            // Inc the mining time.
            $config->donation_time += (TIME_NOW - $config->donate_last);
            $config->donate_last = TIME_NOW;
        }
        else {
            // We are not donating so remove the last active donation time.
            $config->donate_last = '';
        }
        
        // If we donated 15 minutes. Switch to back to normal pools.
        if ($config->donation_time >= 900) {
            
            // Switch back to normal pool group.
            require 'controllers/main.php';
            $main = new main();
            $main->set_request_type('cron');
            $main->setup_controller();
            $main->switch_pool_group($config->switch_back_group);
            $config->reload();
            $config->switch_back_group = '';
        }
    }
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
    global $notification_config, $notification_delay, $notification_resend_delay;
    
    if ($reset === true) {
        $notification_config->set_value($type . '_estart', 0);
        return;
    }
    
    if ($last_send === true) {
        $notification_config->set_value($type . '_last_send', TIME_NOW);
        return;
    }
    $notify_estart = $notification_config->get_value($type . '_estart');
    $notify_last_send = $notification_config->get_value($type . '_last_send');
    
    if (empty($notify_estart)) {
        $notify_estart = TIME_NOW;
        $notification_config->set_value($type . '_estart', $notify_estart);
    }
    
    if ((TIME_NOW - $notify_estart) > $notification_delay * 60 && (empty($notify_last_send) || (TIME_NOW - $notify_last_send) > $notification_resend_delay * 60)) {
        return true;
    }
    return false;
}