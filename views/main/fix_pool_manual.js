Soopfw.behaviors.main_fix_pool_manual = function() {
    var cfg_pools = {};
    var current_group = null;
    function update_cfg_pools() {
        var cgminer_pools = {};
        $('#cgminer_pools tr').each(function() {
            cgminer_pools[$(this).data('uuid')] = true;
        });
        var all_valid = true;
        $.each(cgminer_pools, function(uuid, v) {
            if (cfg_pools[uuid] !== undefined) {
                $('#cgminer_pools tr[data-uuid="' + uuid + '"]').addClass('valid');
                $('#group_pools tr[data-uuid="' + uuid + '"]').addClass('valid');
            }
            else {
                $('#cgminer_pools tr[data-uuid="' + uuid + '"]').addClass('invalid');
                $('#group_pools tr[data-uuid="' + uuid + '"]').addClass('invalid');
                all_valid = false;
            }
        });
        
        $.each(cfg_pools, function(uuid, v) {
            if (cgminer_pools[uuid] !== undefined) {
                $('#cgminer_pools tr[data-uuid="' + uuid + '"]').addClass('valid');
                $('#group_pools tr[data-uuid="' + uuid + '"]').addClass('valid');
            }
            else {
                $('#cgminer_pools tr[data-uuid="' + uuid + '"]').addClass('invalid');
                $('#group_pools tr[data-uuid="' + uuid + '"]').addClass('invalid');
                all_valid = false;
            }
        });
        if (all_valid === true) {
            success_alert('You have successfully merged the pools, you will be now redirected to the main page', function() {
                Soopfw.location(phpminer.settings.docroot + '/');
            });            
            return;
        }
        $('.add_to_cgminer').off('click').on('click', function() {
            var parent = $(this).parent().parent();
            var pass = parent.data('pass');
            var uuid = parent.data('uuid');
            var data = uuid.split('|');
            var url = data[0];
            var user = data[1];
            confirm("Are you sure you want to add the following pool to CGMiner/SGMiner?\nPool: <b>" + url + "</b>\nUser: <b>" + user + "</b>", 'Add pool to CGMiner/SGMiner', function() {
                wait_dialog();
                ajax_request(murl('main', 'fix_pool_manual_action'), {type: 3, url: url, user: user, pass: pass}, function() {
                    $.alerts._hide();
                    var html = '<tr data-uuid="' + url + '|' + user + '" data-pass="' + pass + '">'
                        + '    <td>' + url + '</td>'
                        + '    <td>' + user + '</td>'
                        + '    <td class="options"><a href="javascript:void(0);" class="btn btn-success add_to_cgminer">Add to CGMiner/SGMiner</a> - <a href="javascript:void(0);" class="btn btn-danger remove_from_group">Remove from pool</a></td>'
                        + '</tr>';
                    $('#cgminer_pools').append($(html).hide().fadeIn());
                    update_cfg_pools();
                });
            });
        });

        $('.remove_from_group').off('click').on('click', function() {
            var that = this;
            var parent = $(this).parent().parent();
            var uuid = parent.data('uuid');
            var data = uuid.split('|');
            var url = data[0];
            var user = data[1];
            confirm("Are you sure you want to remove the following pool from group <b>" + current_group + "</b>?\nPool: <b>" + url + "</b>\nUser: <b>" + user + "</b>", 'Remove pool from group ' + current_group, function() {
                wait_dialog();
                ajax_request(murl('main', 'fix_pool_manual_action'), {type: 2, url: url, user: user, group: current_group}, function() {
                    $.alerts._hide();
                    $(parent).fadeOut('slow', function() {
                        $(this).remove();
                        delete cfg_pools[uuid];
                        update_cfg_pools();
                    });
                });
            });
        });
        
        $('.add_to_group').off('click').on('click', function() {
            if ($('#cfg_groups').val() === '') {
                alert('Please choose a group first');
                return;
            }

            var uuid = $(this).parent().parent().data('uuid');
            var data = uuid.split('|');
            var url = data[0];
            var user = data[1];


            var dialog = ""
                + "<div style='margin-top: 5px;bottom:15px;'>"
                + "PHPMiner can only read out the current configured pools without the password.<br />"
                + "So please provide your worker passwords here."
                + "</div>";
            dialog += '    <div class="simpleform">';
                dialog += '        <div class="form-element">';
                dialog += '            <b>' + url + ': </b><br />';
                dialog += '            Username: ' + user + '<br />';
                dialog += '            <label for="pass">Password:</label>';
                dialog += '            <input type="text" id="pass" data-url="' + url + '" data-user="' + user + '" class="pool_pws">';
                dialog += '        </div>';

            make_modal_dialog('Add pools', dialog, [
            {
                    title: 'Save',
                    type: 'primary',
                    id: 'main_fix_pools_set_value',
                    data: {
                        "loading-text": 'Saving...'
                    },
                    click: function() {
                        var pass = $('#pass').val();
                        wait_dialog();
                        ajax_request(murl('main', 'fix_pool_manual_action'), {type: 4, url: url, user: user, pass: pass, group: current_group}, function() {
                            $.alerts._hide();
                            cfg_pools[url + '|' + user] = true;
                            var html = '<tr data-uuid="' + url + '|' + user + '" data-pass="' + pass + '">'
                                  + '    <td>' + url + '</td>'
                                  + '    <td>' + user + '</td>'
                                  + '    <td class="options"><a href="javascript:void(0);" class="btn btn-success add_to_cgminer">Add to CGMiner/SGMiner</a> - <a href="javascript:void(0);" class="btn btn-danger remove_from_group">Remove from pool</a></td>'
                                  + '</tr>';
                            $('#group_pools').append($(html).hide().fadeIn());
                            update_cfg_pools();
                        });
                    }
                }
            ], {
                width: 400
            });
        });

        $('.remove_from_cgminer').off('click').on('click', function() {
            if ($('#cfg_groups').val() === '') {
                alert('Please choose a group first');
                return;
            }
            var parent = $(this).parent().parent();
            var data = parent.data('uuid').split('|');
            var url = data[0];
            var user = data[1];
            confirm("Are you sure you want to remove the following pool from CGMiner/SGMiner?\nPool: <b>" + url + "</b>\nUser: <b>" + user + "</b>", 'Remove pool from CGMiner/SGMiner', function() {
                wait_dialog();
                ajax_request(murl('main', 'fix_pool_manual_action'), {type: 1, url: url, user: user}, function() {
                    $.alerts._hide();
                    $(parent).fadeOut('slow', function() {
                        $(this).remove();
                        update_cfg_pools();
                    });
                });
            });
        });
    }
    
    $('#cfg_groups').off('change').on('change', function() {
        current_group = $(this).val();
        $('#cgminer_pools tr').removeClass('valid').removeClass('invalid');
        $('#group_pools tr').removeClass('valid').removeClass('invalid');
        cfg_pools = {};        
        var html = '';
        if (empty(phpminer.settings.cfg_groups[$(this).val()])) {
            html = '<tr><td colspan="3">You didn\'t selected a group yet, or this group does not have any pools configurated.</td></tr>';
        }
        else {
            html = '';
            $.each(phpminer.settings.cfg_groups[$(this).val()], function(k, pool) {
                cfg_pools[pool.url + '|' + pool.user] = true;
                html += '<tr data-uuid="' + pool.url + '|' + pool.user + '" data-pass="' + pool.pass + '">'
                      + '    <td>' + pool.url + '</td>'
                      + '    <td>' + pool.user + '</td>'
                      + '    <td class="options"><a href="javascript:void(0);" class="btn btn-success add_to_cgminer">Add to CGMiner/SGMiner</a> - <a href="javascript:void(0);" class="btn btn-danger remove_from_group">Remove from pool</a></td>'
                      + '</tr>';
            });
        }
        $('#group_pools').html("").append(html);

        update_cfg_pools();
    });
    
    $('#search_best_match').off('click').on('click', function(){
        var cgminer_pools = {};
        $('#cgminer_pools tr').each(function() {
            cgminer_pools[$(this).data('uuid')] = true;
        });
        
        var best_match = {};
        $.each(phpminer.settings.cfg_groups, function(group, cfg_pool_arr) {
            var cfg_pool_arr_check = $.extend({}, cfg_pool_arr);
            $.each(cfg_pool_arr_check, function(k, v) {
                cfg_pool_arr_check[v.url + '|' + v.user] = v;
                delete cfg_pool_arr_check[k];
            });
            
            best_match[group] = {
                valid: 0,
                invalid: 0
            };
            $.each(cgminer_pools, function(uuid, v) {

                if (cfg_pool_arr_check[uuid] !== undefined) {
                    //console.log('ok');
                   best_match[group]['valid']++;
                   delete cfg_pool_arr_check[uuid];
                }
                else {
                    best_match[group]['invalid']++;
                }
            });
            best_match[group]['invalid'] += count(cfg_pool_arr_check);
        });
                
        var best_group = {
            group: null,
            valid: null,
            invalid: null            
        };
        $.each(best_match, function(group, stat) {
            if ((stat.valid > best_group.valid) || (stat.valid === best_group.valid && stat.invalid <= best_group.invalid)) {
                if (stat.valid === best_group.valid && stat.invalid === best_group.invalid && best_group.group !== null) {
                    return;
                }
                best_group = {
                    group: group,
                    valid: stat.valid,
                    invalid: stat.invalid            
                };
            }
        });
        $('#cfg_groups').val(best_group.group).change();
    });
};