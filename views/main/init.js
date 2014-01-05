var refresh_device_list_timeout = null;
var mem = 1375;
var ttt = null;
var iii = 30;

function get_config(name, default_val) {    
    if (phpminer.settings.config === undefined) {
        return default_val;
    }
    
    var cfg = $.extend({}, phpminer.settings.config);
    for(var i = 0; i < name.length; i++) {
        if (cfg[name[i]] === undefined) {
            return default_val;
        }
        cfg = cfg[name[i]];
    }
    return cfg;
} 

Soopfw.behaviors.main_init = function() {
    
    set_device_list(phpminer.settings.device_list);

    if (refresh_device_list_timeout !== null) {
        clearInterval(refresh_device_list_timeout);
    }
    refresh_device_list_timeout = setInterval(refresh_device_list, get_config('ajax_refresh_intervall', 5000));

    if (empty(phpminer.settings.active_pool_group)) {
        var msg = "PHPMiner could not find the current active group.\n"
                + "This means that the current configurated pools within cgminer are not equal with the pools within a pool group.\n"
                + "PHPMiner needs to know which pool group is currently active.\n"
                + "To fix this issue, you have 2 possibilities.\n\n"
                + "<b>1. Auto create pool group</b>\n"
                + "If you choose this method, a pool group called \"cgminer\" will be created and all pools which are currently configurated will be added to this pool. If a group with this name already exists it will be overridden.\n"
                + "<b>Notice:</b>\n"
                + "<b>Before the pool group cgminer is created, you will be asked for the worker password for each pool, because the cgminer api has no feature to retrieve worker passwords.:</b>\n\n"
                + "<b>2. Manual</b>\n"
                + "If you choose this method, you will be redirected to a page where you can self add the missing pools within an existing group and/or remove pools from an existing group which are not within cgminer.\n\n"
                + "Please choose";
        question(msg, null, 'Configuration missmatch', function() {
            wait_dialog('Please wait');
            ajax_request('/main/get_cgminer_pools.json', null, function(result) {
                $.alerts._hide();
                var dialog = ""
                    + "<div style='margin-top: 5px;bottom:15px;'>"
                    + "PHPMiner can only read out the current configured pools without the password.<br />"
                    + "So please provide your worker passwords here."
                    + "</div>";
                dialog += '    <div class="simpleform">';
                $.each(result, function(k, pool) {
                    dialog += '        <div class="form-element">';
                    dialog += '            <b>' + pool.URL + ': </b><br />';
                    dialog += '            Username: ' + pool.User + '<br />';
                    dialog += '            <label for="' + pool.POOL + '">Password:</label>';
                    dialog += '            <input type="text" id="' + pool.POOL + '" data-url="' + pool.URL + '" data-user="' + pool.User + '" class="pool_pws">';
                    dialog += '        </div>';
                });
                dialog += '    </div>';
                var pools = {};
                make_modal_dialog('Save pools', dialog, [
                {
                        title: 'Save',
                        type: 'primary',
                        id: 'main_fix_pools_set_value',
                        data: {
                            "loading-text": 'Saving...'
                        },
                        click: function() {


                            $('.pool_pws').each(function() {
                                if (pools[$(this).data('url') + '|' + $(this).data('user')] === undefined) {
                                    pools[$(this).data('url') + '|' + $(this).data('user')] = {
                                        url: $(this).data('url'),
                                        user: $(this).data('user'),
                                        pass: $(this).val(),
                                        valid: 0
                                    };
                                }

                                pools[$(this).data('url') + '|' + $(this).data('user')]['pass'] = $(this).val();
                            });
                            wait_dialog('Please wait, while the save process is in progress. Each pool must be validated. The wait time differs from the amount of pools which needs to be checked.');
                            ajax_request('/main/fix_pool.json', {pools: pools}, function(result) {
                                $.alerts._hide();
                                $('#main_fix_pools_set_value').button('reset');
                                var alert_msg = "Some pools are invalid due to an invalid pool url, worker user or/and worker password.\n\n<b>The following pools are invalid:</b>\n";
                                if (!empty(result)) {
                                    var valid = true;
                                    $.each(result, function(k, v) {
                                        if (v !== true) {
                                            valid = false;
                                            var con_data = k.split('|');
                                            var input = $('[data-url="' + con_data[0] + '"][data-user="' + con_data[1] + '"]');
                                            alert_msg += 'Pool:<b>' + con_data[0] + "</b>\nUser: <b>" + con_data[1] + "</b>\nError: <b>" + v + "</b>\n\n";

                                            // If an errr occured which is not an invalid username or password, we need to provide the possibility to remove the pool. It can happen that an invalid pool is
                                            // configurated within cgminer and it must be possible to delete those invalid ones, else we could never get a good result within auto mode.

                                            if (v !== 'Username and/or password incorrect.') {
                                                $('a.remove_pool', $(input).parent()).remove();
                                                input.after(
                                                    $('<a href="javascript:void(0);" data-pool="' + input.attr('id') + '" data-url="' + con_data[0] + '" data-user="' + con_data[1] + '" class="remove_pool">Delete</a>').off('click').on('click', function() {
                                                        var that = this;
                                                        var url = $(this).data('url');
                                                        var user = $(this).data('user');
                                                        confirm("Do you really want to remove this pool from cgminer?\nPool: " + url + "\nUser: " + user, 'Delete pool from cgminer', function() {
                                                            ajax_request('/main/remove_pool_from_cgminer.json', {pool: $(that).data('pool')}, function() {
                                                                delete pools[$(that).data('url') + '|' + $(that).data('user')];
                                                                $('#' + $(that).data('pool')).parent().fadeOut('slow', function() {
                                                                    $(this).remove();
                                                                });
                                                            });
                                                        });
                                                    })
                                                );
                                            }
                                        }
                                        else {
                                            pools[k]['valid'] = 1;
                                        }
                                    });
                                }

                                if (valid === true) {
                                    $('.modal').modal('hide');
                                    Soopfw.reload();
                                }
                                else {
                                   alert(alert_msg); 
                                }
                            }, function() {
                                $.alerts._hide();
                                $('#main_fix_pools_set_value').button('reset');
                            });
                        }
                    }
                ], {
                    width: 400
                });
            });
        }, function() {
            Soopfw.location('/main/fix_pool_manual');
        }, {ok: '1. Auto create pool group', cancel: '2. Manual', width: 800});
    }
    $('#current_pool_group').off('change').on('change', function(){
        wait_dialog('<img style="margin-top: 7px;margin-bottom: 7px;" src="/templates/ajax-loader.gif"/><br>Please wait until the new pool group is activated. This takes some time because PHPMiner needs to verify that the last active pool is one of the newly added one.<br /><b>Do not close this window (refresh page), else the old pools will remain and only the first pool within the group is added.');
        ajax_request('/main/switch_pool_group.json', {group: $(this).val()}, function(new_pools) {
            update_pools(new_pools);
            $.alerts._hide();
        });
    });
    
    $('#current_pool_pool').off('change').on('change', function(){
        ajax_request('/main/switch_pool.json', {pool: $(this).val()}, function() {
            success_alert('Pool switched successfully, Within the overview, the pool will be updated after the first accepted share was send to the new pool, this can take some time.', null, null, 10000);
        });
    });
    update_pools();
};

function update_pools(new_pools) {
    if (new_pools !== undefined && new_pools !== null) {
        phpminer.settings.pools = new_pools;
    }
    $('#current_pool_pool').html("");
    if (empty(phpminer.settings.pools)) {
        $('#current_pool_pool').append('<option value="">No pools in group</optiona>');
    }
    if (empty(phpminer.settings.pools)) {
        return;
    }
    $.each(phpminer.settings.pools, function(k, v) {
        $('#current_pool_pool').append('<option value="' + k + '"' + ((phpminer.settings.active_pool_uuid === k) ? ' selected="selected"' : '') + '>' + v['url'] + '</option>');
    });
}

function refresh_device_list() {
    ajax_request('/main/get_device_list.json', null, function(result) {
        set_device_list(result);
    });
}

function set_device_list(result) {
    $('#device_list tbody').html("");
    if (empty(result)) {
        $('#device_list tbody').html('<tr><td colspan="11" class="center">No devices found</td></tr>');
    }
    else {
        var donating = false;
        foreach(result, function(index, device) {
           
            var temp_ok = false;
            device['gpu_info']['Temperature'] = parseInt(device['gpu_info']['Temperature']);
            if (device['gpu_info']['Temperature'] >= get_config(['gpu_' + device['ID'], 'temperature', 'min'], 50) && device['gpu_info']['Temperature'] <= get_config(['gpu_' + device['ID'], 'temperature', 'max'], 85)) {
                temp_ok = true;
            }
            
            var hashrate_ok = false;
            if (device['gpu_info']['MHS 5s'] * 1000 >= get_config(['gpu_' + device['ID'], 'hashrate', 'min'], 100)) {
                hashrate_ok = true;
            }
            
            var load_ok = false;
            if (device['gpu_info']['GPU Activity'] >= get_config(['gpu_' + device['ID'], 'load', 'min'], 90)) {
                load_ok = true;
            }
           
            var tr = $('<tr></tr>')
                    .append($('<td class="center clickable ' + ((device['gpu_info']['Enabled'] === 'Y') ? 'enabled' : 'disabled') + '"><i class="icon-' + ((device['gpu_info']['Enabled'] === 'Y') ? 'check' : 'attention') + '"></i></td>').off('click').on('click', function() {
                        getEnableDialog(device['ID'], device['Model'], device['gpu_info']['Enabled']);
                    }))
                    .append($('<td>' + device['Model'] + '</td>'))
                    .append($('<td class="center clickable ' + ((load_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((load_ok === true) ? 'check' : 'attention') + '"></i>' + device['gpu_info']['GPU Activity'] + ' %</td>').off('click').on('click', function() {
                        getLoadConfigDialog(device['ID'], device['Model']);
                    }))
                    .append($('<td class="right clickable' + ((temp_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((temp_ok === true) ? 'check' : 'attention') + '"></i>'  + device['gpu_info']['Temperature'] + ' c</td>').off('click').on('click', function() {
                        getTempConfigDialog(device['ID'], device['Model']);
                    }))
                    .append($('<td class="right clickable' + ((hashrate_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((hashrate_ok === true) ? 'check' : 'attention') + '"></i>'  + (device['gpu_info']['MHS 5s'] * 1000) + ' kh/s (' + (device['gpu_info']['MHS av'] * 1000) + ' kh/s)</td>').off('click').on('click', function() {
                        getHashrateConfigDialog(device['ID'], device['Model']);
                    }))
                    .append($('<td class="left shares"><i class="icon-check"></i>' + device['gpu_info']['Accepted'] + ' <i class="icon-cancel"></i>' + device['gpu_info']['Rejected'] + ' (' + Math.round((100 / device['gpu_info']['Accepted']) * device['gpu_info']['Rejected'], 2) + '%)</td>'))
                    .append($('<td class="right clickable">' + device['gpu_info']['Fan Percent'] + ' % (' + device['gpu_info']['Fan Speed'] + ' rpm)</td>').off('click').on('click', function() {
                        getFanChangeDialog(device['ID'], device['Model'], device['gpu_info']['Fan Percent']);
                    }))
                    .append($('<td class="right clickable">' + device['gpu_info']['GPU Clock'] + ' Mhz</td>').off('click').on('click', function() {
                        getChangeDialog(device['ID'], device['Model'], device['gpu_info']['GPU Clock'], 'engine');
                    }))
                    .append($('<td class="right clickable">' + device['gpu_info']['Memory Clock'] + ' Mhz</td>').off('click').on('click', function() {
                        getChangeDialog(device['ID'], device['Model'], device['gpu_info']['Memory Clock'], 'memory');
                    }))
                    .append($('<td class="right clickable">' + device['gpu_info']['GPU Voltage'] + ' V</td>').off('click').on('click', function() {
                        getVoltageChangeDialog(device['ID'], device['Model'], device['gpu_info']['GPU Voltage']);
                    }))
                    .append($('<td class="right clickable">' + device['gpu_info']['Intensity'] + '</td>').off('click').on('click', function() {
                        getIntensityChangeDialog(device['ID'], device['Model'], device['gpu_info']['Intensity']);
                    }));
                    
            if (device['donating'] !== undefined) {
                donating = true;
                tr.append($('<td class="right ' + ((device['pool']['Status'] === 'Alive') ? '' : 'disabled') + '"><i class="icon-' + ((device['pool']['Status'] === 'Alive') ? 'check' : 'attention') + '"></i>Donating (' + device['donating'] + ' minutes left)</td>'));
            }
            else if (!empty(device['pool'])) {
                tr.append($('<td class="right ' + ((device['pool']['Status'] === 'Alive') ? '' : 'disabled') + '"><i class="icon-' + ((device['pool']['Status'] === 'Alive') ? 'check' : 'attention') + '"></i>' + device['pool']['URL'] + '</td>'));
            }
            else {
                tr.append('<td class="right">Waiting for pool</td>');
            }
            $('#device_list tbody').append(tr);
        });
        if (donating) {
            $('#pool_switching_container').hide();
        }
        else {
            $('#pool_switching_container').show();
        }
    }

}

function getEnableDialog(gpu_id, gpuname, current_value) {
    var message = 'Enable GPU';
    if (current_value === 'Y') {
        message = "Disable GPU";
    }
    confirm(message, 'Enable/Disable GPU ' + gpuname + ' (' + gpu_id + ')', function() {
        ajax_request("/gpu/enable_gpu.json", {gpu: gpu_id, value: (current_value === 'Y') ? 0 : 1});
    });
}

function getHashrateConfigDialog(gpu_id, gpuname) {
    
    if (phpminer.settings.config['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['gpu_' + gpu_id]['hashrate'] === undefined) {
        phpminer.settings.config['gpu_' + gpu_id]['hashrate'] = 100;
    }
    
    var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="min_hashrate">Min. hashrate:</label>';
    dialog += '            <div id="min_hashrate"></div> <span id="min_hashrate_new_value"></span> kh/s';
    dialog += '        </div>';
    dialog += '    </div>';

    var dlg = make_modal_dialog('Set min. hashrate for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {
            $('#min_hashrate').noUiSlider({
                range: [0, 1500],
                start: phpminer.settings.config['gpu_' + gpu_id]['hashrate'],
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#min_hashrate_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                ajax_request("/gpu/set_hashrate_config.json", {gpu: gpu_id, min: $(this).val()}, function() {
                    phpminer.settings.config['gpu_' + gpu_id]['hashrate']['min'] = $('#min_hashrate').val();
                });
            });
    

        }
    });
}

function getLoadConfigDialog(gpu_id, gpuname) {
    
    if (phpminer.settings.config['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['gpu_' + gpu_id]['load'] === undefined) {
        phpminer.settings.config['gpu_' + gpu_id]['load'] = 90;
    }
    
    var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="min_load">Min. load:</label>';
    dialog += '            <div id="min_load"></div> % <span id="min_load_new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    var dlg = make_modal_dialog('Set min. load for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {
            $('#min_load').noUiSlider({
                range: [0, 100],
                start: phpminer.settings.config['gpu_' + gpu_id]['load'],
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#min_load_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                ajax_request("/gpu/set_load_config.json", {gpu: gpu_id, min: $(this).val()}, function() {
                    phpminer.settings.config['gpu_' + gpu_id]['load']['min'] = $('#min_load').val();
                });
            });
    

        }
    });
}


function getTempConfigDialog(gpu_id, gpuname) {
    
    if (phpminer.settings.config['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['gpu_' + gpu_id]['temperature'] === undefined) {
        phpminer.settings.config['gpu_' + gpu_id]['temperature'] = {
            min: 50,
            max: 85
        };
    }
    
    var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="min_temp">Min. temperature:</label>';
    dialog += '            <div id="min_temp"></div> % <span id="min_temp_new_value"></span>';
    dialog += '        </div>';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="max_temp">Max. temperature:</label>';
    dialog += '            <div id="max_temp"></div> % <span id="max_temp_new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    var dlg = make_modal_dialog('Set temperature for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {
            $('#min_temp').noUiSlider({
                range: [0, 100],
                start: phpminer.settings.config['gpu_' + gpu_id]['temperature'].min,
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#min_temp_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                ajax_request("/gpu/set_temp_config.json", {gpu: gpu_id, min: $(this).val(), max: $('#max_temp').val()}, function() {
                    phpminer.settings.config['gpu_' + gpu_id]['temperature']['min'] = $('#min_temp').val();
                });
            });
            
            $('#max_temp').noUiSlider({
                range: [0, 100],
                start: phpminer.settings.config['gpu_' + gpu_id]['temperature'].max,
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#max_temp_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                ajax_request("/gpu/set_temp_config.json", {gpu: gpu_id, min: $('#min_temp').val(), max: $(this).val()}, function() {
                    phpminer.settings.config['gpu_' + gpu_id]['temperature']['max'] = $('#max_temp').val();
                });
            });

        }
    });
}

function change_btn(hide) {
    if (hide === undefined) {
        $('#save_cg_miner_config_container').fadeIn('slow');
    }
    else {
        $('#save_cg_miner_config_container').fadeOut('slow');
    }
}
function getFanChangeDialog(gpu_id, gpuname, current_fan_speed) {
    var dialog = "";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">Fanspeed:</label>';
    dialog += '            <div id="sample-update-slider"></div> % <span id="new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    var dlg = make_modal_dialog('Set fanspeed for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {

            $('#sample-update-slider').noUiSlider({
                range: [0, 100],
                start: current_fan_speed,
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                ajax_request("/gpu/set_fan_speed.json", {gpu: gpu_id, speed: $(this).val()}, function() {
                    change_btn();
                });
            });

        }
    });
}

function getVoltageChangeDialog(gpu_id, gpuname, value) {
    var dialog = "When using the text field to enter the value, please click outside when you are finish so the 'change' event can be fired";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">Voltage:</label>';
    dialog += '            <div id="sample-update-slider"></div> <input type="text" id="new_value" style="width:60px" /> V';
    dialog += '        </div>';
    dialog += '    </div>';

    var dlg = make_modal_dialog('Set voltage for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 860,
        show: function() {
            
            $('#sample-update-slider').noUiSlider({
                range: [0.8, 1.3],
                start: value,
                handles: 1,
                margin: 2,
                step: 0.001,
                serialization: {
                    to: [$('#new_value'), 'val'],
                    resolution: 0.001
                }
            }).change(function() {
                ajax_request("/gpu/set_voltage.json", {gpu: gpu_id, value: $(this).val()}, function() {
                    change_btn();
                });
            });
            
            $('#new_value').on('change', function() {
                $(this).val(parseFloat($(this).val()));
                $('#sample-update-slider').val($(this).val());
                $('#sample-update-slider').change();
            });
        }
    });
}

function getIntensityChangeDialog(gpu_id, gpuname, value) {
    var dialog = "";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">Intensity:</label>';
    dialog += '            <div id="sample-update-slider"></div> <span id="new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    var dlg = make_modal_dialog('Set intensity for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {

            $('#sample-update-slider').noUiSlider({
                range: [8, 20],
                start: value,
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                ajax_request("/gpu/set_intensity.json", {gpu: gpu_id, value: $(this).val()}, function(){
                    change_btn();
                });
            });

        }
    });
}

function getChangeDialog(gpu_id, gpuname, current_value, type) {

    var title = "";
    var label = "";
    var url = "";
    var unit = "";
    if (type === 'engine') {
        label = "Engine clock";
        title = "Set engine clock";
        url = "/gpu/set_engine_clock.json";
        unit = "Mhz";
    }
    else if (type === 'memory') {
        label = "Memory clock";
        title = "Set memory clock";
        url = "/gpu/set_memory_clock.json";
        unit = "Mhz";
    }

    var dialog = "";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">' + title + ':</label>';
    dialog += '            <input type="text" value="' + current_value + '" id="value"></input> <span class="unit">' + unit + '</span>';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog(label + ' <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, [
        {
            title: 'Save',
            type: 'primary',
            id: 'main_init_set_value',
            data: {
                "loading-text": 'Saving...'
            },
            click: function() {
                ajax_request(url, {gpu: gpu_id, value: $('#value').val()}, function() {
                    change_btn();
                    $('.modal').modal('hide');
                    $('#main_init_set_value').button('reset');
                }, function() {
                    $('#main_init_set_value').button('reset');
                });
            }
        }
    ], {
        width: 400
    });
}