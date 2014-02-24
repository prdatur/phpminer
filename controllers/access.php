<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'includes/validators/FunctionValidator.class.php';
class access extends Controller {
    
   
    /**
     * Display a list of all users
     */
    public function index() {
        
        if (!AccessControl::getInstance()->is_enabled()) {
            throw new AccessException('Access control is disabled, if you working alone with your own rigs you don\'t need access control, otherweise enable it first within the main settings');
        }
        $users = array();
        $groups = array();
        try {
            AccessControl::check_permission(AccessControl::PERM_MANAGE_USERS);
            foreach ($this->access_control->get_config()->user_get() AS $user) {
                $users[$user] = $this->access_control->get_config()->user_get($user);
                unset($users[$user]['password']);
            }
            foreach ($this->access_control->get_config()->group_get() AS $group) {
                $groups[$group] = $this->access_control->get_config()->group_get($group);
                $groups[$group]['permissions'] = $this->access_control->get_config()->group_get_permission($group);
            }
        }  catch (Exception $e) {
            $users = false;
            $groups = false;
        }
        $this->js_config('users', $users);
        $this->js_config('groups', $groups);
        
        $this->js_config('possible_permissions', AccessControl::get_permission_array());
    }
    
    
    /**
     * Delete a user.
     */
    public function delete_user() {
        AccessControl::check_permission(AccessControl::PERM_MANAGE_USERS);
        $params = new ParamStruct();
        $params->add_required_param('username', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        $this->access_control->get_config()->user_del($params->username);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    /**
     * Add/Change a user.
     */
    public function user_add() {
        AccessControl::check_permission(AccessControl::PERM_MANAGE_USERS);
        $params = new ParamStruct();
        $params->add_param('old_user', PDT_STRING, '');
        $params->add_param('group', PDT_STRING, null);
        $params->add_required_param('username', PDT_STRING);
        $params->add_param('password', PDT_STRING, null);
        
        $params->fill();
        
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        if ($params->username !== $params->old_user && AccessControl::getInstance()->get_config()->user_exists($params->username)) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'This user already exists.');
        }
        
        if (empty($params->old_user)) {
            if (empty($params->password)) {
                AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'Password can only be left empty when you edit a user');
            }
        }
        $current_users = $this->access_control->get_config()->user_get();
        if (!empty($current_users)) {
            $group = $params->group;
            if (empty($group)) {
                AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, "You didn't provided a access group");                
            }
        }
        $old_user = $params->old_user;
        if (empty($old_user)) {
            if (empty($current_users)) {
                if (!$this->access_control->get_config()->group_exists('admin')) {
                    $this->access_control->get_config()->group_add('admin');
                }
                $group = 'admin';
                $this->access_control->get_config()->group_grant_permission('admin', '*');
            }
            $result = $this->access_control->get_config()->user_add($params->username, $params->password, $group);
        }
        else {
            $result = $this->access_control->get_config()->user_update($params->old_user, $params->username, (empty($params->password)) ? null : $params->password, $params->group);
        }
        if ($result) {
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'Could not add or update the user');
    }
    
    /**
     * Delete  a user group.
     */
    public function delete_group() {
        AccessControl::check_permission(AccessControl::PERM_MANAGE_USERS);
        $params = new ParamStruct();
        $params->add_required_param('name', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        
        $this->access_control->get_config()->group_delete($params->name);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    /**
     * Add/Change a group.
     */
    public function group_add() {
        AccessControl::check_permission(AccessControl::PERM_MANAGE_USERS);
        $params = new ParamStruct();
        $params->add_param('old_name', PDT_STRING, '');
        $params->add_required_param('name', PDT_STRING);
        $params->add_param('permissions', PDT_ARR, array());
        
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }

        $old = $params->old_name;
        
        if ($params->name !== $old && AccessControl::getInstance()->get_config()->group_exists($params->name)) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'This group already exists.');
        }
        
        if (empty($old)) {
            $result = $this->access_control->get_config()->group_add($params->name);
        }
        else {
            $this->access_control->get_config()->group_revoke_all_permission($old);
            $result = $this->access_control->get_config()->group_change($old, $params->name);
        }
        
        
        
        foreach ($params->permissions AS $permission) {
            $this->access_control->get_config()->group_grant_permission($params->name, $permission);
        }
        
        if ($result) {
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'Could not add or update the user');
    }
    
}