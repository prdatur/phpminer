<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'includes/validators/FunctionValidator.class.php';
class notify extends Controller {
    
   
    public function settings() {
       $config = new Config(SITEPATH . '/config/notify.json');
       $this->assign('config', $config->get_config());
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
        $config = new Config(SITEPATH . '/config/notify.json');
        foreach ($params->settings AS $key => $val) {
            $config->set_value($key, $val);
        }
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
}