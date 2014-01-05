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
            $this->assign('unconfigured_pools', $this->api->get_pools());
        }
    }

    public function change_pool() {
        $params = new ParamStruct();
        $params->add_required_param('pk', PDT_STRING);
        $params->add_required_param('name', PDT_STRING);
        $params->add_required_param('value', PDT_STRING);

        $params->fill();
        if (!$params->is_valid(true)) {
            header("HTTP/1.0 400 Bad request");
            echo implode("\n", $params->get_errors());
            exit();
        }
        list($uuid, $group) = explode("|", $params->pk, 2);
        $this->load_pool_config();
        $old_pool = $this->pool_config->get_pool($uuid, $group);
        $old_pool[$params->name] = $params->value;
        
        $current_group = $this->pool_config->get_current_active_pool_group($this->api);
        if ($current_group === $group)  {
            
            if ($this->has_advanced_api) {
                $devices = $this->api->get_devices();
                $current_pool = null;
                $change_pool = null;
                foreach ($devices AS $device) {

                    // Check if we have our own cgminer fork with advanced api.
                    if (isset($device['Current Pool'])) {
                        $current_pool = $device['Current Pool'];

                    }
                    else {
                        $current_pool = $device['Last Share Pool'];
                    }
                    break;
                }
                foreach ($this->api->get_pools() AS $pool) {
                    if ($this->pool_config->get_pool_uuid($pool['URL'], $pool['User']) === $uuid) {
                        $change_pool = $pool['POOL'];
                    }
                }

                if ($current_pool === $change_pool) {
                    header("HTTP/1.0 400 Bad request");
                    echo 'The active pool can not be changed.';
                    exit();
                }
                
                $this->api->updatepool($change_pool, $old_pool['url'], $old_pool['user'], $old_pool['pass']);
                $this->api->set_poolquota($change_pool, $old_pool['quota']);
                
            }
            else {
                header("HTTP/1.0 400 Bad request");
                echo 'The active pool group can not be changed.';
                exit();
            }
        }
        
        $this->pool_config->update_pool($uuid, $old_pool['url'], $old_pool['user'], $old_pool['pass'], $old_pool['quota']);
        die();
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
        
        $cfg_groups = $this->pool_config->get_config();
        $cfg_pools = $cfg_groups[$group];
        if ($group === 'default' && count($cfg_pools) == 1) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not remove this pool, it is the only one within the default group. If you want to delete this pool, please add a pool to the default group first.');
        }
        if ($group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not remove the donate pool group.');
        }
        
        // Get all configured pools within cgminer.
        $pools = $this->api->get_pools();
        
        // Determine which pool group we are currently using.        
        $current_active_group = $this->pool_config->get_current_active_pool_group($this->api);
        
        // If we want to delete a pool from the group which is currently active, we need to check additional things, in order to not delete the current active pool, cgminer would not let us do this.
        if ($current_active_group === $group) {
            
            // We need to check if we want to delete the pool which is currently active.
            $active_pools = array();
            
            // Get all devices.
            $devices = $this->api->get_devices();
            
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
                $msg =  "There are devices which have different last share pools, please wait until all devices have the same pool.\n"
                      . "<b>Current active pools (Pool / Count of devices using this pool currently):</b>\n";
                foreach ($active_pools AS $pool_id => $device_count) {
                   $msg .= $sorted_pools[$pool_id]['URL'] . " (" . $device_count . ")\n"; 
                }
                AjaxModul::return_code(305, null, true, $msg);
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
                    AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not delete the only active pool, there are no other pools which we can switch to it. CGMiner will not let you delete the pool which is currently in use');
                }
                
                // Get the pool where we can switch to.
                $pool_to_switch = reset($alive_pools);
                
                if (!$params->confirm) {
                     AjaxModul::return_code(AjaxModul::NEED_CONFIRM, null, true, 
                               "You are going to delete a pool which is currently in use.\n"
                             . "In order to do this, the system needs to switch to a different pool within the mining group.\n"
                             . "The pool which will be used is:\n"
                             . "<b>" . $pool_to_switch['URL'] . " (" . $pool_to_switch['User'] . ")\n\n"
                             . "Do you want to proceed?</b>");
                }
                
                // Switch to the pool.
                $this->api->switchpool($pool_to_switch['POOL']);
                
                // Now we can remove the active pool.
                $this->api->removepool($active_pool['POOL']);
            }
            else {
                
                // No need to switch something, just remove it.
                $this->api->removepool($pool_to_remove);
            }
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

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, array(
                'url' => $params->url,
                'user' => $params->user,
            ));
        }

        if ($params->group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'You can not add pools to donate group.');
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
        $this->pool_config->add_pool($params->url, $params->user, $params->pass, $params->group, $params->quota);
        AjaxModul::return_code(AjaxModul::SUCCESS, array(
            'url' => $params->url,
            'user' => $params->user,
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
        $result = $this->pool_config->del_group($params->group);
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
        
        if ($params->group === 'donate') {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'donate group is a special one, you can not add it.');
        }
        
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, implode("\n", $params->get_errors()));
        }

        $this->load_pool_config();
        $result = $this->pool_config->add_group($params->group, $params->strategy, $params->rotate_period);
        if ($result !== true) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, $result);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

}
