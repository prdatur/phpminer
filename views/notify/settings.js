Soopfw.behaviors.notify_settings = function() {
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
    
    $('.save_config_notify').off('click').on('click', function() {
        var values = {};
        $('.rig_data').each(function() {
            var rig = $(this).data('tab_title');
            values[rig] = {};
            $('input, select', $('.notification_settings[data-rig="' + rig + '"]')).each(function() {
                if ($(this).attr('type') === 'checkbox') {
                    values[rig][$(this).attr('name')] = ($(this).prop('checked')) ? $(this).val() : '0';
                }
                else {
                    values[rig][$(this).attr('name')] = $(this).val();
                }
            });
        });
        
        ajax_request(murl('notify', 'save_settings'), {settings: {rigs: values}}, function() {
            success_alert('Configuration saved successfully.');
        });
    });
   
    $('.tabs').each(function() {
        
        var tab_headers = $('<ul class="tab_links">');
        $('div[data-tab]').addClass('js').each(function() {
            tab_headers.append('<li id="tab-' + $(this).data('tab') + '">' + $(this).data('tab_title') + '</li>');
        });
        $(this).prepend(tab_headers);
        var that = this;
        $(".tab_links li", this).each(function() {

                $(this).click(function() {
                        $('div[data-tab]', that).hide();
                        $('div[data-tab="' + $(this).attr('id').split('-')[1] + '"]', that).show();
                        $(this).addClass('selected').siblings().removeClass('selected');
                });

        });
        $('li:first-child', this).click();
    });
};