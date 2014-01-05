<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'includes/validators/FunctionValidator.class.php';
class gpu extends Controller {
    
   
    public function set_load_config() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('min', PDT_INT);
        $params->add_validator('min', new FunctionValidator('Min. load value out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 100);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        $gpu_config = $this->config->get_value('gpu_' . $params->gpu);
        $gpu_config['load'] = array(
            'min' => $params->min,
        );
        $this->config->set_value('gpu_' . $params->gpu, $gpu_config);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    public function set_hashrate_config() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('min', PDT_INT);
        $params->add_validator('min', new FunctionValidator('Min. hashrate value out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 15000);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        $gpu_config = $this->config->get_value('gpu_' . $params->gpu);
        $gpu_config['hashrate'] = array(
            'min' => $params->min,
        );
        $this->config->set_value('gpu_' . $params->gpu, $gpu_config);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    public function set_temp_config() {
        $params = new ParamStruct();
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
        
        $gpu_config = $this->config->get_value('gpu_' . $params->gpu);
        $gpu_config['temperature'] = array(
            'min' => $params->min,
            'max' => $params->max,
        );
        $this->config->set_value('gpu_' . $params->gpu, $gpu_config);
        AjaxModul::return_code(AjaxModul::SUCCESS);
    }
    
    public function set_fan_speed() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('speed', PDT_INT);
        $params->add_validator('speed', new FunctionValidator('Speed out of range, can only between 0 and 100', function($value) {
            return ($value >= 0 && $value <= 100);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        try {
            $this->api->set_gpufan($params->gpu, $params->speed);
            $this->config->set_cgminer_value($this->api, 'gpu-fan', $params->speed, $params->gpu);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        catch(APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        catch(APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        
    }
    
    public function set_intensity() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        $params->add_validator('value', new FunctionValidator('value out of range, can only between 8 and 20', function($value) {
            return ($value >= 8 && $value <= 20);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        try {
            $this->api->set_gpuintensity($params->gpu, $params->value);
            $this->config->set_cgminer_value($this->api, 'intensity', $params->value, $params->gpu);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        catch(APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        catch(APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        
    }
    
    public function set_voltage() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_FLOAT);
        $params->add_validator('value', new FunctionValidator('value out of range, can only between 0.800 and 1.300', function($value) {
            return ($value >= 0.8 && $value <= 1.3);
        }));
        $params->fill();
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        try {
            $this->api->set_gpuvddc($params->gpu, $params->value);
            $this->config->set_cgminer_value($this->api, 'gpu-vddc', $params->value, $params->gpu);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        catch(APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        catch(APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        
    }
    
    public function enable_gpu() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        
        $params->fill();
        
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER. null, true, implode("\n", $params->get_errors()));
        }
        
        try {
            if ($params->value === 1) {
                $this->api->gpuenable($params->gpu);
            }
            else {
                $this->api->gpudisable($params->gpu);
            }
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        catch(APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        catch(APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }
    
    public function set_memory_clock() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        $params->add_validator('value', new FunctionValidator('Speed out of range, can only between 0 and 9999', function($value) {
            return ($value >= 0 && $value <= 9999);
        }));
        
        $params->fill();
        
        if (!$params->is_valid(true)) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER. null, true, implode("\n", $params->get_errors()));
        }
        
        try {
            $this->api->set_gpumem($params->gpu, $params->value);
            $this->config->set_cgminer_value($this->api, 'gpu-memclock', $params->value, $params->gpu);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        catch(APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        catch(APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }
    
    public function set_engine_clock() {
        $params = new ParamStruct();
        $params->add_required_param('gpu', PDT_INT);
        $params->add_required_param('value', PDT_INT);
        $params->add_validator('value', new FunctionValidator('Speed out of range, can only between 0 and 9999', function($value) {
            return ($value >= 0 && $value <= 9999);
        }));
        
        $params->fill();
        
        if (!$params->is_valid()) {
            AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
        }
        
        try {
            $this->api->set_gpuengine($params->gpu, $params->value);
            $this->config->set_cgminer_value($this->api, 'gpu-engine', $params->value, $params->gpu);
            AjaxModul::return_code(AjaxModul::SUCCESS);
        }
        catch(APIRequestException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
        catch(APIException $ex) {
            AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $ex->getMessage());
        }
    }
}