<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class main extends Controller {

    public function pools() {
        $this->load_pool_config();
        if ($this->pool_config->is_empty()) {
            $this->assign('unconfigured_pools', $this->api->get_pools());
        }
    }

    /**
     * Ajax request to save the cgminer.conf with the current settings.
     */
    public function save_config() {
        
        $cgminer_config = new Config($this->config->cgminer_config_path);
        if (!$cgminer_config->is_writeable()) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'The cgminer config file <b>' . $this->config->cgminer_config_path . '</b> is not writeable.');
        }
        
        foreach ($this->config->cgminer_conf as $key => $val) {
            $cgminer_config->set_value($key, $val);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    /**
     * Action: settings
     * Will display the page to configurate the system settings.
     */
    public function settings() {
        $this->assign('config', $this->config->get_config());
    }
    
    /**
     * Ajax request to save new configuration settings.
     */
    public function save_settings() {
        $params = new ParamStruct();
        $params->add_required_param('settings', PDT_ARR);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        foreach ($params->settings AS $key => $val) {
            $this->config->set_value($key, $val);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    /**
     * Ajax request to save new cgminer configuration settings.
     */
    public function save_cgminer_settings() {
        $params = new ParamStruct();
        $params->add_required_param('settings', PDT_ARR);

        $params->fill();
        
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        foreach(array('vectors', 'worksize', 'kernel', 'lookup-gap', 'thread-concurrency', 'shaders', 'temp-cutoff', 'temp-overheat', 'temp-target', 'expire', 'gpu-dyninterval', 'gpu-platform', 'gpu-threads', 'log', 'no-pool-disable', 'queue', 'scan-time', 'scrypt', 'temp-hysteresis', 'shares', 'kernel-path') AS $key) {
            if (isset($params->settings[$key])) {
                $conf[$key] = $params->settings[$key];
            }
        }
        $this->config->cgminer_conf = $conf;
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    /**
     * Ajax request to add a new pool.
     */
    public function add_pool() {
        $params = new ParamStruct();
        $params->add_required_param('url', PDT_STRING);
        $params->add_required_param('user', PDT_STRING);
        $params->add_required_param('pass', PDT_STRING);
        $params->add_param('group', PDT_STRING, 'default');

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, array(
                'url' => $params->url,
                'user' => $params->user,
            ));
        }

        $this->load_pool_config();
        $result = $this->pool_config->check_pool($params->url, $params->user, $params->pass);
        if ($result !== true) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, array(
                'url' => $params->url,
                'user' => $params->user,
                    ), true, $result);
        }
        $this->load_pool_config();
        $this->pool_config->add_pool($params->url, $params->user, $params->pass, $params->group);
        AjaxModul::return_code(AjaxModul::SUCCESS, array(
            'url' => $params->url,
            'user' => $params->user,
        ));
    }

    /**
     * Action: Switch to the given pool group
     */
    public function switch_pool_group($pool_group = '') {
        
        $params = new ParamStruct();
        $params->add_required_param('group', PDT_STRING);

        if (!empty($pool_group)) {
            $params->fill(array('group' => $pool_group));
        }
        else {
            $params->fill();
        }
        
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        // Makre sure group exists.
        $this->load_pool_config();
        if (!$this->pool_config->group_exists($params->group)) {
            if (!empty($pool_group)) {
                return false;
            }
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'You provided an invalid group');
        }

        $cgminer_config = new Config($this->config->cgminer_config_path);
        if (!$cgminer_config->is_writeable()) {
            if (!empty($pool_group)) {
                return false;
            }
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'The cgminer config file <b>' . $this->config->cgminer_config_path . '</b> is not writeable.');
        }
        
        // Loop through all pools which are within the group, those will be added old ones will be removed.
        $first_add = true;

        // First make sure after switching to the first added pool, it will not switch back when the pool is dead.
        $this->api->set_failover_only(false);
        $pool_quota_reset = 0;
        $cgminer_config_pools = array();
        $pool_no = 0;
        foreach ($this->pool_config->get_pools($params->group) AS $pool) {
            
            if (!isset($pool['quota'])) {
                $pool['quota'] = 1;
            }
            $pool_quota = $pool['quota'];
            
            $pool_no++;
            
            // Add the pool.
            $this->api->addpool($pool['url'], $pool['user'], $pool['pass'], $pool['quota']);
            $cgminer_config_pools[] = array(
                'url' => $pool['url'],
                'user' => $pool['user'],
                'pass' => $pool['pass'],
            );

            // When we have added our first pool, we have to switch to this pool, wait until all devices are connected to this and then remove the old ones.
            // After that we can add the other pools within the group.
            if ($first_add === true) {

                $first_add = false;

                // Get all current active pools.
                $cgminer_pools = $this->api->get_pools();

                // Find the currently added pool and switch it to active.
                foreach ($cgminer_pools AS $cgminer_pool) {
                    if ($pool['url'] == $cgminer_pool['URL'] && $pool['user'] == $cgminer_pool['User']) {
                        $this->api->switchpool($cgminer_pool['POOL']);
                        
                        // Also we need to set the pool quota really high, so we can be sure that the pool is still active after the wait time.
                        $this->api->set_poolquota($cgminer_pool['POOL'], 100000);
                        $pool_quota_reset = $cgminer_pool['POOL'];
                        break;
                    }
                }

                // Get all configurated pools
                $check_pools = $this->api->get_pools();

                // Wait until all gpu's have switched to pool.
                $gpus = $this->api->get_devices();
                // Loop until all gpus are empty. gpu will be removed if we are mining add the first added pool.
                while (!empty($gpus)) {

                    // Loop through each gpu.
                    foreach ($gpus AS $i => $gpu) {
                        if (isset($gpu['Current Pool'])) {
                            $pool_check = $gpu['Current Pool'];
                        }
                        else {
                            $pool_check = $gpu['Last Share Pool'];
                        }
                        // Find the correct pool which was the last share pool of the gpu. or if we have advanced api commands get the current pool directly.
                        foreach ($check_pools AS $chk_pool) {
                            if ($chk_pool['POOL'] == $pool_check) {
                                // Mark found gpu pool.
                                $gpu_pool = $chk_pool;
                                break;
                            }
                        }

                        // Check if the last share pool details are the same as the currently added one. If so remove the current gpu from the gpu-list.
                        if ($pool['url'] == $gpu_pool['URL'] && $pool['user'] == $gpu_pool['User']) {
                            unset($gpus[$i]);
                        }
                    }

                    // Only re-request the current gpu list details if we have some gpu's, because if we have no gpu's left in the list we are finished and can process further.
                    if (!empty($gpus)) {
                        // Just to get a little bit of time to let gpu set the new last share pool.
                        usleep(250);

                        // Get fresh updated gpu list.
                        $gpus = $this->api->get_devices();
                    }
                }
                
                // Reset quota
                $this->api->set_poolquota($pool_quota_reset, $pool_quota);
                
                $c = 0;
                // Remove the old pools.
                while (count($this->api->get_pools()) > 1) {
                    // Just make sure that we are not within an endless loop.
                    if ($c++ >= 100) {
                        break;
                    }

                    // Find the first pool which is not the current added one which is active.
                    foreach ($this->api->get_pools() AS $cgminer_pool) {
                        // Check if the current pool is not the one we added previous.
                        if ($pool['url'] != $cgminer_pool['URL'] || $pool['user'] != $cgminer_pool['User']) {
                            // Remove old group.
                            try {
                                $this->api->removepool($cgminer_pool['POOL']);
                            } catch (APIRequestException $e) {
                                
                            }
                            break;
                        }
                    }
                }
            }
            else {
                $this->api->set_poolquota($pool_no, $pool_quota);
            }
        }

        // Make sure failover will be resetted.
        $this->api->set_failover_only(true);
        $this->config->set_cgminer_value($this->api, 'pools', $cgminer_config_pools);
        if ($this->has_advanced_api) {
            $this->api->set_poolstrategy($this->pool_config->get_strategy($params->group), $this->pool_config->get_period($params->group));
            $this->config->del_cgminer_value($this->api, 'rotate');
            $this->config->del_cgminer_value($this->api, 'balance');
            $this->config->del_cgminer_value($this->api, 'load-balance');
            $this->config->del_cgminer_value($this->api, 'round-robin');
            if ($this->pool_config->get_strategy($params->group) !== 0) {
                switch ($this->pool_config->get_strategy($params->group)) {
                    case 1:
                        $this->config->set_cgminer_value($this->api, 'round-robin', true);
                        break;
                    case 2:
                        $this->config->set_cgminer_value($this->api, 'rotate', $this->pool_config->get_period($params->group));
                        break;
                    case 3:
                        $this->config->set_cgminer_value($this->api, 'load-balance', true);
                        break;
                    case 4:
                        $this->config->set_cgminer_value($this->api, 'balance', true);
                        break;
                }
            }
        }
        // While switching pool groups we directly need to write to cgminer config file.
        $cgminer_config->set_value('pools', $cgminer_config_pools);
        if (!empty($pool_group)) {
            return true;
        }
        AjaxModul::return_code(AjaxModul::SUCCESS, $this->pool_config->get_pools($params->group));
    }

    /**
     * Main init function where the devices are listed-
     */
    public function init() {
        $active_pool = null;
        
        // Get pools
        $pools = $this->api->get_pools();
        
        $devices = $this->api->get_devices_details();
        foreach ($devices AS &$device) {
            $info = $this->api->get_gpu($device['ID']);
            $info = reset($info);

            $device['gpu_info'] = $info;
            // Check if we have our own cgminer fork with advanced api.
            if (isset($info['Current Pool'])) {
                $pool_check = $info['Current Pool'];
            }
            else {
                $pool_check = $info['Last Share Pool'];
            }
            
            foreach ($pools AS $pool) {

                if ($pool['POOL'] == $pool_check) {
                    if (!empty($pool_check)) {
                        $active_pool = $pool;
                    }
                    $device['pool'] = $pool;
                }
            }
            
            if (!empty($this->config->switch_back_group)) {
                $device['donating'] = ceil((900 - $this->config->donation_time) / 60);
            }
        }
        $this->load_pool_config();

        // Determine which pool group we are currently using.        
        $current_active_group = $this->pool_config->get_current_active_pool_group($this->api);

        $cfg_pools = $this->pool_config->get_pools($current_active_group);

        // Get the pool uuid which is currently in use.
        $active_pool_uuid = $this->pool_config->get_pool_uuid($active_pool['URL'], $active_pool['User']);
        $this->assign('pool_groups', $this->pool_config->get_groups());
        $this->assign('current_group', $current_active_group);
        $this->assign('donating', !empty($this->config->switch_back_group));
        $this->js_config('device_list', $devices);
        $this->js_config('pools', $cfg_pools);
        $this->js_config('active_pool_group', $current_active_group);
        $this->js_config('active_pool_uuid', $active_pool_uuid);
        $this->js_config('config', $this->config->get_config());
    }
    
    /**
     * Ajax request to retrieve all current configurated pools within cgminer.
     */
    public function get_cgminer_pools() {
        try {
            AjaxModul::return_code(AjaxModul::SUCCESS, $this->api->get_pools());
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }
    
    /**
     * Ajax request to remove a pool from cgminer.
     */
    public function remove_pool_from_cgminer() {
        $params = new ParamStruct();
        $params->add_required_param('pool', PDT_INT);
        
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        try {
            $this->api->removepool($params->pool);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $e) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
        }
           
    }
    
    /**
     * Ajax request page which will auto generate or override pool group "cgminer" with the current cgminer pools.
     */
    public function fix_pool() {
        $params = new ParamStruct();
        $params->add_required_param('pools', PDT_ARR);
        
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        $this->load_pool_config();
        $this->pool_config->del_group('cgminer');
        $results = array();
        foreach ($params->pools AS $pool) {
            if (isset($pool['valid'])) {
                $pool['valid'] = intval($pool['valid']);
            }
            if ($pool['valid'] === 1) {
                continue;
            }
            if (empty($pool['valid']) || $pool['valid'] !== 1) {
                
                $pool_result = $this->pool_config->check_pool($pool['url'], $pool['user'], $pool['pass']);
                if ($pool_result === true) {
                    $pool['valid'] = true;
                }
                else {
                    $results[$pool['url'] . '|' . $pool['user']] = $pool_result;
                }
            }
            if (!empty($pool['valid'])) {
                $this->pool_config->add_pool($pool['url'], $pool['user'], $pool['pass'], 'cgminer');
                $results[$pool['url'] . '|' . $pool['user']] = true;
            }
        }
        AjaxModul::return_code(AjaxModul::SUCCESS, $results);
    }
    
    /**
     * This page will display a nice merge tool to handle pool missconfigurations.
     */
    public function fix_pool_manual() {
        $this->load_pool_config();
        $cgminer_pools = $this->api->get_pools();
        foreach ($cgminer_pools AS $k=> $pool) {
            $cgminer_pools[$k]['uuid'] = $this->pool_config->get_pool_uuid($pool['URL'], $pool['User']);
        }
        $this->assign('cgminer_pools', $cgminer_pools);
        $this->assign('cfg_groups', $this->pool_config->get_groups());
        $this->js_config('cfg_groups', $this->pool_config->get_config());
    }
    
    /**
     * Ajax action to handle the actions on manual pool config fix.
     */
    public function fix_pool_manual_action() {
        $params = new ParamStruct();
        $params->add_required_param('type', PDT_INT);
        $params->add_required_param('url', PDT_STRING);
        $params->add_required_param('user', PDT_STRING);
        $params->add_param('pass', PDT_STRING);
        $params->add_param('group', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        $this->load_pool_config();
        try {
            switch($params->type) {
                // Remove from cgminer.
                case 1:
                    $pool_id = null;
                    $uuid = $this->pool_config->get_pool_uuid($params->url, $params->user);
                    foreach ($this->api->get_pools() AS $pool) {
                        if ($this->pool_config->get_pool_uuid($pool['URL'], $pool['User']) === $uuid) {
                            $pool_id = $pool['POOL'];
                            break;
                        }
                    }

                    if ($pool_id === null) {
                        AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'The given pool does not exist.');
                    }
                    $this->api->removepool($pool_id);
                    break;

                // Remove from group.
                case 2:
                    $this->pool_config->remove_pool($params->url, $params->user, $params->group);
                    break;

                // Add to cgminer.
                case 3:
                    $this->api->addpool($params->url, $params->user, $params->pass);
                    break;

                // Add to pool.
                case 4:
                    $check_result = $this->pool_config->check_pool($params->url, $params->user, $params->pass);
                    if ($check_result) {
                        $this->pool_config->add_pool($params->url, $params->user, $params->pass, $params->group);
                    }
                    else {
                        AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $check_result);
                    }
                    break;
            }
        } catch (APIRequestException $e) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    /**
     * Switch all devices to the given pool.
     * 
     * @post string $pool_uuid
     *   The pool uuid to switch
     */
    public function switch_pool() {
        $params = new ParamStruct();
        $params->add_required_param('pool', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $this->load_pool_config();
        $sorted_pools = array();
        foreach ($this->api->get_pools() AS $pool) {
            $sorted_pools[$this->pool_config->get_pool_uuid($pool['URL'], $pool['User'])] = $pool;
        }
        try {
            $this->api->switchpool($sorted_pools[$params->pool]['POOL']);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $e) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
        }
    }

    /**
     * Returns all configurated pools within the given group.
     * 
     * @param string $group
     *   The pool group to retrieve.
     */
    public function get_pools($group) {
        $this->load_pool_config();
        AjaxModul::return_code(AjaxModul::SUCCESS, $this->pool_config->get_pools($group));
    }

    /**
     * Ajax request to retrieve current configurated devices within cgminer.
     */
    public function get_device_list() {
        $devices = $this->api->get_devices_details();

        // Get pools
        $pools = $this->api->get_pools();
        foreach ($devices AS &$device) {
            $info = $this->api->get_gpu($device['ID']);
            $info = reset($info);

            $device['gpu_info'] = $info;
            
            if (isset($info['Current Pool'])) {
                $pool_check = $info['Current Pool'];
            }
            else {
                $pool_check = $info['Last Share Pool'];
            }
            
            foreach ($pools AS $pool) {
                if ($pool['POOL'] == $pool_check) {
                    $device['pool'] = $pool;
                }
            }
            
            if (!empty($this->config->switch_back_group)) {
                $device['donating'] = ceil((900 - $this->config->donation_time) / 60);
            }
        }

        AjaxModul::return_code(AjaxModul::SUCCESS, $devices);
    }

    /**
     * This method will be called when we disconnected from the cgminer api.
     * It will try to re-connect.
     * If connection succeed a ajax success will be returned, else an error.
     * The javascript will then reload the page on success.
     */
    public function connection_reconnect() {
        #AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
        try {
            $this->api = new CGMinerAPI($this->config->remote_port);
            $this->api->test_connection();
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    /**
     * Ajax request to check a connection to cgminer.
     */
    public function check_connection() {
        $params = new ParamStruct();
        $params->add_required_param('port', PDT_INT);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $api = new CGMinerAPI($params->port);
        try {
            $version = $api->test_connection();

            $cgminer_version = explode(".", $version['CGMiner']);
            $api_version = explode(".", $version['API']);

            $required_cgminer_version = array(3, 7, 2);
            $required_api_version = array(1, 32);

            $cgminer_version_valid = false;
            foreach ($required_cgminer_version AS $i => $check_value) {
                if (!isset($cgminer_version[$i])) {
                    $cgminer_version[$i] = 0;
                }
                if ($cgminer_version[$i] > $check_value || ($i === count($required_cgminer_version) - 1 && $cgminer_version[$i] == $check_value)) {
                    $cgminer_version_valid = true;
                    break;
                }
            }

            $api_version_valid = false;
            foreach ($required_api_version AS $i => $check_value) {
                if (!isset($api_version[$i])) {
                    $api_version[$i] = 0;
                }
                if ($api_version[$i] > $check_value || ($i === count($required_api_version) - 1 && $api_version[$i] == $check_value)) {
                    $api_version_valid = true;
                    break;
                }
            }

            if ($cgminer_version_valid === false || $api_version_valid === false) {
                $message = "PHPMiner could connect to cgminer, but cgminer runs in a unsupported version.\n\n";

                $message .= "PHPMiner requires version: \n";
                $message .= "CGMiner: <b>" . implode(".", $required_cgminer_version) . "</b>\n";
                $message .= "CGMiner API: <b>" . implode(".", $required_api_version) . "</b>\n\n";

                $message .= "Your version: \n";
                $message .= "CGMiner: <b>" . $version['CGMiner'] . "</b>\n";
                $message .= "CGMiner API: <b>" . $version['API'] . "</b>\n";
                AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $message);
            }

            $this->config->remote_port = $params->port;
            AjaxModul::return_code(AjaxModul::SUCCESS, array(
                'cgminer' => $version['CGMiner'],
                'api' => $version['API'],
            ));
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

}
