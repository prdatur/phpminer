var refresh_device_list_timeout = null;
var global_hashrate = 0;
function get_config(name, default_val) {    
    if (phpminer.settings.config.rigs === undefined) {
        return default_val;
    }
    
    var cfg = $.extend({}, phpminer.settings.config.rigs);
    for(var i = 0; i < name.length; i++) {
        if (cfg[name[i]] === undefined) {
            return default_val;
        }
        cfg = cfg[name[i]];
    }
    return cfg;
} 

var current_device_list = {};
var rig_collapsed = {};
var pagerdata = {
    mepp: 0,
};
var rig_counter = 0;
Soopfw.behaviors.main_init = function() {
    
    if (refresh_device_list_timeout !== null) {
        clearInterval(refresh_device_list_timeout);
    }

    
    $.each(phpminer.settings.rig_data, function(rig, rig_data) {
        add_rig(rig, rig_data);
        update_pools(rig, rig_data.pools);
        
        set_device_list(rig_data.device_list, rig, rig_data.disabled);
    });
    $('.rig_edit').off('click').on('click', function() {
        ajax_request(murl('main', 'get_rig_data'), {rig: $(this).data('rig')}, function(rig_results) {
            add_rig_dialog(true, rig_results);
        });
    });
    $('.rig_delete').off('click').on('click', function() {
        var rig = $(this).data('rig');
        confirm('Do you really want to delete the rig: <b>' + rig + '</b>', 'Delete rig ' + rig + '?', function() {
            ajax_request(murl('main', 'delete_rig'), {rig: rig}, function() {
                $('.rig[data-rig="' + rig + '"]').fadeOut('fast', function() {
                    $(this).remove();
                });
            });
        });
    });
    
    $('.reset_stats').off('click').on('click', function() {
        reset_stats($(this).data('rig'));
    });

    $('.swap_rig').off('click').on('click', function() {
        var datarig = $(this).data('rig');
        var i = $('i', this);
        if (rig_collapsed[datarig]) {
            i.removeClass('icon-plus')
                .addClass('icon-minus');
        }
        else {
            i.removeClass('icon-minus')
                .addClass('icon-plus');
        }
        rig_collapsed[datarig] = !rig_collapsed[datarig];
        ajax_request(murl('main', 'set_rig_collapse'), {rig: datarig, collapsed: rig_collapsed[datarig]});
        set_device_list(current_device_list[datarig].list, datarig, current_device_list[datarig].disabled);
    });
    
    $('.stop_rig').off('click').on('click', function() {
        var datarig = $(this).data('rig');
        var disabled = get_config([datarig, 'disabled'], false);
        confirm('Do you really want to ' + ((disabled) ? 'start' : 'stop') + ' mining at rig "' + datarig + '"?', 'Stop mining / Disable rig', function() {
            ajax_request(murl('main', 'start_stop_mining'), {rig: datarig, stop: !disabled}, function() {
                phpminer.settings.config.rigs[datarig]['disabled'] = !disabled;
                set_device_list(current_device_list[datarig].list, datarig, current_device_list[datarig].disabled);
                refresh_update_device_list();
            });
        });
        
    });
    
    
    
    $('#global_hashrate').html(get_hashrate_string(global_hashrate));
    $('#add_rig').off('click').on('click', function() {
        add_rig_dialog(true);
    });
    
    $('#reset_all_rig_stats').off('click').on('click', function() {
        confirm('Do you really want to reset stats for all rigs?', 'Resets stats', function() {
            reset_stats();
        });
    });

    refresh_device_list_timeout = setInterval(refresh_update_device_list, get_config('ajax_refresh_intervall', 5000));
    init_pager();
};

function reset_stats(rig) {
    var data = {};
    if (rig !== undefined) {
        data['rig'] = rig;
    }
    ajax_request(murl('main', 'reset_stats'), data, function() {
        success_alert('Stats resetted');
    });
}

function add_rig(rig, rig_data) {
    if (phpminer.settings.rig_data[rig] === undefined) {
        phpminer.settings.rig_data[rig] = rig_data;
    }
    rig_collapsed[rig] = (rig_data.collapsed !== undefined) ? rig_data.collapsed : false;
    rig_counter++;
    var html  = '<div class="rig" data-rig="' + rig + '">';
        html += '   <h2 style="display: inline;"><span data-rig="' + rig + '" class="swap_rig"><i class="icon-' + ((rig_data.collapsed) ? 'plus' : 'minus') + '"></i></span>' + rig + '<span data-rig="' + rig + '" class="rig_hashrate"></span></h2>';
        html += '<div class="rig_btn rig_edit" data-rig="' + rig + '"><i class="icon-edit">Edit</i></div> ';
        html += '<div class="rig_btn rig_delete" data-rig="' + rig + '"><i class="icon-trash">Delete</i></div>';
        html += '<div class="rig_btn reset_stats" data-rig="' + rig + '"><i class="icon-ccw">Reset stats</i></div>';
        html += '<div class="rig_btn stop_rig" data-rig="' + rig + '"><i class="icon-off">Stop rig</i></div>';

        if (!empty(phpminer.settings.pool_groups)) {
            html += '<div class="pool_switching_container" data-rig="' + rig + '" style="float: right;' + ((rig_data.donating) ? 'display: none;' : '') + '">';
            html += '   <select class="current_pool_pool" data-rig="' + rig + '" id="' + rig_counter + '_current_pool_pool"style="float: right;"><option value="">Please wait, loading pools.</option></select>';
            html += '   <label for="' + rig_counter + '_current_pool_pool" style="float: right; margin-left: 10px;">Change mining pool:&nbsp;&nbsp;</label>';
            html += '   <select id="' + rig_counter + '_current_pool_group" class="current_pool_group" data-rig="' + rig + '" style="float: right;">';

            $.each(phpminer.settings.pool_groups, function(tmp, group) {
                if (group === 'donate') {
                    return;
                }
                html += '   <option value="' + group + '">' + group + '</option>';
            });

            html += '   </select>';

            html += '   <label for="' + rig_counter + '_current_pool_group" style="float: right;">Change mining group:&nbsp;&nbsp;</label>';
            html += '</div>';
        }

        html += '   <table class="device_list" data-rig="' + rig + '">';
        html += '       <thead>';
        html += '           <tr>';
        html += '               <th style="width:70px;" class="center">Enabled</th>';
        html += '               <th>Name</th>';
        html += '               <th style="width: 70px;" class="right"><i class="icon-signal"></i>Load</th>';
        html += '               <th style="width: 65px;" class="right"><i class="icon-thermometer"></i>Temp</th>';
        html += '               <th style="width: 140px;" class="right"><i class="icon-chart-line"></i>Hashrate 5s (avg)</th>';
        html += '               <th style="width: 145px; class="right"><i class="icon-link-ext"></i>Shares</th>';
        html += '               <th style="width: 65px; class="right"><i class="icon-attention"></i>HW</th>';
        html += '               <th style="width: 60px;" class="right"><i class="icon-air"></i>Fan</th>';
        html += '               <th style="width: 75px;" class="right"><i class="icon-clock"></i>Engine</th>';
        html += '               <th style="width: 83px;" class="right"><i class="icon-clock"></i>Memory</th>';
        html += '               <th style="width: 80px;" class="right"><i class="icon-flash"></i>Voltage</th>';
        html += '               <th style="width: 85px;" class="right"><i class="icon-fire"></i>Intensity</th>';
        html += '               <th style="width: 310px; class="right"><i class="icon-group"></i>Current pool</th>';
        html += '           </tr>';
        html += '       </thead>';
        html += '       <tbody>';
        html += '       </tbody>';
        html += '   </table>';
        html += '</div>';

    $('#rigs').append(html);

    $('.current_pool_group[data-rig="' + rig + '"]').val(rig_data.active_pool_group).off('change').on('change', function(){
        wait_dialog('<img style="margin-top: 7px;margin-bottom: 7px;" src="/templates/ajax-loader.gif"/><br>Please wait until the new pool group is activated. This takes some time because PHPMiner needs to verify that the last active pool is one of the newly added one.');
        ajax_request(murl('main', 'switch_pool_group'), {rig: rig, group: $(this).val()}, function(data) {
            $.alerts._hide();
            if(data.errors !== undefined) {
                var err_str = 'There are some rig\'s which produced errors. Here is a list of all errors which occured:\n';
                $.each(data.errors, function(k, v) {
                    err_str += " - " + v + "\n";
                });
                alert(err_str);
            }
            if(data.success !== undefined) {
                $.each(data.success, function(k, v) {
                    update_pools(k, data.new_pools);
                });
            }
        });
    });
    var rig_pools = $('.current_pool_pool[data-rig="' + rig + '"]');
    rig_pools.off('change').on('change', function(){
        ajax_request(murl('main', 'switch_pool'), {rig: rig, pool: $(this).val()}, function() {
            success_alert('Pool switched successfully, Within the overview, the pool will be updated after the first accepted share was send to the new pool, this can take some time.', null, null, 10000);
        });
        rig_pools.val("");
    });
}

function init_pager() {
    if (phpminer.settings.config.enable_paging === undefined || phpminer.settings.config.enable_paging === "0") {
        return;
    }
    
    pagerdata.mepp = $('#pager').off('change').on('change', function() {
        phpminer.settings.config.pager_mepp = $(this).val();
        ajax_request(murl('main', 'set_pager_mepp'), {mepp: phpminer.settings.config.pager_mepp});
        refresh_device_list(1, phpminer.settings.config.pager_mepp);
        init_pager();
    }).val();
    
    if (phpminer.settings.config.pager_mepp === undefined) {
        phpminer.settings.config.pager_mepp = 1;
    }
    var pager = new SoopfwPager({
        entries: phpminer.settings.rig_count,
        max_entries_per_page: phpminer.settings.config.pager_mepp,
        is_ajax: true,
        uuid: "init_device_pager",
        callback: function(current_page) {
            refresh_device_list((parseInt(current_page) + 1), pagerdata.mepp);
        },
    });
    pager.build_pager();
    
}
function update_pools(rig, new_pools) {
    if (new_pools !== undefined && new_pools !== null) {
        phpminer.settings.rig_data[rig].pools = new_pools;
    }
    var rig_pools = $('.current_pool_pool[data-rig="' + rig + '"]');
    rig_pools.html("");
    if (empty(phpminer.settings.rig_data[rig].pools)) {
        rig_pools.append('<option value="">No pools in group</optiona>');
        return;
    }
    
    rig_pools.append('<option value="">Select a pool</optiona>');
    $.each(phpminer.settings.rig_data[rig].pools, function(k, v) {
        rig_pools.append('<option value="' + v['url'] + '|' + v['user'] + '">' + v['url'] + '</option>');
    });
}

function refresh_update_device_list() {
    var rigs_to_update = [];
    $.each(current_device_list, function(rig, data) {
        rigs_to_update.push(rig);
    });
    ajax_request(murl('main', 'get_device_list'), {rigs: rigs_to_update}, function(result) {
        global_hashrate = 0;
        $.each(result, function(rig, devices) {
            
            if (devices['device_list'] === undefined) {
                devices['device_list'] = {};
            }
            
            set_device_list(devices.device_list, rig, devices.disabled);
        })
        $('#global_hashrate').html(get_hashrate_string(global_hashrate));
    });
}

function refresh_device_list(page, mepp) {
    if (refresh_device_list_timeout !== null) {
        clearInterval(refresh_device_list_timeout);
    }
    ajax_request(murl('main', 'get_device_list'), {page: page, mepp: mepp}, function(result) {
        $('#rigs').html("");
        
        current_device_list = {};
        global_hashrate = 0;
        $.each(result, function(rig, devices) {
            add_rig(rig, devices);
            update_pools(rig, devices.pools);
            if (devices['device_list'] === undefined) {
                devices['device_list'] = {};
            }
            
            set_device_list(devices.device_list, rig, devices.disabled);
        })
        $('#global_hashrate').html(get_hashrate_string(global_hashrate));
        refresh_device_list_timeout = setInterval(refresh_update_device_list, get_config('ajax_refresh_intervall', 5000));
    });
}

function get_hashrate_string(value) { 
    if (value > 1000) {
        return (Math.round(value/10)/100) + " Gh/s";
    }
    
    if (value < 1) {
        return (Math.round(value * 100000)/100) + " Kh/s";
    }
    
    return (Math.round(value * 100)/100) + " Mh/s";

}
function set_device_list(result, rig, disabled) {
    current_device_list[rig] =   {list: result, disabled: disabled};
    $('.device_list[data-rig="' + rig + '"] tbody').html("");
    $('.rig_btn.stop_rig[data-rig="' + rig + '"] i').html('Stop rig');
    if (disabled !== undefined && disabled === true) {
        $('.pool_switching_container[data-rig="' + rig + '"]').hide();
        $('.device_list[data-rig="' + rig + '"] tbody').html('<tr><td colspan="13" class="center">Rig is disabled</td></tr>');
        $('.rig_btn.stop_rig[data-rig="' + rig + '"] i').html('Start rig');
    }
    else if (empty(Soopfw.obj_size(result))) {
        $('.pool_switching_container[data-rig="' + rig + '"]').hide();
        $('.device_list[data-rig="' + rig + '"] tbody').html('<tr><td colspan="13" class="center">No devices found or rig is not alive</td></tr>');
    }
    else {
        var donating = false;        
        var rig_hashrate = 0.0;
        var device_count = 0;
        var avg_data = {
            load: 0,
            temp: 0,
            mhs: {
                cur: 0,
                avg: 0
            },
            shares: {
                accepted: 0,
                rejected: 0
            },
            hw: 0,
            fan: 0,
            engine: 0,
            memory: 0,
            voltage: 0,
            intensity: 0

        };
        var active_pools = {};
        foreach(result, function(index, device) {
            
            var temp_ok = false;
            device['gpu_info']['Temperature'] = parseInt(device['gpu_info']['Temperature']);
            if (device['gpu_info']['Temperature'] >= get_config([rig, 'gpu_' + device['ID'], 'temperature', 'min'], 50) && device['gpu_info']['Temperature'] <= get_config([rig, 'gpu_' + device['ID'], 'temperature', 'max'], 85)) {
                temp_ok = true;
            }
            
            var hashrate_ok = false;
            if (device['gpu_info']['MHS 5s'] * 1000 >= get_config([rig, 'gpu_' + device['ID'], 'hashrate', 'min'], 100)) {
                hashrate_ok = true;
            }
            
            var load_ok = false;
            if (device['gpu_info']['GPU Activity'] >= get_config([rig, 'gpu_' + device['ID'], 'load', 'min'], 90)) {
                load_ok = true;
            }
            
            var hw_ok = false;
            if (device['gpu_info']['Hardware Errors'] <= get_config([rig, 'gpu_' + device['ID'], 'hw', 'max'], 5)) {
                hw_ok = true;
            }
            rig_hashrate += device['gpu_info']['MHS 5s'];
            if (device['donating'] !== undefined) {
                donating = true;
            }
            
            avg_data = {
                load: avg_data.load + parseFloat(device['gpu_info']['GPU Activity']),
                temp: avg_data.temp + parseFloat(device['gpu_info']['Temperature']),
                mhs: {
                    cur: avg_data.mhs.cur + parseFloat(device['gpu_info']['MHS 5s']),
                    avg: avg_data.mhs.avg + parseFloat(device['gpu_info']['MHS av'])
                },
                shares: {
                    accepted: avg_data.shares.accepted + parseFloat(device['gpu_info']['Accepted']),
                    rejected: avg_data.shares.rejected + parseFloat(device['gpu_info']['Rejected'])
                },
                hw: avg_data.hw + parseFloat(device['gpu_info']['Hardware Errors']),
                fan: avg_data.fan + parseFloat(device['gpu_info']['Fan Percent']),
                engine: avg_data.engine + parseFloat(device['gpu_info']['GPU Clock']),
                memory: avg_data.memory + parseFloat(device['gpu_info']['Memory Clock']),
                voltage: avg_data.voltage + parseFloat(device['gpu_info']['GPU Voltage']),
                intensity: avg_data.intensity + parseFloat(device['gpu_info']['Intensity'])
            };

            device_count++;

            if (device['donating'] !== undefined) {
                if (active_pools['donating'] === undefined) {
                    active_pools['donating'] = [];
                }
                active_pools['donating'].push(device['Model']);
            }
            else if (!empty(device['pool'])) {
                if (active_pools[device['pool']['URL']] === undefined) {
                    active_pools[device['pool']['URL']] = [];
                }
                active_pools[device['pool']['URL']].push(device['Model']);
            }
            else {
                if (active_pools['waiting'] === undefined) {
                    active_pools['waiting'] = [];
                }
                active_pools['waiting'].push(device['Model']);
            }
            
            if (rig_collapsed[rig] && temp_ok && hashrate_ok && load_ok && hw_ok) {
                return;
            }
            
            var tr = $('<tr></tr>')
                    .append($('<td class="nowrap center clickable ' + ((device['gpu_info']['Enabled'] === 'Y') ? 'enabled' : 'disabled') + '"><i class="icon-' + ((device['gpu_info']['Enabled'] === 'Y') ? 'check' : 'attention') + '"></i></td>').off('click').on('click', function() {
                        getEnableDialog(rig, device['ID'], device['Model'], device['gpu_info']['Enabled']);
                    }))
                    .append($('<td class="nowrap">' + device['Model'].replace("Series", "").replace("AMD", "").replace("Radeon", "").trim() + '</td>'))
                    .append($('<td class="nowrap center clickable ' + ((load_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((load_ok === true) ? 'check' : 'attention') + '"></i>' + device['gpu_info']['GPU Activity'] + ' %</td>').off('click').on('click', function() {
                        getLoadConfigDialog(rig, device['ID'], device['Model']);
                    }))
                    .append($('<td class="nowrap right clickable' + ((temp_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((temp_ok === true) ? 'check' : 'attention') + '"></i>'  + device['gpu_info']['Temperature'] + ' c</td>').off('click').on('click', function() {
                        getTempConfigDialog(rig, device['ID'], device['Model']);
                    }))
                    .append($('<td class="nowrap right clickable' + ((hashrate_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((hashrate_ok === true) ? 'check' : 'attention') + '"></i>'  + get_hashrate_string(device['gpu_info']['MHS 5s']) + ' (' + get_hashrate_string(device['gpu_info']['MHS av']) + ')</td>').off('click').on('click', function() {
                        getHashrateConfigDialog(rig, device['ID'], device['Model']);
                    }))
                    .append($('<td class="nowrap right shares"><i class="icon-check"></i>' + device['gpu_info']['Accepted'] + ' <i class="icon-cancel"></i>' + device['gpu_info']['Rejected'] + ' (' + Math.round((100 / device['gpu_info']['Accepted']) * device['gpu_info']['Rejected'], 2) + '%)</td>'))
                    .append($('<td class="nowrap right clickable' + ((hw_ok !== true) ? ' disabled' : '') + '"><i class="icon-' + ((hw_ok === true) ? 'check' : 'attention') + '"></i>'  + device['gpu_info']['Hardware Errors'] + '</td>').off('click').on('click', function() {
                        getHWConfigDialog(rig, device['ID'], device['Model']);
                    }))
                    .append($('<td class="nowrap right clickable">' + device['gpu_info']['Fan Percent'] + ' %</td>').off('click').on('click', function() {
                        getFanChangeDialog(rig, device['ID'], device['Model'], device['gpu_info']['Fan Percent']);
                    }))
                    .append($('<td class="nowrap right clickable">' + device['gpu_info']['GPU Clock'] + ' Mhz</td>').off('click').on('click', function() {
                        getChangeDialog(rig, device['ID'], device['Model'], device['gpu_info']['GPU Clock'], 'engine');
                    }))
                    .append($('<td class="nowrap right clickable">' + device['gpu_info']['Memory Clock'] + ' Mhz</td>').off('click').on('click', function() {
                        getChangeDialog(rig, device['ID'], device['Model'], device['gpu_info']['Memory Clock'], 'memory');
                    }))
                    .append($('<td class="nowrap right clickable">' + device['gpu_info']['GPU Voltage'] + ' V</td>').off('click').on('click', function() {
                        getVoltageChangeDialog(rig, device['ID'], device['Model'], device['gpu_info']['GPU Voltage']);
                    }))
                    .append($('<td class="nowrap right clickable">' + device['gpu_info']['Intensity'] + '</td>').off('click').on('click', function() {
                        getIntensityChangeDialog(rig, device['ID'], device['Model'], device['gpu_info']['Intensity']);
                    }));
                    
            
            if (device['donating'] !== undefined) {
                tr.append($('<td class="nowrap right ' + ((device['pool']['Status'] === 'Alive') ? '' : 'disabled') + '"><i class="icon-' + ((device['pool']['Status'] === 'Alive') ? 'check' : 'attention') + '"></i>Donating (' + device['donating'] + ' minutes left)</td>'));
            }
            else if (!empty(device['pool'])) {
                tr.append($('<td class="nowrap right ' + ((device['pool']['Status'] === 'Alive') ? '' : 'disabled') + '"><i class="icon-' + ((device['pool']['Status'] === 'Alive') ? 'check' : 'attention') + '"></i>' + device['pool']['URL'] + '</td>'));
            }
            else {
                tr.append('<td class="nowrap right">Waiting for pool</td>');
            }
            $('.device_list[data-rig="' + rig + '"] tbody').append(tr);
            
        });
        if (rig_collapsed[rig]) {
            function maxRound(val, decimals) {
                if (decimals === undefined) {
                    decimals = 2;
                }
                return Math.round((val / device_count)*Math.pow(10, decimals))/Math.pow(10, decimals);
            }
            if (device_count > 0) {
                avg_data = {
                    load: maxRound(avg_data.load),
                    temp: maxRound(avg_data.temp),
                    mhs: {
                        cur: maxRound(avg_data.mhs.cur),
                        avg: maxRound(avg_data.mhs.avg)
                    },
                    shares: {
                        accepted: avg_data.shares.accepted,
                        rejected: avg_data.shares.rejected
                    },
                    hw: avg_data.hw,
                    fan: maxRound(avg_data.fan),
                    engine: maxRound(avg_data.engine),
                    memory: maxRound(avg_data.memory),
                    voltage: maxRound(avg_data.voltage, 3),
                    intensity: maxRound(avg_data.intensity)
                };
            }

            // Add average tr
            var tr = $('<tr class="average_tr" data-rig="' + rig + '"></tr>')
                    .append($('<td class="nowrap center clickable enabled"><i class="icon-check"></i></td>'))
                    .append($('<td class="nowrap">' + device_count + ' GPUs</td>'))
                    .append($('<td class="nowrap center" title="Average value of all cards"><i class="icon-check"></i>' + avg_data.load + ' %</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards"><i class="icon-check"></i>'  + avg_data.temp + ' c</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards"><i class="icon-check"></i>'  + get_hashrate_string(avg_data.mhs.cur) + ' (' + get_hashrate_string(avg_data.mhs.avg) + ')</td>'))
                    .append($('<td class="nowrap right shares" title="Summary value of all cards"><i class="icon-check"></i>' + avg_data.shares.accepted + ' <i class="icon-cancel"></i>' + avg_data.shares.rejected + ' (' + Math.round((100 / avg_data.shares.accepted) * avg_data.shares.rejected, 2) + '%)</td>'))
                    .append($('<td class="nowrap right" title="Summary value of all cards"><i class="icon-check"></i>'  + avg_data.hw + '</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards">' + avg_data.fan + ' %</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards">' + avg_data.engine + ' Mhz</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards">' + avg_data.memory + ' Mhz</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards">' + avg_data.voltage + ' V</td>'))
                    .append($('<td class="nowrap right" title="Average value of all cards">' + avg_data.intensity + '</td>'));

            var active_pool_count = 0;
            var active_key = '<i class="icon-check"></i> ';
            for (var i in active_pools) {
                if (active_pools.hasOwnProperty(i)) {
                    active_pool_count++;
                    if (active_pool_count > 1) {
                        break;
                    }
                    active_key += i;
                }
            }

            if (active_pool_count > 1) {
                var tmp_join_array = [];
                $.each(active_pools, function(url, devices) {
                    tmp_join_array.push(devices.length + " Devices: " + url);
                });
                active_key = tmp_join_array.join('<br />');
            }

            tr.append($('<td class="nowrap right">' + active_key + '</td>'));       
            $('.device_list[data-rig="' + rig + '"] tbody').append(tr);      
        }
        if (donating) {
            $('.pool_switching_container[data-rig="' + rig + '"]').hide();
        }
        else {
            $('.pool_switching_container[data-rig="' + rig + '"]').show();
        }
        $('.rig_hashrate[data-rig="' + rig + '"]').html(get_hashrate_string(rig_hashrate));
        global_hashrate += rig_hashrate;
    }
}

function getEnableDialog(rig, gpu_id, gpuname, current_value) {
    var message = 'Enable GPU';
    if (current_value === 'Y') {
        message = "Disable GPU";
    }
    confirm(message, 'Enable/Disable GPU ' + gpuname + ' (' + gpu_id + ')', function() {
        ajax_request(murl('gpu', 'enable_gpu'), {rig: rig, gpu: gpu_id, value: (current_value === 'Y') ? 0 : 1});
    });
}

function getHashrateConfigDialog(rig, gpu_id, gpuname) {
    
    if (phpminer.settings.config['rigs'][rig] === undefined) {
        phpminer.settings.config['rigs'][rig] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hashrate'] === undefined || phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hashrate']['min'] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hashrate'] = {min: 100};
    }
    
    var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="min_hashrate">Min. hashrate:</label>';
    dialog += '            <div id="min_hashrate"></div> <span id="min_hashrate_new_value"></span> kh/s';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Set min. hashrate for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {
            $('#min_hashrate').noUiSlider({
                range: [0, 1500],
                start: phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hashrate']['min'],
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#min_hashrate_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                wait_dialog();
                ajax_request(murl('gpu', 'set_hashrate_config'), {rig: rig, gpu: gpu_id, min: $(this).val()}, function() {
                    $.alerts._hide();
                    phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hashrate']['min'] = $('#min_hashrate').val();
                });
            });
    

        }
    });
}

function getLoadConfigDialog(rig, gpu_id, gpuname) {
    
    if (phpminer.settings.config['rigs'][rig] === undefined) {
        phpminer.settings.config['rigs'][rig] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['load'] === undefined || phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['load']['min'] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['load'] = {min:90};
    }
    
    var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="min_load">Min. load:</label>';
    dialog += '            <div id="min_load"></div> % <span id="min_load_new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Set min. load for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {
            $('#min_load').noUiSlider({
                range: [0, 100],
                start: phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['load']['min'],
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#min_load_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                wait_dialog();
                ajax_request(murl('gpu', 'set_load_config'), {rig: rig, gpu: gpu_id, min: $(this).val()}, function() {
                    $.alerts._hide();
                    phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['load']['min'] = $('#min_load').val();
                });
            });
    

        }
    });
}

function getHWConfigDialog(rig, gpu_id, gpuname) {
    
    if (phpminer.settings.config['rigs'][rig] === undefined) {
        phpminer.settings.config['rigs'][rig] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hw'] === undefined || phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hw']['max'] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hw'] = {max: 5};
    }
    
    var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="max_hw">Max. Hardware errors:</label>';
    dialog += '            <input type="text" value="' + phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hw']['max'] + '" id="max_hw"></input>';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Set max. hardware errors for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, [
        {
            title: 'Save',
            type: 'primary',
            id: 'main_init_set_hw_eeror',
            data: {
                "loading-text": 'Saving...'
            },
            click: function() {
            wait_dialog();
            ajax_request(murl('gpu', 'set_hw_config'), {rig: rig, gpu: gpu_id, max: $('#max_hw').val()}, function() {
                $.alerts._hide();
                phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['hw']['max'] = $('#max_hw').val();
                $('.modal').modal('hide');
                $('#main_init_set_value').button('reset');
            }, function() {
                $('#main_init_set_value').button('reset');
            });
        }
        }
    ], {
        width: 660        
    });
}


function getTempConfigDialog(rig, gpu_id, gpuname) {
    
    if (phpminer.settings.config['rigs'][rig] === undefined) {
        phpminer.settings.config['rigs'][rig] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id] = {};
    }
    if (phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['temperature'] === undefined) {
        phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['temperature'] = {
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

    make_modal_dialog('Set temperature for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
        width: 660,
        show: function() {
            $('#min_temp').noUiSlider({
                range: [0, 100],
                start: phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['temperature'].min,
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#min_temp_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                wait_dialog();
                ajax_request(murl('gpu', 'set_temp_config'), {rig: rig, gpu: gpu_id, min: $(this).val(), max: $('#max_temp').val()}, function() {
                    $.alerts._hide();
                    phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['temperature']['min'] = $('#min_temp').val();
                });
            });
            
            $('#max_temp').noUiSlider({
                range: [0, 100],
                start: phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['temperature'].max,
                handles: 1,
                margin: 2,
                step: 1,
                decimals: 1,
                serialization: {
                    to: [$('#max_temp_new_value'), 'text'],
                    resolution: 1
                }
            }).change(function() {
                wait_dialog();
                ajax_request(murl('gpu', 'set_temp_config'), {rig: rig, gpu: gpu_id, min: $('#min_temp').val(), max: $(this).val()}, function() {
                    $.alerts._hide();
                    phpminer.settings.config['rigs'][rig]['gpu_' + gpu_id]['temperature']['max'] = $('#max_temp').val();
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
function getFanChangeDialog(rig, gpu_id, gpuname, current_fan_speed) {
    var dialog = "";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">Fanspeed:</label>';
    dialog += '            <div id="sample-update-slider"></div> % <span id="new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Set fanspeed for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
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
                wait_dialog();
                ajax_request(murl('gpu', 'set_fan_speed'), {rig: rig, gpu: gpu_id, speed: $(this).val()}, function() {
                    $.alerts._hide();
                    change_btn();
                });
            });

        }
    });
}

function getVoltageChangeDialog(rig, gpu_id, gpuname, value) {
    var dialog = "When using the text field to enter the value, please click outside when you are finish so the 'change' event can be fired";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">Voltage:</label>';
    dialog += '            <div id="sample-update-slider"></div> <input type="text" id="new_value" style="width:60px" /> V';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Set voltage for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
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
                wait_dialog();
                ajax_request(murl('gpu', 'set_voltage'), {rig: rig, gpu: gpu_id, value: $(this).val()}, function() {
                    $.alerts._hide();
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

function getIntensityChangeDialog(rig, gpu_id, gpuname, value) {
    var dialog = "";
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="value">Intensity:</label>';
    dialog += '            <div id="sample-update-slider"></div> <span id="new_value"></span>';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Set intensity for <b>' + gpuname + '</b> (GPU: <b>' + gpu_id + '</b>)', dialog, null, {
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
                wait_dialog();
                ajax_request(murl('gpu', 'set_intensity'), {rig: rig,gpu: gpu_id, value: $(this).val()}, function(){
                    $.alerts._hide();
                    change_btn();
                });
            });

        }
    });
}

function getChangeDialog(rig, gpu_id, gpuname, current_value, type) {

    var title = "";
    var label = "";
    var url = "";
    var unit = "";
    if (type === 'engine') {
        label = "Engine clock";
        title = "Set engine clock";
        url = murl('gpu', 'set_engine_clock');
        unit = "Mhz";
    }
    else if (type === 'memory') {
        label = "Memory clock";
        title = "Set memory clock";
        url = murl('gpu', 'set_memory_clock');
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
                wait_dialog();
                ajax_request(url, {rig: rig,gpu: gpu_id, value: $('#value').val()}, function() {
                    $.alerts._hide();
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