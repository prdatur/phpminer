<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'includes/validators/FunctionValidator.class.php';
class notify extends Controller {
    
   
    public function settings() {
       AccessControl::check_permission(AccessControl::PERM_CHANGE_NOTIFICATION_SETTINGS);
       $this->assign('config', $this->config->get_config('notify'));
       $this->assign('rigs', array_keys($this->config->rigs));
    }
    
    /**
     * Ajax request to save new configuration settings.
     */
    public function save_settings() {
        AccessControl::check_permission(AccessControl::PERM_CHANGE_NOTIFICATION_SETTINGS);
        $params = new ParamStruct();
        $params->add_required_param('settings', PDT_ARR);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        db::getInstance()->begin();
        foreach ($params->settings AS $key => $val) {
            $this->config->set_value($key,  $val, 'notify');
        }
        db::getInstance()->commit();
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
}