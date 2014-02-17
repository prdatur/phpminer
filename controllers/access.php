<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class access extends Controller {
    
   
    public function settings() {
       $config = new Config(SITEPATH . '/config/notify.json');
       $this->assign('config', $config->get_config());
       $this->assign('rigs', array_keys($this->config->rigs));
    }
    
    /**
     * Add a user.
     */
    public function user_add() {
        $params = new ParamStruct();
        $params->add_param('old_user', PDT_STRING, '');
        $params->add_required_param('user', PDT_STRING);
        $params->add_required_param('password', PDT_STRING);

        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
        }
        if ($params->is_submitted()) {
            $old_user = $params->old_user;
            if (empty($old_user)) {
                $result = $this->access_control->get_config()->user_add($params->user, $params->pass);
            }
            else {
                $result = $this->access_control->get_config()->user_update($params->old_user, $params->user, $params->pass);
            }
            if ($result) {
                AjaxModul::return_code(AjaxModul::SUCCESS);
            }
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, 'Could not add or update the user');
        }
    }
    
}