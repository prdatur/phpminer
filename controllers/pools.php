<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'includes/validators/FunctionValidator.class.php';

class pools extends Controller {

    public function main() {
        $this->load_pool_config();
        if ($this->pool_config->is_empty()) {
            $this->pool_config->add_group('default', 0, 0);
        }
        $rig_names = array();
        foreach ($this->config->rigs AS $rig => $rig_data) {
            if (!empty($rig_data['shortname'])) {
                $rig_names[] = $rig_data['shortname'];
            }
            else {
                $rig_names[] = $rig;
            }
        }
        $this->js_config('rig_names', $rig_names);
    }

    public function change_pool() {
        $params = new ParamStruct();
        $params->add_required_param('pk', PDT_STRING);
        $params->add_required_param('name', PDT_STRING);
        $params->add_isset_param('value', PDT_STRING);

        $params->fill();
        if (!$params->is_valid(true)) {
            header("HTTP/1.0 400 Bad request");
            echo implode("\n", $params->get_errors());
            exit();
        }
        
        if ($params->name === 'rig_based') {
            $val = ($params->value === 'Enabled') ? true : false;
        }
        else {
            $val = $params->value;
        }
        
        list($uuid, $group) = explode("|", $params->pk, 2);
        $this->load_pool_config();
        $old_pool = $this->pool_config->get_pool($uuid, $group);
        if (empty($old_pool)) {
            header("HTTP/1.0 400 Bad request");
            echo 'No such pool.';
            exit();
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)) {
            header("HTTP/1.0 400 Bad request");
            echo 'You do not have permission to change this pool.';
            exit();
        }
        
        
        $old_pool[$params->name] = $val;
        
        foreach ($this->config->rigs AS $rig => $rig_data) {
            if (!empty($rig_data['disabled'])) {
                continue;
            }
            try {
                if ($this->pool_config->get_current_active_pool_group($this->get_rpc($rig)) === $group)  {
                    header("HTTP/1.0 400 Bad request");
                    echo 'The active pool group can not be changed.';
                    exit();
                }
            } catch (Exception $ex) {
                continue;
            }
        }
        
        $pool_check = $this->pool_config->check_pool($old_pool['url'], $old_pool['user'], $old_pool['pass']);
        if ($pool_check !== true) {
            header("HTTP/1.0 400 Bad request");
            echo $pool_check;
            exit();
        }
        
        $this->pool_config->update_pool($uuid, $old_pool['url'], $old_pool['user'], $old_pool['pass'], $old_pool['quota'], $old_pool['rig_based']);
        AjaxModul::return_code(AjaxModul::SUCCESS, array(
            'group' => $group,
            'old' => $uuid,
            'new' => $this->pool_config->get_pool_uuid($old_pool['url'], $old_pool['user']),
            'url' => $old_pool['url'],
        ));
    }

    public function delete_pool() {
        $params = new ParamStruct();
        $params->add_required_param('uuid', PDT_STRING);
        $params->add_param('confirm', PDT_BOOL, false);

        $params->fill(ParamStruct::FILL_FROM_GP);
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, array(
                'url' => $params->url,
                'user' => $params->user,
            ));
        }
        list($uuid, $group) = explode("|", $params->uuid, 2);
        $this->load_pool_config();
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $cfg_pools = $this->pool_config->get_pools($group);
        if ($group === 'default' && count($cfg_pools) == 1) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not remove this pool, it is the only one within the default group. If you want to delete this pool, please add a pool to the default group first.');
        }
        if ($group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not remove the donate pool group.');
        }
        $wait_msg = '';
        $pools_needs_to_switch = array();
        foreach ($this->config->rigs AS $rig => $rig_data) {
        
            if (!empty($rig_data['disabled'])) {
                continue;
            }
            
            try {
                
                // Get all configured pools within cgminer.
                $pools = $this->get_rpc($rig)->get_pools();

                // Determine which pool group we are currently using.        
                $current_active_group = $this->pool_config->get_current_active_pool_group($this->get_rpc($rig));

                // If we want to delete a pool from the group which is currently active, we need to check additional things, in order to not delete the current active pool, cgminer would not let us do this.
                if ($current_active_group === $group) {

                    // We need to check if we want to delete the pool which is currently active.
                    $active_pools = array();

                    // Get all devices.
                    $devices = $this->get_rpc($rig)->get_devices();

                    $pool_to_remove = null;

                    // We create an array where we have the pool id as the array key, this will save some performance withi nthe next loop, else we would need to loop each pool within each device loop.
                    $sorted_pools = array();
                    foreach ($pools AS $pool) {
                        $sorted_pools[$pool['POOL']] = $pool;
                        if ($this->pool_config->get_pool_uuid($pool['URL'], $pool['User']) === $uuid) {
                            $pool_to_remove = $pool['POOL'];
                        }
                    }

                    $active_pool = null;
                    // Loop through each device.
                    foreach ($devices AS $device) {

                        if (isset($device['Current Pool'])) {
                            $pool_check = $device['Current Pool'];
                        }
                        else {
                            $pool_check = $device['Last Share Pool'];
                        }

                        // Just add the last share pool as an index to the active pools, this will just help us to save performance while checking if all devices have the same pool.
                        if (!isset($active_pools[$pool_check])) {
                            $active_pools[$pool_check] = 0;
                        }
                        $active_pools[$pool_check]++;

                        // For the first time we save the pool as the active one, we will set it back to null if we find more than one active pool which are not the same.
                        $active_pool = $sorted_pools[$pool_check];
                    }

                    // We don't need to loop again, because we have 
                    if (count($active_pools) > 1) {
                        $active_pool = null;
                    }

                    // If we have more than one active mining pool, we have just switch (or cgminer due to a failover/roundrobin), in this state we can't delete anything, tell browser to wait until devices have the same pool.
                    if ($active_pool === null) {
                        $wait_msg .= 'Rig <b>' . $rig . '</b>\n';
                        foreach ($active_pools AS $pool_id => $device_count) {
                           $wait_msg .= ' - ' . $sorted_pools[$pool_id]['URL'] . " (" . $device_count . ")\n"; 
                        }
                        continue;
                    }
                    // Don't process further when we have some errors, we need to continue so we have really all errors, not only the first one.
                    if (!empty($wait_msg)) {
                        continue;
                    }
                    // Get the pool uuid which is currently in use.
                    $active_pool_uuid = $this->pool_config->get_pool_uuid($active_pool['URL'], $active_pool['User']);

                    // When we want to delete a pool which is currently in use, we have to switch first to another alive pool, else we can not delete it.
                    if ($uuid === $active_pool_uuid) {
                        // Loop through each pool wich are active within cgminer and get all pools which are alive.
                        $alive_pools = array();
                        foreach ($pools AS $pool) {
                            if ($pool['Status'] !== 'Alive') {
                                continue;
                            }
                            $alive_pools[$this->pool_config->get_pool_uuid($pool['URL'], $pool['User'])] = $pool;
                        }

                        // Remove the current mining pool from the alive pool array, because to this we can not switch.. we already there.
                        if (isset($alive_pools[$active_pool_uuid])) {
                            unset($alive_pools[$active_pool_uuid]);
                        }

                        // After removing active pool, check if there are some alive ones to which we can switch else we can't delete the pool.
                        if (empty($alive_pools)) {
                            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not delete the only active pool, there are no other pools which we can switch to it. CGMiner/SGMiner will not let you delete the pool which is currently in use');
                        }

                        // Get the pool where we can switch to.
                        $pool_to_switch = reset($alive_pools);

                        $pools_needs_to_switch[$rig] = array(
                            'pool_to_switch' => $pool_to_switch['POOL'],
                            'active_pool' => $active_pool['POOL'],
                        );
                    }
                    else {

                        // No need to switch something, just remove it.
                        $pools_needs_to_switch[$rig] = array(
                            'pool_to_switch' => null,
                            'active_pool' => $pool_to_remove,
                        );
                    }
                }
            } catch (Exception $ex) {
                continue;
            }
        }
        if (!empty($wait_msg)) {
            AjaxModul::return_code(305, null, true, 
                  "There are devices which have different last share pools, please wait until all devices have the same pool.\n"
                . "<b>Current active pools (Pool / Count of devices using this pool currently) per rig:</b>\n" . $wait_msg);
        }
        
        foreach ($pools_needs_to_switch AS $rig => $data) {
            
            // We only need to switch if we have an active pool which should be deleted.
            if ($data['pool_to_switch'] !== null) {
                // Switch to the pool.
                $this->get_rpc($rig)->switchpool($data['pool_to_switch']);
            }
            
            // Now we can remove the pool.
            $this->get_rpc($rig)->removepool($data['active_pool']);
        }
        
        $this->pool_config->remove_pool_by_uuid($uuid, $group);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    public function add_pool() {
        $params = new ParamStruct();
        $params->add_required_param('url', PDT_STRING);
        $params->add_required_param('user', PDT_STRING);
        $params->add_required_param('pass', PDT_STRING);
        $params->add_param('group', PDT_STRING, 'default');
        $params->add_param('quota', PDT_INT, 1);
        $params->add_param('rig_based', PDT_BOOL, false);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, array(
                'url' => $params->url,
                'user' => $params->user,
            ));
        }

        if (preg_match("/.rb/", $params->user)) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'Due to the internal system a worker username can not include the substring ".rb".');
        }
        if ($params->group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not add pools to donate group.');
        }
        
        $this->load_pool_config();
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        if ($params->rig_based) {
            foreach ($this->config->rigs AS $rig_data) {
                
                if (!empty($rig_data['disabled'])) {
                    continue;
                }
                
                $user = preg_replace("/[^a-zA-Z0-9]/", "", $rig_data['shortname']);
                $result = $this->pool_config->check_pool($params->url, $params->user . '.rb' . $user, $params->pass);
        
                if ($result !== true) {
                    AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, array(
                        'url' => $params->url,
                        'user' => $params->user . '.rb' . $user,
                    ), true, $params->user . '.rb' . $user . ' => ' . $result);
                }
            }
        }
        else {
            $result = $this->pool_config->check_pool($params->url, $params->user, $params->pass);

            if ($result !== true) {
                AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, array(
                    'url' => $params->url,
                    'user' => $params->user,
                ), true, $params->user . ' => ' . $result);
            }
        }
        $this->load_pool_config();
        
        // Before we adding the pool we have to check if the group to which we add the pool is in a rig active, if so we have to add the pool into the cgminer of the rig.
        foreach ($this->config->rigs AS $rig => $rig_data) {
            if (!empty($rig_data['disabled'])) {
                continue;
            }
            try {
                if ($this->pool_config->get_current_active_pool_group($this->get_rpc($rig)) === $params->group) {
                    if ($params->rig_based) {                
                        $user = $params->user . '.rb' . preg_replace("/[^a-zA-Z0-9]/", "", $rig_data['shortname']);
                    }
                    else {
                        $user = $params->user;
                    }

                    $this->get_rpc($rig)->addpool($params->url, $user, $params->pass);
                    $miner_config = $this->get_rpc($rig)->get_config();
                    $miner_config['pools'][] = array(
                        'url' => $params->url,
                        'user' => $user,
                        'pass' => $params->pass,
                    );

                    $this->get_rpc($rig)->set_config('pools', $miner_config['pools']);
                }
            }
            catch (Exception $e) {
                continue;
            }
        }
        
        // Now add the pool to the config.
        $this->pool_config->add_pool($params->url, $params->user, $params->pass, $params->group, $params->quota, $params->rig_based);
                
        AjaxModul::return_code(AjaxModul::SUCCESS, array(
            'url' => $params->url,
            'user' => $params->user,
            'rig_based' => $params->rig_based,
            'uuid' => $this->pool_config->get_pool_uuid($params->url, $params->user),
        ));
    }

    public function del_group() {
        $params = new ParamStruct();
        $params->add_required_param('group', PDT_STRING);
        $params->add_validator('group', new FunctionValidator('You can not delete the default group', function($value) {
            return $value != 'default';
        }));
        $params->fill();
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, implode("\n", $params->get_errors()));
        }
        
        if ($params->group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not delete donate group.');
        }

        $this->load_pool_config();
                
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        $rigs_in_use = array();
        foreach ($this->config->rigs AS $rig => $rig_data) {
            if (!empty($rig_data['disabled'])) {
                continue;
            }
            try {
                if ($this->pool_config->get_current_active_pool_group($this->get_rpc($rig)) === $params->group)  {
                    $rigs_in_use[] = ' - ' . $rig;
                }
            }
            catch(Exception $e) {
                continue;
            }
        }
        
        if (!empty($rigs_in_use)) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, "You can not delete this group because there exist rig's which currently have this group active.\n\nRig's which have this group active:\n" . implode("\n", $rigs_in_use));
        }
        
        $result = $this->pool_config->del_group($params->group);
        if ($result !== true) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, $result);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    public function change_group() {
        $params = new ParamStruct();
        $params->add_required_param('old_group', PDT_STRING);
        $params->add_required_param('group', PDT_STRING);
        $params->add_param('strategy', PDT_INT, 0);
        $params->add_param('rotate_period', PDT_INT, 0);
        $params->add_validator('old_group', new FunctionValidator('You can not change the default group', function($value) {
            return $value != 'default';
        }));
        $params->add_validator('group', new FunctionValidator('This group already exists.', function($value) {
            $pool_config = new PoolConfig();
            return !$pool_config->group_exists($value);
        }));
        $params->fill();
        
        $this->load_pool_config();
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, implode("\n", $params->get_errors()));
        }
        
        if ($params->group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'donate group is a special one, you can not add it.');
        }
        
        $rigs_in_use = array();
        foreach ($this->config->rigs AS $rig => $rig_data) {
            if (!empty($rig_data['disabled'])) {
                continue;
            }
            try {
                if ($this->pool_config->get_current_active_pool_group($this->get_rpc($rig)) === $params->group)  {
                    $rigs_in_use[] = ' - ' . $rig;
                }
            }
            catch(Exception $e) {
                continue;
            }
        }
        
        if (!empty($rigs_in_use)) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, "You can not change this group because there exist rig's which currently have this group active.\n\nRig's which have this group active:\n" . implode("\n", $rigs_in_use));
        }
        
        $result = $this->pool_config->update_group($params->old_group, $params->group, $params->strategy, $params->rotate_period);
        if ($result !== true) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, $result);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    public function add_group() {
        $params = new ParamStruct();
        $params->add_required_param('group', PDT_STRING);
        $params->add_param('strategy', PDT_INT, 0);
        $params->add_param('rotate_period', PDT_INT, 0);
        $params->add_validator('group', new FunctionValidator('This group already exists.', function($value) {
            $pool_config = new PoolConfig();
            return !$pool_config->group_exists($value);
        }));
        $params->fill();
        
        if (!$this->access_control->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, implode("\n", $params->get_errors()));
        }
        
        if ($params->group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'donate group is a special one, you can not add it.');
        }

        $this->load_pool_config();
        
        $result = $this->pool_config->add_group($params->group, $params->strategy, $params->rotate_period);
        if ($result !== true) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, $result);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

}
