Soopfw.behaviors.main_settings = function() {
    $('#save_config').off('click').on('click', function() {
        var values = {};
        $('input, select', $('#system_settings')).each(function() {
            if ($(this).attr('type') === 'checkbox') {
                values[$(this).attr('name')] = ($(this).prop('checked')) ? $(this).val() : '0';
            }
            else {
                values[$(this).attr('name')] = $(this).val();
            }
        });
        ajax_request(murl('notify', 'save_settings'), {settings: values}, function() {
            success_alert('Configuration saved successfully.');
        });
    });
    
    $('#save_config_notify').off('click').on('click', function() {
        var values = {};
        $('input, select', $('#notification_settings')).each(function() {
            if ($(this).attr('type') === 'checkbox') {
                values[$(this).attr('name')] = ($(this).prop('checked')) ? $(this).val() : '0';
            }
            else {
                values[$(this).attr('name')] = $(this).val();
            }
        });
        ajax_request(murl('notify', 'save_settings'), {settings: values}, function() {
            success_alert('Configuration saved successfully.');
        });
    });
    
};