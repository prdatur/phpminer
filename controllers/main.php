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
        $conf = $this->config->get_config();
        // Get cgminer config from cgminer when this rig is not within config yet.
        foreach ($conf['rigs'] AS $rig => $rig_data) {
            if (!isset($rig_data['cgminer_conf'])) {
                $cgminer_config = $this->get_rpc($rig)->get_config();
                if (isset($cgminer_config['pools'])) {
                    unset($cgminer_config['pools']);
                }
                
                $conf['rigs'][$rig]['cgminer_conf'] = $cgminer_config;
            }
        }
        $this->config->rigs = $conf['rigs'];
        $this->assign('config', $conf);
        $this->js_config('config', $conf);
        $this->js_config('possible_configs', Config::$possible_configs);
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
    public function switch_pool_group($pool_group = '', $rig = '') {

        $params = new ParamStruct();
        $params->add_required_param('group', PDT_STRING);
        $params->add_param('rig', PDT_STRING);

        if (!empty($pool_group)) {
            $params->fill(array('group' => $pool_group, 'rig' => $rig));
        } else {
            $params->fill();
        }

        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        // Make sure group exists.
        $this->load_pool_config();
        if (!$this->pool_config->group_exists($params->group)) {
            if (!empty($pool_group)) {
                return false;
            }
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'You provided an invalid group');
        }

        $rig = $params->rig;
        if (!empty($rig)) {
            $rigs = array($rig);
        } else {
            $rigs = array_keys($this->config->rigs);
        }
        
        $errors = array();
        $success = array();
        foreach ($rigs AS $current_rig) {
            if (!$this->get_rpc($current_rig)->check_cgminer_config_path()) {
                $errors[] = 'The cgminer config file for rig <b>' . $current_rig . '</b> is not writeable.';
                continue;
            }

            $old_group = $this->pool_config->get_current_active_pool_group($this->get_api($current_rig));

            $cg_pools = $this->get_api($current_rig)->get_pools();
            $pools_to_add = $this->pool_config->get_pools($params->group);
            foreach ($pools_to_add AS $k => $pool) {
                unset($pools_to_add[$k]);
                $pools_to_add[$this->pool_config->get_pool_uuid($pool['url'], $pool['user'])] = $pool;
            }
            $group_pools = $pools_to_add;

            foreach ($cg_pools AS $pool) {
                $rem_uuid = $this->pool_config->get_pool_uuid($pool['URL'], $pool['User']);
                if (isset($pools_to_add[$rem_uuid])) {
                    unset($pools_to_add[$rem_uuid]);
                }
            }

            foreach ($pools_to_add AS $pool) {
                // Add the pool.
                $this->get_api($current_rig)->addpool($pool['url'], $pool['user'], $pool['pass'], !empty($pool['quota']) ? $pool['quota'] : 1);
                usleep(100000); // Wait 100 milliseconds to let the pool to be alived.
            }

            $pool_switched = false;
            $fallback_counter = 0;
            while (!$pool_switched) {
                usleep(100000);

                // Just try 100 times, which are around 1 second, to get a current added pool alive.
                if ($fallback_counter++ > 100) {
                    break;
                }

                // save the current cg pools, we do this here because we can then reuse it after switching.
                $cg_pools = $this->get_api($current_rig)->get_pools();
                foreach ($cg_pools AS $pool) {
                    $check_uuid = $this->pool_config->get_pool_uuid($pool['URL'], $pool['User']);
                    if (isset($group_pools[$check_uuid]) && $pool['Status'] == 'Alive') {
                        $this->get_api($current_rig)->switchpool($pool['POOL']);
                        $pool_switched = true;
                        break;
                    }
                }
            }

            $cgminer_config_pools = array();
            if (!$pool_switched) {
                $group_pools = $this->pool_config->get_pools($old_group);
            }

            foreach ($group_pools AS $k => $pool) {
                unset($group_pools[$k]);
                $group_pools[$this->pool_config->get_pool_uuid($pool['url'], $pool['user'])] = $pool;

                $cgminer_config_pools[] = array(
                    'url' => $pool['url'],
                    'user' => $pool['user'],
                    'pass' => $pool['pass'],
                );
            }

            $pools_to_remove = array();
            foreach ($cg_pools AS $pool) {
                $rem_uuid = $this->pool_config->get_pool_uuid($pool['URL'], $pool['User']);
                if (!isset($group_pools[$rem_uuid])) {
                    $pools_to_remove[$rem_uuid] = $pool;
                }
            }
            $c = 0;
            // Loop through the pools to be removed until all pools are removed.
            while (!empty($pools_to_remove)) {

                // Get the pool which needs to be removed now.
                $pool_to_remove_uuid = key($pools_to_remove);

                // Loop through each current cgminer pools.
                foreach ($cg_pools AS $k => $pool) {
                    // Get the uuid for this pool.
                    $rem_uuid = $this->pool_config->get_pool_uuid($pool['URL'], $pool['User']);

                    // Check if this pool is the wanted one which needs to be removed.
                    if ($pool_to_remove_uuid == $rem_uuid) {
                        // Remove pool from cgminer.
                        $this->get_api($current_rig)->removepool($pool['POOL']);

                        // Mark pool as removed.
                        unset($pools_to_remove[$pool_to_remove_uuid]);
                        break;
                    }
                }

                // Let cgminer a bit time to reorder his pool no.
                usleep(100000);
                // Get fresh pools.
                $cg_pools = $this->get_api($current_rig)->get_pools();

                // Fallback
                if ($c++ >= 50) {
                    break;
                }
            }

            // When we switched successfully.
            if ($pool_switched) {
                $this->get_rpc($current_rig)->set_config('pools', $cgminer_config_pools);

                if ($this->get_api($current_rig)->has_advanced_api()) {
                    $this->get_api($current_rig)->set_poolstrategy($this->pool_config->get_strategy($params->group), $this->pool_config->get_period($params->group));
                    $this->get_rpc($current_rig)->set_config('rotate', "");
                    $this->get_rpc($current_rig)->set_config('balance', "");
                    $this->get_rpc($current_rig)->set_config('oad-balance', "");
                    $this->get_rpc($current_rig)->set_config('round-robin', "");

                    if ($this->pool_config->get_strategy($params->group) !== 0) {
                        switch ($this->pool_config->get_strategy($params->group)) {
                            case 1:
                                $this->get_rpc($current_rig)->set_config('round-robin', true);
                                break;
                            case 2:
                                $this->get_rpc($current_rig)->set_config('rotate', $this->pool_config->get_period($params->group));
                                break;
                            case 3:
                                $this->get_rpc($current_rig)->set_config('load-balance', true);
                                break;
                            case 4:
                                $this->get_rpc($current_rig)->set_config('balance', true);
                                break;
                        }
                    }
                }
                $this->get_rpc($current_rig)->set_config('pools', $cgminer_config_pools);
                $success[$current_rig] = true;
            } else {
                $errors['noalivepool'] = 'Could not find an alive pool from the new group, switched back to previous pool group';
            }
        }

        $resp_data = array(
            'new_pools' => $this->pool_config->get_pools($params->group),
        );
        if (!empty($success)) {
            $resp_data['success'] = $success;
        }
        if (!empty($errors)) {
            $resp_data['errors'] = $errors;
        }
        if (!empty($pool_group)) {
            return true;
        }
        AjaxModul::return_code(AjaxModul::SUCCESS, $resp_data);
    }

    /**
     * Main init function where the devices are listed-
     */
    public function init() {
        // Get pools
        $rigs = $this->config->rigs;
        $rig_js_data = $rig_data = array();

        $this->load_pool_config();
        foreach (array_keys($rigs) AS $rig) {
            try {
                $pools = $this->get_api($rig)->get_pools();

                $devices = $this->get_api($rig)->get_devices_details();
                foreach ($devices AS &$device) {
                    if ($device['Name'] !== 'GPU') {
                        continue;
                    }
                    $info = $this->get_api($rig)->get_gpu($device['ID']);
                    $info = reset($info);

                    $device['gpu_info'] = $info;
                    // Check if we have our own cgminer fork with advanced api.
                    if (isset($info['Current Pool'])) {
                        $pool_check = $info['Current Pool'];
                    } else {
                        $pool_check = $info['Last Share Pool'];
                    }

                    foreach ($pools AS $pool) {
                        if ($pool['POOL'] == $pool_check) {
                            $device['pool'] = $pool;
                        }
                    }

                    if (!empty($rigs[$rig]['switch_back_group'])) {
                        $device['donating'] = ceil((900 - $rigs[$rig]['donation_time']) / 60);
                    }
                }
                // Determine which pool group we are currently using.        
                $current_active_group = $this->pool_config->get_current_active_pool_group($this->get_api($rig));
                
                if (empty($current_active_group)) {
                    $rig_conf = $this->get_rpc($rig)->get_config();
                    if (!empty($rig_conf)) {
                        $this->pool_config->del_group('Auto created for rig: ' . $rig);
                        foreach ($rig_conf['pools'] AS $rig_conf_pool) {
                            $this->pool_config->add_pool($rig_conf_pool['url'], $rig_conf_pool['user'], $rig_conf_pool['pass'], 'Auto created for rig: ' . $rig);
                        }
                        $current_active_group = $this->pool_config->get_current_active_pool_group($this->get_api($rig));
                    }
                }
                
                $rig_js_data[$rig] = array(
                    'device_list' => $devices,
                    'active_pool_group' => $current_active_group,
                    'pools' => $this->pool_config->get_pools($current_active_group),
                    'donating' => !empty($rigs[$rig]['switch_back_group']),
                );
            } catch (Exception $e) {
                $rig_js_data[$rig] = false;
            }
        }

        // Get the pool uuid which is currently in use.
        $this->js_config('pool_groups', $this->pool_config->get_groups());
        $this->js_config('config', $this->config->get_config());
        $this->js_config('rig_data', $rig_js_data);
        $this->js_config('is_configurated', !empty($rigs));
    }

    /**
     * Ajax request to get rig data for editing.
     */
    public function get_rig_data() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $rigs = $this->config->rigs;
        if (!isset($rigs[$params->rig])) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'No such rig');
        }

        AjaxModul::return_code(AjaxModul::SUCCESS, $rigs[$params->rig]);
    }

    /**
     * Ajax request to remove rig.
     */
    public function delete_rig() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $rigs = $this->config->rigs;
        if (!isset($rigs[$params->rig])) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'No such rig');
        }
        unset($rigs[$params->rig]);
        $this->config->rigs = $rigs;
        AjaxModul::return_code(AjaxModul::SUCCESS);
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
     * Ajax request to save new cgminer configuration settings.
     */
    public function save_cgminer_settings() {
        $params = new ParamStruct();
        $params->add_required_param('settings', PDT_ARR);

        $params->fill();
        
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        $errors = array();
        $local_conf = $this->config->get_config();
        foreach ($params->settings AS $rig => $rig_data) {
            if (!isset($local_conf['rigs'][$rig]['cgminer_conf'])) {
                $local_conf['rigs'][$rig]['cgminer_conf'] = array();
            }
            
            // Remove deleted config keys
            foreach ($local_conf['rigs'][$rig]['cgminer_conf'] as $key => $val) {
                if (!isset($rig_data[$key])) {
                    $res = $this->get_rpc($rig)->set_config($key, "");
                    if ($res !== true) {
                        if (!isset($errors[$rig])) {
                            $errors[$rig] = array();
                        }
                        $msg_chk = md5($res);
                        $errors[$rig][$msg_chk] = ' - ' . $res;
                    }
                }
            }
            // Add / Change config keys
            foreach ($rig_data as $key => $val) {
                $res = $this->get_rpc($rig)->set_config($key, $val);
                if ($res !== true) {
                    if (!isset($errors[$rig])) {
                        $errors[$rig] = array();
                    }
                    $msg_chk = md5($res);
                    $errors[$rig][$msg_chk] = ' - ' . $res;
                }
            }
            if (!isset($errors[$rig])) {
                $local_conf['rigs'][$rig]['cgminer_conf'] = $rig_data;
            }
        }
        if (!empty($errors)) {
            $err_str = "Not all rig's could be saved.\n";
            foreach ($errors AS $rig => $errors) {
                $err_str .= "Rig: " . $rig . "\n" . implode("\n", $errors) . "\n";
            }
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $err_str);
        }
        $this->config->rigs = $local_conf['rigs'];
        AjaxModul::return_code(AjaxModul::SUCCESS);
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
     * Switch all devices to the given pool.
     * 
     * @post string $pool_uuid
     *   The pool uuid to switch
     */
    public function switch_pool() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('pool', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        $this->load_pool_config();
        $pool_uuid = $this->pool_config->get_pool_uuid($params->pool);
        $sorted_pools = array();
        foreach ($this->get_api($params->rig)->get_pools() AS $pool) {
            $sorted_pools[$this->pool_config->get_pool_uuid($pool['URL'], $pool['User'])] = $pool;
        }
        try {
            $this->get_api($params->rig)->switchpool($sorted_pools[$pool_uuid]['POOL']);
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
        $resp = array();
        
        $this->load_pool_config();
        foreach (array_keys($this->config->rigs) AS $rig) {
            try {
                $devices = $this->get_api($rig)->get_devices_details();
                

                $group_pools = $this->pool_config->get_pools($this->pool_config->get_current_active_pool_group($this->get_api($rig)));
                foreach ($group_pools AS $k => $pool) {
                    unset($group_pools[$k]);
                    $group_pools[$this->pool_config->get_pool_uuid($pool['url'], $pool['user'])] = $pool;
                }

                $rigs = $this->config->rigs;

                // Get pools
                $pools = $this->get_api($rig)->get_pools();
                foreach ($devices AS &$device) {
                    if ($device['Name'] !== 'GPU') {
                        continue;
                    }
                    $info = $this->get_api($rig)->get_gpu($device['ID']);
                    $info = reset($info);

                    $device['gpu_info'] = $info;

                    if (isset($info['Current Pool'])) {
                        $pool_check = $info['Current Pool'];
                    } else {
                        $pool_check = $info['Last Share Pool'];
                    }

                    foreach ($pools AS $pool) {
                        if ($pool['POOL'] == $pool_check) {
                            $device['pool'] = $pool;
                        }
                    }

                    if (!empty($rigs[$rig]['switch_back_group'])) {
                        $device['donating'] = ceil((900 - $rigs[$rig]['donation_time']) / 60);
                    }
                }
                $resp[$rig] = $devices;
            } catch (Exception $ex) {
                continue;
            }
        }
        AjaxModul::return_code(AjaxModul::SUCCESS, $resp);
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
        $params->add_required_param('name', PDT_STRING);
        $params->add_required_param('ip', PDT_STRING);
        $params->add_required_param('port', PDT_INT);
        $params->add_required_param('http_ip', PDT_STRING);
        $params->add_required_param('http_port', PDT_INT);
        $params->add_required_param('rpc_key', PDT_STRING);
        $params->add_param('edit', PDT_STRING, '');

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $api = new CGMinerAPI($params->ip, $params->port);
        try {
            $version = $api->test_connection();
            if (empty($version) || !isset($version['CGMiner']) || !isset($version['API'])) {
                $message = "Could not find CGMiner version or CGMiner API version. Please check that CGMiner is running and configurated how it is written within the readme of PHPMiner.";
                AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $message);
            }
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

            if (empty($this->config->rigs )) {
                $this->config->rigs = array();
            }
            $rpc_check = new PHPMinerRPC($params->http_ip, $params->http_port, $params->rpc_key, 10);
            $rpc_response = $rpc_check->ping();
            if ($rpc_response !== true) {
                AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'RPC Error: ' . $rpc_response);
            }
            $rigs = $this->config->rigs;
            
            if (isset($rigs[$params->name]) && (empty($params->edit) || $params->edit === "false")) {
                AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'This rig already exists.');
            }
            $new_rigs = array();
            
            $rig_to_use = array(
                'name' => $params->name,
                'ip' => $params->ip,
                'port' => $params->port,
                'http_ip' => $params->http_ip,
                'http_port' => $params->http_port,
                'rpc_key' => $params->rpc_key,
            );
           
            if (!empty($params->edit) && $params->edit !== "false") {
                foreach ($rigs AS $rig_name => $rig_data) {
                    if ($rig_name === $params->edit) {
                        $new_rigs[$params->name] = $rig_to_use;
                    }
                    else {
                        $new_rigs[$rig_name] = $rig_data;
                    }
                }
            } else {
                $new_rigs = $rigs;
                $new_rigs[$params->name] = $rig_to_use;
            }

            $this->config->rigs = $new_rigs;
            AjaxModul::return_code(AjaxModul::SUCCESS, array(
                'cgminer' => $version['CGMiner'],
                'api' => $version['API'],
            ));
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

}
