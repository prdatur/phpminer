Soopfw.behaviors.main_settings = function() {
    $('#save_config').off('click').on('click', function() {
        var values = {};
        $('input', $('#system_settings')).each(function() {
            if ($(this).attr('type') === 'checkbox') {
                values[$(this).attr('name')] = ($(this).prop('checked')) ? $(this).val() : '0';
            }
            else {
                values[$(this).attr('name')] = $(this).val();
            }
        });
        ajax_request(murl('main', 'save_settings'), {settings: values}, function() {
            success_alert('Configuration saved successfully.');
        });
    });
    
    $('#save_cgminer_config').off('click').on('click', function() {
        var values = {};
        $('input', $('#cgminer_settings')).each(function() {
            values[$(this).attr('name')] = $(this).val();
        });
        
        ajax_request(murl('main', 'save_cgminer_settings'), {settings: values}, function() {
            success_alert('Configuration saved successfully.');
        });
    });
};