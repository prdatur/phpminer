Soopfw.behaviors.pools_main = function() {
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
            ajax_request('/pools/add_pool.json', {url: $(this).data('pool_url'), user: $(this).data('pool_user'), pass: $(this).val(), group: group}, function(result) {
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
            ajax_request('/pools/delete_pool.json', {uuid: $(that).data('uuid')}, function() {
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
            ajax_request('/pools/del_group.json', {group: group}, function() {
                $.alerts._hide();
                $('div[data-grp="' + group + '"]').fadeOut('slow', function() {
                   $(this).remove();
                });
            }); 
        });
    });
    
    $('#add-group').off('click').on('click', function() {
        var dialog = "";
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="group">Group name:</label>';
        dialog += '            <input type="text" id="group" style="position: absolute;margin-left: 180px;width: 300px;"></input>';
        dialog += '        </div>';
        if (phpminer.settings.has_advanced_api) {
            dialog += '        <div class="form-element">';
            dialog += '            <label for="strategy">Strategy:</label>';
            dialog += '            <select id="strategy" style="position: absolute;margin-left: 126px;width: 300px;">';
            dialog += '                 <option value="0">Failover</option>';
            dialog += '                 <option value="1">Round Robin</option>';
            dialog += '                 <option value="2">Rotate</option>';
            dialog += '                 <option value="3">Load Balance</option>';
            dialog += '                 <option value="4">Balance</option>';
            dialog += '            </select>';
            dialog += '        </div>';
            dialog += '        <div class="form-element" id="rotate_period" style="display: none">';
            dialog += '            <label for="period">Rotate period (minutes):</label>';
            dialog += '            <input type="text" id="period" style="position: absolute;margin-left: 180px;width: 300px;"></input>';
            dialog += '        </div>';
            dialog += '    </div>';
        }
        make_modal_dialog('Add a pool group', dialog, [
            {
                title: 'Add group',
                type: 'primary',
                id: 'pools_add_new_group',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    var group = $('#group').val();
                    wait_dialog('Please wait');
                    var send_data = {group: group};
                    if (phpminer.settings.has_advanced_api) {
                        send_data['strategy'] = $('#strategy').val();
                        send_data['period'] = $('#period').val();
                    }
                    ajax_request('/pools/add_group.json', send_data, function() {
                        $.alerts._hide();
                        var guuid = uuid();
                        $('.pool_groups').append(
                        
                                '<div class="panel panel-default" data-grp="' + group + '">' + 
                                '<div class="panel-heading"">' + 
                                '    <h4 class="panel-title">' + 
                                '        <a data-toggle="collapse" href="#collapse_' + guuid + '">' + 
                                '            Group: ' + group + '' + 
                                '        </a>' + 
                                '    </h4>' + 
                                '</div>' + 
                                '<div id="collapse_' + guuid + '" class="panel-collapse collapse in">' + 
                                '    <div class="panel-body">' + 
                                '        <a class="btn btn-default" data-add-pool-group="' + group + '" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a pool</a>' + 
                                '        <a class="btn btn-danger" data-del-pool-group="' + group + '" style="margin-bottom: 10px;padding-left: 5px;float: right;"><i class="icon-minus"></i>Delete group</a>' + 
                                '        <table>' + 
                                '            <thead>' + 
                                '                <tr>' + 
                                '                    <th>Url</th>' + 
                                '                    <th style="width:200px;">Username</th>' + 
                                '                    <th style="width:200px;">Password</th>' + 
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

    });
    
    $('a[data-add-pool-group]').off('click').on('click', function() {
        var group = $(this).data('add-pool-group');
        var dialog = "";
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="url">Pool url:</label>';
        dialog += '            <input type="text" id="url" style="position: absolute;margin-left: 190px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="username">Worker username:</label>';
        dialog += '            <input type="text" id="username" style="position: absolute;margin-left: 190px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="password">Worker password:</label>';
        dialog += '            <input type="text" id="password" style="position: absolute;margin-left: 190px;width: 300px;"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="quota">Pool Quota for load balance:</label>';
        dialog += '            <input type="text" id="quota" value="1" style="position: absolute;margin-left: 190px;width: 300px;"></input>';
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
                    console.log($('#quota').val());
                    ajax_request('/pools/add_pool.json', {url: $('#url').val(), user: $('#username').val(), pass: $('#password').val(), group: group, quota: $('#quota').val()}, function(result) {
                        $.alerts._hide();
                        $('.pools[data-pool_group="' + group + '"]').append(
                             '<tr data-uuid="' + result.uuid + '|' + group + '">' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="url" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="/pools/change_pool.json" data-title="Enter pool url">' + result.url + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="user" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="/pools/change_pool.json" data-title="Enter worker username">' + result.user + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="pass" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="/pools/change_pool.json" data-title="Enter worker password">' + $('#password').val() + '</a></td>' + 
                             '   <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="quota" data-type="text" data-pk="' + result.uuid + '|' + group + '" data-url="/pools/change_pool.json" data-title="Pool Quota">' + $('#quota').val() + '</a></td>' + 
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
            width: 660
        });

    });
};