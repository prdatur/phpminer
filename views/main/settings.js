Soopfw.behaviors.main_settings = function() {
    if (phpminer.settings.rigs !== undefined) {
        $.each(phpminer.settings.rigs, function(rig, rig_data) {
            var configs_to_add = $.extend({}, phpminer.settings.possible_configs);

            if (rig_data.cgminer_conf === undefined) {
                rig_data.cgminer_conf = {};
            }

            $.each(rig_data.cgminer_conf, function(k, v) {
                if (k === 'pools' || phpminer.settings.possible_configs[k] === undefined) {
                    return;
                }
                delete configs_to_add[k];
                add_config(rig, k, v);
            });

            $.each(configs_to_add, function(k, v) {
               $('.add_config_key[data-rig="' + rig + '"]').append('<option value="' + k + '">' + k + ' - ' + v.description + '</option>'); 
            });

            $('.add_config_key[data-rig="' + rig + '"]').change(function() {
                var key = $(this).val();
                $(this).val("");
                if(phpminer.settings.possible_configs[key] !== undefined) {
                    add_config(rig, key);
                    $('option[value="' + key + '"]', this).remove();
                    $('*[data-toggle="tooltip"]').tooltip();
                }

            });
        });
    }
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
        ajax_request(murl('main', 'save_settings'), {settings: values}, function(result) {
            if (result !== undefined && result !== null && result['url'] !== undefined) {
                Soopfw.location(result.url);
            }
            else {
                success_alert('Configuration saved successfully.');
            }
        });
    });
    
    $('.save_cgminer_config').off('click').on('click', function() {
        var values = {};
        $('input', $('#cgminer_settings')).each(function() {
            values[$(this).attr('name')] = $(this).val();
        });
        
        var values = {};
        $('.rig_data').each(function() {
            var rig = $(this).data('tab_title');
            values[rig] = {};
            $('input, select', $('.cgminer_settings[data-rig="' + rig + '"] tbody')).each(function() {
                if ($(this).attr('type') === 'checkbox') {
                    values[rig][$(this).attr('name')] = ($(this).prop('checked')) ? $(this).val() : '0';
                }
                else {
                    values[rig][$(this).attr('name')] = $(this).val();
                }
            });
        });
        wait_dialog()
        ajax_request(murl('main', 'save_cgminer_settings'), {settings: values}, function() {
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
    
    var pre_val = 0;
    if (!empty(phpminer.settings.config['donation'])) {
        pre_val = parseInt(phpminer.settings.config['donation']);
    }
    $('#donation').noUiSlider({
        range: [5, 241],
        start: pre_val,
        handles: 1,
        margin: 2,
        step: 1,
        decimals: 1,
        slide: function() {
            var value = parseInt($(this).val());
            if (value === 5) {
                value = 0;
            }
            else {
                value--;
            }
            
            $('.donation_new_value_percent').html(Math.round(((100/1440) * value)*100)/100);
            $('.donation_new_value').html(Math.round(value));
        }
    }).change(function() {
        var value = parseInt($(this).val());
        if (value === 5) {
            value = 0;
        }
        else {
            value--;
        }
        $('#donation_val').val(value);
    });
    $('.donation_new_value_percent').html(Math.round(((100/1440) * pre_val)*100)/100);
    $('.donation_new_value').html(Math.round(pre_val));

};

function add_config(rig, key, value) {
    var html = '';
    html += '   <td class="value">';
    if (typeof(phpminer.settings.possible_configs[key].values) === "object" || (phpminer.settings.possible_configs[key].values === PDT_BOOL && !phpminer.settings.possible_configs[key].multivalue)){
        html += '       <select id="' + key+ '" name="' + key+ '">';
        if (phpminer.settings.possible_configs[key].values === PDT_BOOL) {
            html += '       <option value="true"' + (('true' === value) ? ' selected="selected"' : '') +'>True</option>';
            html += '       <option value="false"' + (('false' === value) ? ' selected="selected"' : '') +'>False</option>';
        }
        else {
            $.each(phpminer.settings.possible_configs[key].values, function(k2, v2) {
                html += '       <option value="' + v2 + '"' + ((v2 === value) ? ' selected="selected"' : '') +'>' + v2 + '</option>';
            });
        }
        html += '       </select>';
    }
    else {
        if (value !== undefined) {
            value = ' value="' + value + '"';
        }
        else {
            value = '';
        }
        html += '       <input type="text" id="' + key+ '" name="' + key+ '"' + value + ' />';
    }
    html += '   </td>';
    $('.cgminer_settings_container[data-rig="' + rig + '"]').append(
            $('<tr data-key="' + key+ '">')
                .append('<td class="key"><label for="' + key + '">' + key+ '</label><i data-toggle="tooltip" style="float:right;" title="' + phpminer.settings.possible_configs[key].description + '" class="icon-help-circled"></i></td>')
                .append(html)
                .append($('<td style="text-align: center;" class="clickable"><i class="icon-trash"></i></td>').off('click').on('click', function() {
                    var key = $(this).parent().data('key');
                    $('.add_config_key[data-rig="' + rig + '"]').append('<option value="' + key + '">' + key + ' - ' + phpminer.settings.possible_configs[key].description + '</option>'); 
                    $(this).parent().fadeOut('fast', function() {
                        $(this).remove();
                    });
                }))
    );
}