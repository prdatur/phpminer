Soopfw.behaviors.pools_main = function() {
    
    function reload_editable() {
        
       $('*[data-pk]:not(*[data-name="rig_based"])').editable('destroy').editable({
            ajaxOptions: {
                dataType: 'json'
            },
            success: function(response, new_value) {
                parse_ajax_result(response, function(data) {
                    var new_pk = data.new + '|' + data.group;
                    var tr = $('tr[data-uuid="' + data.old + '|' + data.group + '"]');
                    tr.attr('data-uuid', new_pk).data('uuid', new_pk);
                    $('*[data-pk]', tr).attr('data-pk', new_pk).data("pk", new_pk);
                    $('*[data-uuid]', tr).attr('data-uuid', new_pk).data("uuid", new_pk);
                    $('a[data-action="delete-pool"]', tr).attr('name', data.url).data("name", data.url);
                    setTimeout(function() {
                        reload_editable();
                    }, 100);
                });
            }
        });
        $('*[data-name="rig_based"]').editable('destroy').editable({
            ajaxOptions: {
                dataType: 'json'
            },
            source: [
                    {value: 'Enabled', text: 'Enabled'},
            ],
            emptytext: 'Disabled',
            success: function(response, new_value) {
                parse_ajax_result(response, function(data) {
                    var new_pk = data.new + '|' + data.group;
                    var tr = $('tr[data-uuid="' + data.old + '|' + data.group + '"]');
                    tr.attr('data-uuid', new_pk).data('uuid', new_pk);
                    $('*[data-pk]', tr).attr('data-pk', new_pk).data("pk", new_pk);
                    $('*[data-uuid]', tr).attr('data-uuid', new_pk).data("uuid", new_pk);
                    $('a[data-action="delete-pool"]', tr).attr('name', data.url).data("name", data.url);
                    setTimeout(function() {
                        reload_editable();
                    }, 100);
                });
            }
        });
    }
    reload_editable();
    
    $('#save_unconfigurated_pools').off('click').on('click', function() {
        var btn = this;
        $(this).button('loading');
        var i = 0;
        $('input[data-pool_url]').removeClass('error').each(function() {
            i++;
            if (empty($(this).val())) {
                i--;
                if (i <= 0) {
                    $(btn).button('reset');
                }
                return;
            }
            $('.url', $(this).parent().parent()).append('<img style="float: right;margin-top: 7px;margin-right: 7px;" src="/templates/ajax-loader.gif"/>');
            var group = $('.pool_group', $(this).parent().parent()).val();
            if (empty(group)) {
                group = 'default';
            }
            ajax_request(murl('pools', 'add_pool'), {url: $(this).data('pool_url'), user: $(this).data('pool_user'), pass: $(this).val(), group: group}, function(result) {
                i--;
                var elm = $('input[data-pool_url="' + result.url + '"][data-pool_user="' + result.user + '"]');
                var parent = elm.parent().parent();
                $('.url img', parent).remove();

                parent.fadeOut('slow', function() {
                    $(this).remove();
                    if (i <= 0) {
                        $(btn).button('reset');
                        if ($('input[data-pool_url]').size() === 1) {
                            Soopfw.reload();
                        }
                    }
                });
            }, function(result) {
                i--;
                if (i <= 0) {
                    $(btn).button('reset');
                }
                var elm = $('input[data-pool_url="' + result.data['url'] + '"][data-pool_user="' + result.data['user'] + '"]');
                $('.url img', elm.parent().parent()).remove();
                elm.addClass('error');
            });
        });

    });

    $('a[data-action="delete-pool"]').off('click').on('click', function() {
        var that = this;
        confirm('Do you really want to delete pool <b>' + $(this).data('name') + '</b> from group <b>' + $(this).data('group') + '</b>', 'Delete pool from group', function() {
            wait_dialog('Please wait');
            ajax_request(murl('pools', 'delete_pool'), {uuid: $(that).data('uuid')}, function() {
                $.alerts._hide();
                $('tr[data-uuid="' + $(that).data('uuid') + '"]').fadeOut('slow', function() {
                    $(this).remove();
                    success_alert('Pool successfully deleted');
                });
            });
        });
    });

    $('a[data-del-pool-group]').off('click').on('click', function() {
        var group = $(this).data('del-pool-group');
        confirm('Do you really want to delete this group? All configurated pools within this group will also be deleted.', 'Delete pool group', function() {
            wait_dialog('Please wait');
            ajax_request(murl('pools', 'del_group'), {group: group}, function() {
                $.alerts._hide();
                $('div[data-grp="' + group + '"]').fadeOut('slow', function() {
                   $(this).remove();
                });
            }); 
        });
    });
    
    $('#add-group').off('click').on('click', function() {
        add_group();
    });
    $('.edit-group').off('click').on('click', function() {
        add_group({
            group: $(this).data('group'),
            strategy: $(this).data('strategy'),
            rotate_period: $(this).data('rotate_period')
        });
    });
    
    $('a[data-add-pool-group]').off('click').on('click', function() {
        var group = $(this).data('add-pool-group');
        var dialog = "";
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="url">Pool url:</label>';
        dialog += '            <input type="text" id="url" style="position: absolute;margin-left: 210px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="username">Worker username:</label>';
        dialog += '            <input type="text" id="username" style="position: absolute;margin-left: 210px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="password">Worker password:</label>';
        dialog += '            <input type="text" id="password" style="position: absolute;margin-left: 210px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="quota">Pool Quota for load balance:</label>';
        dialog += '            <input type="text" id="quota" value="1" style="position: absolute;margin-left: 210px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element" id="rig_based_elm">';
        dialog += '            <label for="rig_based">Enable rig-based usernames:<i data-toggle="tooltip" title="When you enable this and switch with one rig to this pool, the username which is used for this pool will be appended with \'.rb{rigname}\' where {rigname} will be replaced with the rigname. Notice all characters which are not a-z, A-Z or 0-9 will be removed. This is helpfull when using mining pools which have VARDIF activated. For example you have 2 rigs first name is: \'My rig 5\', second \'My other rig 1\' and you provide the userame \'user.myrigs\'. When you switch with rig \'My rig 5\' the used username will be \'user.myrigs.rbmyrig5\' now you also switch with  \'My other rig 1\' then the used username will be \'user.myrigs.rbmyotherrig1\'. Don\'t be afraid, after you activate the checkbox all required usernames will be displayed." class="icon-help-circled"></i></label>';
        dialog += '            <input type="checkbox" id="rig_based" value="Enabled" style="position: absolute;margin-left: 210px;width: 16px;height: 16px;margin-top: 10px;"></input>';
        dialog += '        </div>';
        dialog += '    </div>';
        
        make_modal_dialog('Add a new pool for group <b>' + group + '</b>', dialog, [
            {
                title: 'Add pool',
                type: 'primary',
                id: 'pools_add_new_pool',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    wait_dialog('Please wait');
                    ajax_request(murl('pools', 'add_pool'), {url: $('#url').val(), user: $('#username').val(), pass: $('#password').val(), group: group, quota: $('#quota').val(), rig_based: $('#rig_based').prop('checked')}, function(result) {
                        $.alerts._hide();
                        $('.pools[data-pool_group="' + group + '"]').append(
                             '<tr data-uuid="' + result.uuid + '|' + group + '">' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="url" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="' + murl('pools', 'change_pool') + '" data-title="Enter pool url">' + result.url + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="user" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="' + murl('pools', 'change_pool') + '" data-title="Enter worker username">' + result.user + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="pass" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="' + murl('pools', 'change_pool') + '" data-title="Enter worker password">' + $('#password').val() + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="quota" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="' + murl('pools', 'change_pool') + '" data-title="Pool Quota">' + $('#quota').val() + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="rig_based" data-type="checklist" data-pk="' + result.uuid + '|' + group + '" data-url="' + murl('pools', 'change_pool') + '" data-title="Rig based pool">' + (($('#rig_based').prop('checked')) ? 'Enabled' : 'Disabled') + '</a></td>' + 
                             '   <td><a href="javascript:void(0);" class="option-action" data-uuid="' + result.uuid + '|' + group + '" data-name="' + result.url + '" data-group="' + group + '" data-action="delete-pool" title="Delete"><i class="icon-trash"></i></a></td>' + 
                             '</tr>').hide().fadeIn();
                     
                        $('.modal').modal('hide');
                        $('#pools_add_new_pool').button('reset');
                        Soopfw.reload_behaviors();
                    }, function() {
                        $('#pools_add_new_pool').button('reset');
                    });
                }
            }
        ], {
            width: 660,
            show: function() {
                
                
                function check_rig_based() {
                    $('#rig_based_elm > #rig_based_help').html("");
                    if ($('#rig_based').prop('checked')) {
                        
                        var help_div = $('<div id="rig_based_help">Required usernames at the given pool:<br /></div>');
                        var help = $('<ul></ul>');
                        
                        $.each(phpminer.settings.rig_names, function(k, v) {
                            help.append('<li>For rig "<b>' + v + '</b>" the required usernames is: "<b>' + $('#username').val() + '.rb' +  v.replace(/[^a-zA-Z0-9]/, "") + '</b>"</li>');
                        });
                        help_div.append(help);
                        $('#rig_based_elm').append(help_div);
                        
                    }
                }
                
                $('*[data-toggle="tooltip"]').tooltip();
                $('#rig_based').off('click').on('click', function() {
                    check_rig_based();
                });
                $('#username').off('change').on('change', function() {
                    check_rig_based();
                });
                $('#username').off('keyup').on('keyup', function() {
                    check_rig_based();
                });
            }
        });
        
        

    });
    
    function add_group(old_data) {
        
        var edit_mode = (old_data !== undefined);
        old_data = $.extend({
            group: '',
            strategy: 0,
            rotate_period: ''
        }, old_data);
        
        var dialog = "";
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="group">Group name:</label>';
        dialog += '            <input type="text" id="group" style="position: absolute;margin-left: 180px;width: 300px;" value="' + old_data.group + '"></input>';
        dialog += '        </div>';
        if (phpminer.settings.has_advanced_api) {
            dialog += '        <div class="form-element">';
            dialog += '            <label for="strategy">Strategy:</label>';
            dialog += '            <select id="strategy" style="position: absolute;margin-left: 135px;width: 300px;">';
            dialog += '                 <option value="0"' + ((old_data.strategy === 0) ? 'selected="selected"' : '') + '>Failover</option>';
            dialog += '                 <option value="1"' + ((old_data.strategy === 1) ? 'selected="selected"' : '') + '>Round Robin</option>';
            dialog += '                 <option value="2"' + ((old_data.strategy === 2) ? 'selected="selected"' : '') + '>Rotate</option>';
            dialog += '                 <option value="3"' + ((old_data.strategy === 3) ? 'selected="selected"' : '') + '>Load Balance</option>';
            dialog += '                 <option value="4"' + ((old_data.strategy === 4) ? 'selected="selected"' : '') + '>Balance</option>';
            dialog += '            </select>';
            dialog += '        </div>';
            dialog += '        <div class="form-element" id="rotate_period" style="display: none">';
            dialog += '            <label for="period">Rotate period (minutes):</label>';
            dialog += '            <input type="text" id="period" style="position: absolute;margin-left: 135px;width: 300px;" value="' + old_data.rotate_period + '"></input>';
            dialog += '        </div>';
            dialog += '    </div>';
        }   
        var title = 'Add a pool group';
        if (edit_mode) {
            title = 'Change pool group: ' + old_data.group;
        }
        make_modal_dialog(title, dialog, [
            {
                title: 'Save',
                type: 'primary',
                id: 'pools_add_new_group',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    var group = $('#group').val();
                    wait_dialog('Please wait');
                    var send_data = {group: group};
                    send_data['strategy'] = 0;
                    send_data['rotate_period'] = 0;
                    if (phpminer.settings.has_advanced_api) {
                        send_data['strategy'] = $('#strategy').val();
                        send_data['rotate_period'] = $('#period').val();
                    }
                    var url = 'add_group';
                    if (edit_mode) {
                        send_data['old_group'] = old_data.group;
                        url = 'change_group';
                    }
                    ajax_request(murl('pools', url), send_data, function() {
                        $.alerts._hide();
                        if (!edit_mode) {
                            var guuid = uuid();
                            $('.pool_groups').append(

                                    '<div class="panel panel-default" data-grp="' + group + '">' + 
                                    '<div class="panel-heading"">' + 
                                    '    <h4 class="panel-title">' + 
                                    '        <a data-toggle="collapse" href="#collapse_' + guuid + '">' + 
                                    '            Group: ' + group + '' + 
                                    '        </a>' + 
                                    '        <a href="javascript:void(0)" class="edit-group" data-group="' + group + '" data-strategy="' + send_data['strategy'] + '" data-rotate_period="' +  send_data['rotate_period'] + '"><i class="icon-edit"></i>Edit</a>' + 
                                    '    </h4>' + 
                                    '</div>' + 
                                    '<div id="collapse_' + guuid + '" class="panel-collapse collapse in">' + 
                                    '    <div class="panel-body">' + 
                                    '        <a class="btn btn-default" data-add-pool-group="' + group + '" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a pool</a>' + 
                                    '        <a class="btn btn-danger" data-del-pool-group="' + group + '" style="margin-bottom: 10px;padding-left: 5px;float: right;"><i class="icon-minus"></i>Delete group</a>' + 
                                    '        <table>' + 
                                    '            <thead>' + 
                                    '                <tr class="pool_table">' + 
                                    '                    <th>Url</th>' + 
                                    '                    <th style="width:200px;">Username</th>' + 
                                    '                    <th style="width:200px;">Password</th>' + 
                                    '                    <th style="width:60px;">Quota</th>' + 
                                    '                    <th style="width:60px;">Rig based</th>' + 
                                    '                    <th style="width:120px;">Options</th>' + 
                                    '                </tr>' + 
                                    '            </thead>' + 
                                    '            <tbody class="pools" data-pool_group="' + group + '">' + 
                                    '            </tbody>' + 
                                    '        </table>' + 
                                    '    </div>' + 
                                    '</div>' +
                                    '</div>'

                            ).hide().fadeIn();
                        }
                        else {
                            var panel = $('.pool_groups .panel[data-grp="' + send_data['old_group'] + '"]');

                            panel.data('grp', group).attr('data-grp', group).prop('prop-grp', group);
                            $('.panel-title a[data-toggle]', panel).html('Group: ' + group);
                            $('a[data-add-pool-group]', panel).data('add-pool-group', group).attr('data-add-pool-group', group).prop('data-add-pool-group', group);
                            $('a[data-del-pool-group]', panel).data('del-pool-group', group).attr('data-del-pool-group', group).prop('data-del-pool-group', group);
                            $('.pools[data-pool_group] tr[data-uuid]', panel).each(function() {
                                var pk = $(this).data('uuid').split('|');
                                $(this).data('uuid', pk[0] + '|' + group).attr('data-uuid', pk[0] + '|' + group).prop('data-uuid', pk[0] + '|' + group)
                            });
                            
                            $('a[data-pk]', panel).each(function() {
                                var pk = $(this).data('pk').split('|');
                                $(this).data('pk', pk[0] + '|' + group).attr('data-pk', pk[0] + '|' + group).prop('data-pk', pk[0] + '|' + group)
                            });
                            
                            $('a[data-action="delete-pool"]', panel).each(function() {
                                var pk = $(this).data('uuid').split('|');
                                $(this)
                                        .data('uuid', pk[0] + '|' + group).attr('data-uuid', pk[0] + '|' + group).prop('data-uuid', pk[0] + '|' + group)
                                        .data('group', group).attr('data-group', group).prop('data-group', group)
                            });
                            
                            $('a.edit-group', panel)
                                    .data('group', group).attr('data-group', group).prop('data-group', group)
                                    .data('strategy', send_data['strategy']).attr('data-strategy', send_data['strategy']).prop('data-strategy', send_data['strategy'])
                                    .data('rotate_period', send_data['rotate_period']).attr('data-rotate_period', send_data['rotate_period']).prop('data-rotate_period', send_data['rotate_period']);
                            
                            $('.pools', panel).data('pool_group', group).attr('data-pool_group', group).prop('data-pool_group', group);
                        }
                        $('#pools_add_new_group').button('reset');
                        $('.modal').modal('hide');
                        Soopfw.reload_behaviors();
                    }, function() {
                        $('#pools_add_new_group').button('reset');
                    });
                }
            }
        ], {
            width: 660,
            show: function() {
                if (phpminer.settings.has_advanced_api) {
                    $('#strategy').change(function() {
                        if ($(this).val() + "" === "" + 2) {
                            $('#rotate_period').fadeIn();
                        }
                        else {
                            $('#rotate_period').fadeOut();
                        }
                    });
                }
            }
        });
    }
};