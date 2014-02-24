<?php

/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'includes/validators/FunctionValidator.class.php';

class gpu extends Controller {

    private function set_cfg($rig, $gpu, $key, $value) {
        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        $rigs = $this->config->rigs;

        if (empty($rigs[$rig]['gpu_' . $gpu])) {
            $rigs[$rig]['gpu_' . $gpu] = array();
        }
        if (!is_array($key)) {
            $key = array($key);
        }
        $val_array = &$rigs;
        array_unshift($key, $rig, 'gpu_' . $gpu);
        foreach ($key AS $sub_key) {
            if (!isset($val_array[$sub_key])) {
                $val_array[$sub_key] = array();
            }
            $val_array = &$val_array[$sub_key];
        }
        $val_array = $value;
        $this->config->rigs = $rigs;
    }

    public function set_load_config() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('min', PDT_INT);
        $params->add_validator('min', new FunctionValidator('Min. load value out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 100);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        $this->set_cfg($params->rig, $params->gpu, array('load', 'min'), $params->min);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    public function set_hw_config() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('max', PDT_INT);
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $this->set_cfg($params->rig, $params->gpu, array('hw', 'max'), $params->max);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    public function set_hashrate_config() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('min', PDT_INT);
        $params->add_validator('min', new FunctionValidator('Min. hashrate value out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 15000);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        $this->set_cfg($params->rig, $params->gpu, array('hashrate', 'min'), $params->min);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    public function set_temp_config() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('min', PDT_INT);
        $params->add_required_param('max', PDT_INT);
        $params->add_validator('min', new FunctionValidator('Min. temperature value out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 100);
        }));
        $params->add_validator('max', new FunctionValidator('Max. temperature value out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 100);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        $this->set_cfg($params->rig, $params->gpu, array('temperature', 'min'), $params->min);
        $this->set_cfg($params->rig, $params->gpu, array('temperature', 'max'), $params->max);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }

    public function set_fan_speed() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('speed', PDT_INT);
        $params->add_validator('speed', new FunctionValidator('Speed out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 100);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        try {
            // Just get the api to first check if all connections are fine. 
            $this->get_rpc($params->rig);
            
            $this->get_rpc($params->rig)->set_gpufan($params->gpu, $params->speed);
            $this->get_rpc($params->rig)->set_config('gpu-fan', $params->speed, $params->gpu, $this->get_rpc($params->rig));
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    public function set_intensity() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        $params->add_validator('value', new FunctionValidator('value out of range, can only between 8 and 20', function($value) {
            return ($value >= 8 && $value <= 20);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        try {
            // Just get the api to first check if all connections are fine.
            $this->get_rpc($params->rig);
            
            $this->get_rpc($params->rig)->set_gpuintensity($params->gpu, $params->value);
            $this->get_rpc($params->rig)->set_config('intensity', $params->value, $params->gpu, $this->get_rpc($params->rig));
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    public function set_voltage() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_FLOAT);
        $params->add_validator('value', new FunctionValidator('value out of range, can only between 0.800 and 1.300', function($value) {
            return ($value >= 0.8 && $value <= 1.3);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        try {
            // Just get the api to first check if all connections are fine.
            $this->get_rpc($params->rig);
            
            $this->get_rpc($params->rig)->set_gpuvddc($params->gpu, $params->value);
            $this->get_rpc($params->rig)->set_config('gpu-vddc', $params->value, $params->gpu, $this->get_rpc($params->rig));
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    public function enable_gpu() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);

        $params->fill();

        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER . null, true, implode("\n", $params->get_errors()));
        }
        
        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        try {
            if ($params->value === 1) {
                $this->get_rpc($params->rig)->gpuenable($params->gpu);
            } else {
                $this->get_rpc($params->rig)->gpudisable($params->gpu);
            }
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    public function set_memory_clock() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        $params->add_validator('value', new FunctionValidator('Speed out of range, can only between 0 and 9999', function($value) {
            return ($value >= 0 && $value <= 9999);
        }));

        $params->fill();

        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER . null, true, implode("\n", $params->get_errors()));
        }

        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        try {
            // Just get the api to first check if all connections are fine.
            $this->get_rpc($params->rig);
            
            $this->get_rpc($params->rig)->set_gpumem($params->gpu, $params->value);
            $this->get_rpc($params->rig)->set_config('gpu-memclock', $params->value, $params->gpu, $this->get_rpc($params->rig));
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

    public function set_engine_clock() {
        $params = new ParamStruct();
        $params->add_required_param('rig', PDT_STRING);
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        $params->add_validator('value', new FunctionValidator('Speed out of range, can only between 0 and 9999', function($value) {
            return ($value >= 0 && $value <= 9999);
        }));

        $params->fill();

        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }

        if (!$this->access_control->has_permission(AccessControl::PERM_DEVICE_OVERCLOCK)) {
            AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
        }
        
        try {
            // Just get the api to first check if all connections are fine.
            $this->get_rpc($params->rig);
            
            $this->get_rpc($params->rig)->set_gpuengine($params->gpu, $params->value);
            $this->get_rpc($params->rig)->set_config('gpu-engine', $params->value, $params->gpu, $this->get_rpc($params->rig));
            AjaxModul::return_code(AjaxModul::SUCCESS);
        } catch (APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        } catch (APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }

}
