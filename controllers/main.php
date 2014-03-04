<?php

/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class main extends Controller {

    /**
     * Action: settings
     * Will display the page to configurate the system settings.
     */
    public function settings() {
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_MINER_SETTINGS) && !$this->access_control->has_permission(AccessControl::PERM_CHANGE_MAIN_SETTINGS)) {
            throw new AccessException('You don\'t have access to this action');
        }
        
        
        $conf = $this->config->get_config();
        
        if ($this->access_control->has_permission(AccessControl::PERM_CHANGE_MAIN_SETTINGS)) {
            $this->assign('config', $conf);
            $this->js_config('config', $conf);
        }
        if ($this->access_control->has_permission(AccessControl::PERM_CHANGE_MINER_SETTINGS)) {
            $rigs = $this->config->rigs;
            // Get cgminer config from cgminer when this rig is not within config yet.
            foreach ($rigs AS $rig => $rig_data) {
                if (!isset($rig_data['cgminer_conf'])) {
                    $cgminer_config = $this->get_rpc($rig)->get_config();
                    if (isset($cgminer_config['pools'])) {
                        unset($cgminer_config['pools']);
                    }

                    $rigs[$rig]['cgminer_conf'] = $cgminer_config;
                }
            }

            $this->config->rigs = $rigs;
            
            $rigs_conf = $conf['rigs'];
            unset($conf['rigs']);
            $this->assign('rigs', $rigs_conf);
            
            $this->js_config('rigs', $rigs);
            $this->js_config('possible_configs', Config::$possible_configs);
        }
    }

    /**
     * Set donation time.
     */
    public function set_donate() {
        $params = new ParamStruct();
        $params->add_param('donation', PDT_INT, 0);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        $this->config->set_value('donation', $params->donation);
        $this->config->donation_dialog_displayed = true;
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    /**
     * Send a bugreport.
     */
    public function bugreport() {
        $params = new ParamStruct();
        $params->add_required_param('bugreport', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        if (!file_exists($params->bugreport)) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'Bugreport file not found');
        }
        
        $http = new HttpClient();
        $http->do_post('https://www.phpminer.com/bugreport.php', array(
            'bugreport' => base64_encode(file_get_contents($params->bugreport))
        ), true);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    /**
     * Ajax request to switch rig collapse
     */
    public function set_rig_collapse() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('collapsed', PDT_BOOL);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        $rig_config = $this->config->get_rig($params->rig);
        if ($rig_config != null) {
            $rig_config['collapsed'] = $params->collapsed; 
            $this->config->set_rig($params->rig, $rig_config);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    /**
     * Ajax request to switch rig collapse
     */
    public function set_pager_mepp() {
        $params = new ParamStruct();
        $params->add_required_param('mepp', PDT_INT);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        $this->config->pager_mepp = $params->mepp;
        AjaxModul::return_code(AjaxModul::SUCCESS);
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
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_MAIN_SETTINGS)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $need_redirect = null;
        foreach ($params->settings AS $key => $val) {
            if ($key === 'enable_access_control' && !empty($val)) {
                
                $access_controll = new AccessConfig();
                if ($access_controll->is_empty()) {
                    $need_redirect = true;
                }
                
            }
            $this->config->set_value($key, $val);
        }
        if ($need_redirect) {
            AjaxModul::return_code(AjaxModul::SUCCESS, array('url' => murl('access', 'index')));
        }
        else {
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
    }
    /**
     * Ajax request to save new configuration settings.
     */
    public function start_stop_mining() {
        
        
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('stop', PDT_BOOL);

        // Validate that the rig exists.
        require_once 'includes/validators/FunctionValidator.class.php';
        $rigs = $this->config->rigs;
        $params->add_validator('rig', new FunctionValidator('This rig does not exists', function($value) use ($rigs) {
            return isset($rigs[$value]);
        }));
        
        
        $params->fill();
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, implode($params->get_errors()));
        }
        if (!$this->check_write_access(AccessControl::PERM_STOP_RIGS)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }

        if ($params->stop) {
            $this->get_rpc($params->rig)->kill_cgminer();
        }
        else {
            $this->load_pool_config();
            // Determine active pool group based on rpc client which reads currnt cgminer config.
            $current_active_group = $this->pool_config->get_current_active_pool_group_from_rpc($this->get_rpc($params->rig));
            
            // If we have one, we need to update the pools to make sure changed settings are up to date.
            if ($current_active_group !== null) {
                // This will be the new pool array.
                $new_pools = array();
                
                // Loop through each configurated pools.
                foreach ($this->pool_config->get_pools($current_active_group) AS $pool) {
                    // Setup the pool for cgminer.
                    $user = "";
                    if (!empty($pool['rig_based'])) {
                        $user = '.rb' . preg_replace("/[^a-zA-Z0-9]/", "", $this->config->rigs[$params->rig]['shortname']);
                    }
                    
                    // Ad this pool.
                    $new_pools[] = array(
                        'url' => $pool['url'],
                        'user' => $pool['user'] . $user,
                        'pass' => $pool['pass'],
                    );
                }
                // Store new pool config.
                $this->get_rpc($params->rig)->set_config('pools', $new_pools);
            }
            $this->get_rpc($params->rig)->restart_cgminer();
        }
        $rig_cfg = $this->config->get_rig($params->rig);
        $rig_cfg['disabled'] = $params->stop;
        $this->config->set_rig($params->rig, $rig_cfg);
        AjaxModul::return_code(AjaxModul::SUCCESS);
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

        $from_cron = !empty($rig);
        $rig_config = $this->config->rigs;
        $rig = $params->rig;
        if (!empty($rig)) {
            $rigs = array($rig);
        } else {
            $rigs = array_keys($rig_config);
        }
        
        if (!$from_cron) {
            if (!$this->access_control->has_permission(AccessControl::PERM_SWITCH_POOL_GROUP)) {
                AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
            }
        }
        $errors = array();
        $success = array();
        foreach ($rigs AS $current_rig) {
            
            if (!$from_cron) {
                if (!$this->access_control->has_permission(AccessControl::PERM_SWITCH_POOL_GROUP)) {
                    $errors[] = 'No permission to switch pool group on rig <b>' . $current_rig . '</b>.';
                    continue;
                }
            }
            
            if (!$this->get_rpc($current_rig)->check_cgminer_config_path()) {
                $errors[] = 'The CGMiner/SGMiner config file for rig <b>' . $current_rig . '</b> is not writeable.';
                continue;
            }

            $old_group = $this->pool_config->get_current_active_pool_group($this->get_rpc($current_rig));

            $cg_pools = $this->get_rpc($current_rig)->get_pools();
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
                $user = "";
                if (!empty($pool['rig_based'])) {
                    $user = '.rb' . preg_replace("/[^a-zA-Z0-9]/", "", $this->config->rigs[$current_rig]['shortname']);
                }
                $this->get_rpc($current_rig)->addpool($pool['url'], $pool['user'] . $user, $pool['pass']);
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
                $cg_pools = $this->get_rpc($current_rig)->get_pools();
                foreach ($cg_pools AS $pool) {
                    $check_uuid = $this->pool_config->get_pool_uuid($pool['URL'], $pool['User']);
                    if (isset($group_pools[$check_uuid]) && $pool['Status'] == 'Alive') {
                        $this->get_rpc($current_rig)->switchpool($pool['POOL']);
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
                // Add the pool.
                $user = "";
                if (!empty($pool['rig_based'])) {
                    $user = '.rb' . preg_replace("/[^a-zA-Z0-9]/", "", $this->config->rigs[$current_rig]['shortname']);
                }
                $cgminer_config_pools[] = array(
                    'url' => $pool['url'],
                    'user' => $pool['user'] . $user,
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
                        $this->get_rpc($current_rig)->removepool($pool['POOL']);

                        // Mark pool as removed.
                        unset($pools_to_remove[$pool_to_remove_uuid]);
                        break;
                    }
                }

                // Let cgminer a bit time to reorder his pool no.
                usleep(100000);
                // Get fresh pools.
                $cg_pools = $this->get_rpc($current_rig)->get_pools();

                // Fallback
                if ($c++ >= 50) {
                    break;
                }
            }

            // When we switched successfully.
            if ($pool_switched) {
                $this->get_rpc($current_rig)->set_config('pools', $cgminer_config_pools);

                if ($this->get_rpc($current_rig)->has_advanced_api()) {
                    $this->get_rpc($current_rig)->set_poolstrategy($this->pool_config->get_strategy($params->group), $this->pool_config->get_period($params->group));
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
        if (empty($this->config->enable_paging)) {
            $rig_js_data = $this->get_device_data(true);
        }
        else {
            $mepp = $this->config->pager_mepp;
            if (empty($mepp)) {
                $mepp = 5;
            }
            $rig_js_data = $this->get_device_data(true, 1, $mepp);
        }
        
        if (!$this->config->donation_dialog_displayed) {
            $current_use_time = $this->config->use_time;
            if (empty($current_use_time)) {
                $current_use_time = $this->config->use_time = time();
            }

            if (intval($current_use_time) + 604800 < time()) {
                if (empty($this->config->donation)) {
                    $this->js_config('display_support', true);
                }
            }
        }
        // Get the pool uuid which is currently in use.
        $this->js_config('pool_groups', $this->pool_config->get_groups());
        $this->js_config('config', $this->config->get_config());
        $this->assign('config', $this->config->get_config());
        /** DEBUG PAGER
         * $this->js_config('rig_count', count($rigs) + 20);
         */
        $this->js_config('rig_count', count($rigs));
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
     * Ajax request to reboot rig.
     */
    public function reboot_rig() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_REBOOT_RIGS)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $rigs = $this->config->rigs;
        if (!isset($rigs[$params->rig])) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'No such rig');
        }
        $this->get_rpc($params->rig)->reboot();
        AjaxModul::return_code(AjaxModul::SUCCESS);
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
        
        if (!$this->check_write_access()) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
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
     * Ajax request to save new cgminer configuration settings.
     */
    public function save_cgminer_settings() {
        $params = new ParamStruct();
        $params->add_required_param('settings', PDT_ARR);

        $params->fill();
        
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_MINER_SETTINGS)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $errors = array();
        $local_rigs = $this->config->rigs;
        foreach ($params->settings AS $rig => $rig_data) {
            if (!isset($local_rigs[$rig]['cgminer_conf'])) {
                $local_rigs[$rig]['cgminer_conf'] = array();
            }
            
            if (!empty($local_rigs[$rig]['disabled'])) {
                continue;
            }
            
            if (!$this->check_write_access(AccessControl::PERM_CHANGE_MINER_SETTINGS)) {
                continue;
            }
            
            // Remove deleted config keys
            foreach ($local_rigs[$rig]['cgminer_conf'] as $key => $val) {
                if (!isset($rig_data[$key])) {
                    try {
                        $res = $this->get_rpc($rig)->set_config($key, "");
                    }
                    catch (Exception $e) {
                        $res = $e->getMessage();
                    }
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
                try {
                    $res = $this->get_rpc($rig)->set_config($key, $val);
                }
                catch (Exception $e) {
                    $res = $e->getMessage();
                }
                if ($res !== true) {
                    if (!isset($errors[$rig])) {
                        $errors[$rig] = array();
                    }
                    $msg_chk = md5($res);
                    $errors[$rig][$msg_chk] = ' - ' . $res;
                }
            }
            if (!isset($errors[$rig])) {
                $local_rigs[$rig]['cgminer_conf'] = $rig_data;
            }
        }
        if (!empty($errors)) {
            $err_str = "Not all rig's could be saved.\n";
            foreach ($errors AS $rig => $errors) {
                $err_str .= "Rig: " . $rig . "\n" . implode("\n", $errors) . "\n";
            }
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $err_str);
        }
        $this->config->rigs = $local_rigs;
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
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('pool', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_SWITCH_POOL_GROUP)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $this->load_pool_config();
        $pool_uuid = $this->pool_config->get_pool_uuid($params->pool);
        $sorted_pools = array();
        foreach ($this->get_rpc($params->rig)->get_pools() AS $pool) {
            $sorted_pools[$this->pool_config->get_pool_uuid($pool['URL'], $pool['User'])] = $pool;
        }
        try {
            $this->get_rpc($params->rig)->switchpool($sorted_pools[$pool_uuid]['POOL']);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $e) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
        }
    }

    /**
     * Ajax request to retrieve current configurated devices within cgminer.
     */
    public function reset_stats() {
        
        $params = new ParamStruct();
        $params->add_param('rig', PDT_STRING, '');

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_RESET_STATS)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $rig = $params->rig;
        $rigs = array();
        if (!empty($rig)) {
            $rigs[] = $rig;
        }
        else {
            $rigs = array_keys($this->config->rigs);
        }
        
        foreach ($rigs AS $rig) {
            $this->get_rpc($rig)->zero();
        }
        
        AjaxModul::return_code(AjaxModul::SUCCESS, null, true, 'Rig stats resetted');
    }
    
    /**
     * Ajax request to retrieve current configurated devices within cgminer.
     */
    public function get_device_list() {
        
        $params = new ParamStruct();
        $params->add_param('rigs', PDT_ARR, array());
        $params->add_param('page', PDT_INT, 0);
        $params->add_param('mepp', PDT_INT, 0);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        $rigs = $params->rigs;
        
        if (!empty($rigs)) {
            $resp = $this->get_device_data(false, $params->page, $params->mepp, $rigs);
        }
        else {
            $resp = $this->get_device_data(true, $params->page, $params->mepp);
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
            $this->api = new PHPMinerRPC($this->config->http_ip, $this->config->http_port);
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
        require_once 'includes/validators/RegexpValidator.class.php';
        $params = new ParamStruct();
        $params->add_required_param('name', PDT_STRING);
        $params->add_required_param('shortname', PDT_STRING);
        $params->add_validator('shortname', new RegexpValidator('You have invalid characters within "shortname", please provide only letters from a-z, A-Z and/or numbers from 0-9', '/^[a-zA-Z0-9]+$/'));
        $params->add_required_param('http_ip', PDT_STRING);
        $params->add_required_param('http_port', PDT_INT);
        $params->add_required_param('rpc_key', PDT_STRING);
        $params->add_param('edit', PDT_STRING, '');

        $params->fill();
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, implode("\n", $params->get_errors()));
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_RIGS)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
                
        $api = new PHPMinerRPC($params->http_ip, $params->http_port, $params->rpc_key);
        try {
            $version = $api->test_connection();
            if (empty($version) || (!isset($version['CGMiner']) && !isset($version['SGMiner'])) || !isset($version['API'])) {
                $message = "Could not find CGMiner/SGminer version or CGMiner/SGminer API version. Please check that CGMiner/SGminer is running and configurated how it is written within the readme of PHPMiner.";
                AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $message);
            }
            
            if (isset($version['CGMiner'])) {
                $type = "CGMiner";
                $orig_version = $version['CGMiner'];
                $cgminer_version = explode(".", $version['CGMiner']);
                $required_cgminer_version = array(3, 7, 2);
            }
            else if (isset($version['SGMiner'])) {
                $type = "SGMiner";
                $orig_version = $version['SGMiner'];
                $cgminer_version = explode(".", $version['SGMiner']);
                $required_cgminer_version = array(3, 7, 2);
            }
            $api_version = explode(".", $version['API']);
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
                $message = "PHPMiner could connect to CGMiner/SGMiner, but CGMiner/SGMiner runs in a unsupported version.\n\n";

                $message .= "PHPMiner requires version: \n";
                $message .= $type . ": <b>" . implode(".", $required_cgminer_version) . "</b>\n";
                $message .= $type . " API: <b>" . implode(".", $required_api_version) . "</b>\n\n";

                $message .= "Your version: \n";
                $message .= $type . ": <b>" . $orig_version . "</b>\n";
                $message .= $type . " API: <b>" . $version['API'] . "</b>\n";
                AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $message);
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
                'shortname' => $params->shortname,
                'http_ip' => $params->http_ip,
                'http_port' => $params->http_port,
                'rpc_key' => $params->rpc_key,
            );
            
            if (!empty($params->edit) && $params->edit !== "false") {
                foreach ($rigs AS $rig_name => $rig_data) {
                    if ($rig_name === $params->edit) {
                        foreach ($rig_to_use AS $k => $v) {
                            $rig_data[$k] = $v;
                        }
                        $new_rigs[$params->name] = $rig_data;
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
                'cgminer' => $orig_version,
                'api' => $version['API'],
            ));
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    /**
     * Retrieve the info for all devices per rig.
     * 
     * @param boolean $from_init
     *   Wether we want extra data which is need by init or not. (Optional, default = false)
     * @param int $page
     *   The page to show. (Optional, default = null)
     * @param int $mepp
     *   Max entries per page. (Optional, default = 0)
     * 
     * @return array
     *   A list with the devices for each rig
     */
    private function get_device_data($from_init = false, $page = 0, $mepp = 0, $rigs = array()) {
        $resp = array();
        $rig_config = $this->config->rigs;

        /* DEBUG PAGER
         * $first = reset($rig_config);
        for($i = 0; $i < 20;$i++ ) {
            $first['name'] = uniqid() . $i;
            $rig_config[$first['name']] = $first;
        }
         * 
         */
        
        $sort_mode = $this->config->get_value('overview_sort_mode');
        if (empty($sort_mode)) {
            $sort_mode = "configured";
        }
        
        $this->load_pool_config();
        
        if (empty($rigs)) {
            $rig_names = array_keys($rig_config);

            foreach ($rig_names AS $rig) {
                if ($sort_mode !== 'error') {
                    $resp[$rig] = true;
                }
                else {
                    $resp[$rig] = $this->get_detailed_rig_data($rig, $from_init);
                }
            }
            $this->sort_rigs($sort_mode, $resp, $rig_config);
            $resp = $this->get_paged_entries($resp, $page, $mepp);

            if ($sort_mode !== 'error') {
                foreach ($resp AS $rig => &$entry) {
                    $entry = $this->get_detailed_rig_data($rig, $from_init);
                }
            }
        }
        else {
            foreach ($rigs AS $rig) {
                $resp[$rig] = $this->get_detailed_rig_data($rig, $from_init);
            }
        }
        return $resp;
    }   
        
    /**
     * 
     * @param string $sort_mode
     *   The sort mode
     * @param array $rigs
     *   The rigs which will be sorted.
     * @param array $rig_config
     *   The config for all rig's
     */
    private function sort_rigs($sort_mode, &$rigs, $rig_config) {
        if ($sort_mode == 'name') {
            ksort($rigs);
        }
        else if($sort_mode == 'error') {
            uasort($rigs, function($a, $b) use ($rig_config)  {
                $a_c = $this->get_error_count($a, $rig_config[$a['rig_name']]);
                $b_c = $this->get_error_count($b, $rig_config[$b['rig_name']]);
                if ($a_c == $b_c) {
                    return 0;
                }
                return ($a_c > $b_c) ? -1 : 1;
            });
        }
    }
    
    /**
     * Get the paged entries.
     * 
     * @param array $rigs
     *   The full rig list.
     * @param int $page
     *   The page to show. (Optional, default = null)
     * @param int $mepp
     *   Max entries per page. (Optional, default = 0)
     * 
     * @return array
     *   The paged entries.
     */
    private function get_paged_entries($rigs, $page = 0, $mepp = 0) {
        // Process pages.
        if ($mepp > 0 && $page > 0) {
            $tmp_resp = $rigs;
            $rigs = array();
            
            $start = ($page - 1) * $mepp;
            $end = $start + $mepp;
            
            reset($tmp_resp);
            if (count($tmp_resp) >= $start) {
                for($i = 0; $i < $end; $i++) {
                    if ($i >= $start) {
                        $rigs[key($tmp_resp)] = current($tmp_resp);                        
                    }
                    if (next($tmp_resp) === false) {
                        break;
                    }
                }
            }
        }
        return $rigs;
    }
    
    /**
     * Returns the detailed info for a rig.
     * 
     * @param string $rig
     *   The rig name.
     * @param boolean $from_init
     *   Wether we want extra data which is need by init or not. (Optional, default = false)
     * 
     * @return boolean|array
     *   The detailed info for a rig as an array or false on error.
     */
    private function get_detailed_rig_data($rig, $from_init = false) {
        $orig = $rig;
        /*DEBUG PAGER
         * $rig = 'localhost';
         */
        $rigs = $this->config->rigs;
        try {
            $pools = $this->get_rpc($rig)->get_pools();

            $devices = $this->get_rpc($rig)->get_devices_details();
            foreach ($devices AS &$device) {
                if ($device['Name'] !== 'GPU') {
                    continue;
                }
                $info = $this->get_rpc($rig)->get_gpu($device['ID']);
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
                if (empty($device['disabled'])) {
                    $device['is_running']= $this->get_rpc($rig)->is_cgminer_running();
                }
            }
            
            // Determine which pool group we are currently using.        
            $current_active_group = $this->pool_config->get_current_active_pool_group($this->get_rpc($rig));

            if (empty($current_active_group)) {
                $rig_conf = $this->get_rpc($rig)->get_config();
                if (!empty($rig_conf)) {
                    $this->pool_config->del_group('Auto created for rig: ' . $rig);
                    foreach ($rig_conf['pools'] AS $rig_conf_pool) {
                        $this->pool_config->add_pool($rig_conf_pool['url'], $rig_conf_pool['user'], $rig_conf_pool['pass'], 'Auto created for rig: ' . $rig);
                    }
                    $current_active_group = $this->pool_config->get_current_active_pool_group($this->get_rpc($rig));
                }
            }

            return array(
                'rig_name' => $orig,
                'device_list' => $devices,
                'active_pool_group' => $current_active_group,
                'pools' => $this->pool_config->get_pools($current_active_group),
                'donating' => !empty($rigs[$rig]['switch_back_group']),
                'collapsed' => !empty($rigs[$rig]['collapsed']),
                'disabled' => !empty($rigs[$rig]['disabled']),
            );



        } catch (Exception $ex) {
            if (!empty($rigs[$rig]['disabled'])) {
                return array(
                    'rig_name' => $orig,
                    'pools' => array(),
                    'active_pool_group' => null,
                    'device_list' => array(),
                    'donating' => !empty($rigs[$rig]['switch_back_group']),
                    'collapsed' => !empty($rigs[$rig]['collapsed']),
                    'disabled' => !empty($rigs[$rig]['disabled']),
                );
            }
            return false;
        }
    }
    
    /**
     * Retrieve the error count for the given rig.
     * 
     * @staticvar array $cache
     *   Save performance.
     * 
     * @param array $rig
     *   The rig data. which comes from get_device_data.
     * @param array $rig_config
     *   The rig config which comes from $this->config->rigs
     * 
     * @return int
     *   The error count.
     */
    private function get_error_count($rig, $rig_config) {
        static $cache = array();
        
        if (!isset($cache[$rig['rig_name']])) {
            $errors = 0;
            foreach ($rig['device_list'] AS $device) {

                $device_cfg_key = 'gpu_' . $device['ID'];
                if (!isset($rig_config[$device_cfg_key])) {
                    $rig_config[$device_cfg_key] = array();
                }
                if (!isset($rig_config[$device_cfg_key]['temperature'])) {
                    $rig_config[$device_cfg_key]['temperature'] = array();
                }
                if (!isset($rig_config[$device_cfg_key]['temperature']['min'])) {
                    $rig_config[$device_cfg_key]['temperature']['min'] = 50;
                }
                if (!isset($rig_config[$device_cfg_key]['temperature']['max'])) {
                    $rig_config[$device_cfg_key]['temperature']['max'] = 85;
                }

                if (!isset($rig_config[$device_cfg_key]['hashrate'])) {
                    $rig_config[$device_cfg_key]['hashrate'] = array();
                }
                if (!isset($rig_config[$device_cfg_key]['hashrate']['min'])) {
                    $rig_config[$device_cfg_key]['hashrate']['min'] = 100;
                }

                if (!isset($rig_config[$device_cfg_key]['load'])) {
                    $rig_config[$device_cfg_key]['load'] = array();
                }
                if (!isset($rig_config[$device_cfg_key]['load']['min'])) {
                    $rig_config[$device_cfg_key]['load']['min'] = 90;
                }

                if (!isset($rig_config[$device_cfg_key]['hw'])) {
                    $rig_config[$device_cfg_key]['hw'] = array();
                }
                if (!isset($rig_config[$device_cfg_key]['hw']['max'])) {
                    $rig_config[$device_cfg_key]['hw']['max'] = 5;
                }

                $device['gpu_info']['Temperature'] = intval($device['gpu_info']['Temperature']);
                if ($device['gpu_info']['Temperature'] <  $rig_config[$device_cfg_key]['temperature']['min'] || $device['gpu_info']['Temperature'] > $rig_config[$device_cfg_key]['temperature']['max']) {
                    $errors++;
                }
                if ($device['gpu_info']['MHS 5s'] * 1000 < $rig_config[$device_cfg_key]['hashrate']['min']) {
                    $errors++;
                }
                if ($device['gpu_info']['GPU Activity'] < $rig_config[$device_cfg_key]['load']['min']) {
                    $errors++;
                }
                if ($device['gpu_info']['Hardware Errors'] > $rig_config[$device_cfg_key]['hw']['max']) {
                    $errors++;
                }
            }
            $cache[$rig['rig_name']] = $errors;
        }
        return $cache[$rig['rig_name']];
    }

    private function check_write_access($additional_check = null) {
        
        if (!empty($additional_check) && !$this->access_control->has_permission($additional_check)) {
            return false;
        }
        
        return $this->access_control->has_permission(AccessControl::PERM_CHANGE_RIGS);
    }
}
