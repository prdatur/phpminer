Soopfw.behaviors.access_index = function() {

    if (empty(phpminer.settings.users) && phpminer.settings.users !== false) {
        add_user();
    }
    else {
        if (phpminer.settings.users === false) {
            $('#user_list').html('<tr><td colspan="2">You have no permission to manage users.</td></tr>');
        }
        else {
            $.each(phpminer.settings.users, function(tmp, user) {
                append_user(user);
            });
        }
    }
    
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
    
    $('#add_user').off('click').on('click', function() {
       add_user(); 
    });
    
   
    if (phpminer.settings.groups === false) {
        $('#user_list').html('<tr><td colspan="2">You have no permission to manage groups.</td></tr>');
    }
    else {
        $.each(phpminer.settings.groups, function(tmp, group) {
            append_group(group);
        });
    }
    
    
    $('#add_group').off('click').on('click', function() {
       add_group(); 
    });
    
};

function append_user(user, returnValue) {
    
    var value = $('<tr data-user="' + user.username + '">')
        .append(
            $('<td>').html(user.username)
        )
        .append(
            $('<td>')
                .append(
                    $('<a>', {href: 'javascript:void(0);'}).html('Edit').off('click').on('click', function() {
                        add_user(phpminer.settings.users[user.username]);
                    })
                )
                .append(' - ')
                .append(
                    $('<a>', {href: 'javascript:void(0);'}).html('Delete').off('click').on('click', function() {
                        delete_user(user);
                    })
                )
        )
    ;
    
    if (returnValue !== undefined) {
        return value;
    }
    
    $('#user_list').append(value).hide().fadeIn('fast');
}

function delete_user(user) {
    confirm('Do you really want to delete the user "' + user.username + '"?', 'Delete user', function() {
        ajax_request(murl('access', 'delete_user'), {username: user.username}, function() {
            delete phpminer.settings.users[user.username];
            $('tr[data-user="' + user.username + '"]').fadeOut('fast', function() {
                $(this).remove();
            });
        });
    });
}
 
function change_user(old_username, user) {
    $('tr[data-user="' + old_username + '"]').fadeOut('fast', function() {
        $(this).replaceWith(append_user(user, true)).hide().fadeIn('fast');
    })
}
function add_user(old_data) {
    var dialog = "";

    if (empty(phpminer.settings.users)) {
        dialog += 'You have enabled access control and currently no user exists, please add a user which will be directly an admin.';
    }
    dialog += '    <div class="simpleform add_user_form">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="username">Username:</label>';
    dialog += '            <input type="text" id="username" value="' + ((old_data !== undefined) ? old_data.username : '') + '"></input>';
    dialog += '        </div>';
    if (!empty(phpminer.settings.users)) {
        dialog += '        <div class="form-element">';
        dialog += '            <label for="group">Access group:</label>';
        dialog += '            <select id="group">';
        $.each(phpminer.settings.groups, function(group, group_data) {
            dialog += '            <option value="' + group + '"' + ((old_data !== undefined && old_data.group === group) ? ' selected="selected"' : '') + '>' + group_data.name + '</option>';
        });
        dialog += '            </select>';
        dialog += '        </div>';
    }
    
    if (old_data !== undefined) {
        dialog += "Only provide password when you want to change the user password, else leave it empty.";
    }
    
    dialog += '        <div class="form-element">';
    dialog += '            <label for="password">Password:</label>';
    dialog += '            <input type="password" id="password" value=""></input>';
    dialog += '        </div>';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="password2">Re-type Password:</label>';
    dialog += '            <input type="password" id="password2" value=""></input>';
    dialog += '        </div>';
    dialog += '    </div>';

    make_modal_dialog('Add user', dialog, [
        {
            title: 'Save',
            type: 'primary',
            id: 'access_index_add_user',
            data: {
                "loading-text": 'Saving...'
            },
            click: function() {
                if ($('#password').val() !== $('#password2').val()) {
                    $('#access_index_add_user').button('reset');
                    alert('Passwords missmatch');
                }
                else {
                    wait_dialog();
                    var data = {username: $('#username').val(), password: $('#password').val()};
                    if (!empty(phpminer.settings.users)) {
                        data['group'] = $('#group').val();
                    }
                    if (old_data !== undefined) {
                        data['old_user'] = old_data.username;
                    }
                    ajax_request(murl('access', 'user_add'), data, function() {
                        $.alerts._hide();
                        if (empty(phpminer.settings.users)) {
                            Soopfw.reload();
                        }
                        else {
                            if (old_data !== undefined) {
                                delete phpminer.settings.users[old_data.username];
                            }
                            
                            phpminer.settings.users[data['username']] = {
                                username: data['username'],
                                group: data['group'],    
                            };
                            
                            var success_msg = '';
                            if (old_data !== undefined) {
                                success_msg = 'User changed successfully';
                                change_user(old_data.username, phpminer.settings.users[data['username']]);
                            }
                            else {
                                success_msg = 'User added successfully';
                                append_user(phpminer.settings.users[data['username']]);
                            }
                            success_alert(success_msg, function() {
                                $('.modal').fadeOut('fast', function() {
                                    $(this).remove();
                                });
                            });
                        }
                        $('#access_index_add_user').button('reset');
                    }, function() {
                        $('#access_index_add_user').button('reset');
                    });
                }
            }
        }
    ], {
        width: 660
    });
}

function append_group(group, returnValue) {
    
    var option_td = $('<td>');
    if (group.name !== 'admin') {
        option_td.append(
            $('<a>', {href: 'javascript:void(0);'}).html('Edit').off('click').on('click', function() {
                add_group(phpminer.settings.groups[group.name]);
            })
        )
        .append(' - ')
        .append(
            $('<a>', {href: 'javascript:void(0);'}).html('Delete').off('click').on('click', function() {
                delete_group(group);
            })
        );
    }
    else {
        option_td.html("Admin group can't be changed.");
    }
    var value = $('<tr data-group="' + group.name + '">')
        .append(
            $('<td>').html(group.name)
        )
        .append(option_td)
    ;
    
    if (returnValue !== undefined) {
        return value;
    }
    
    $('#group_list').append(value).hide().fadeIn('fast');
}

function delete_group(group) {
    confirm('Do you really want to delete the group "' + group.name + '"?', 'Delete group', function() {
        ajax_request(murl('access', 'delete_group'), {name: group.name}, function() {
            delete phpminer.settings.groups[group.name];
            $('tr[data-group="' + group.name + '"]').fadeOut('fast', function() {
                $(this).remove();
            });
        });
    });
}
 
function change_group(old_name, group) {
    $('tr[data-group="' + old_name + '"]').fadeOut('fast', function() {
        $(this).replaceWith(append_group(group, true)).hide().fadeIn('fast');
    })
}
function add_group(old_data) {
    var dialog = "";

    dialog += '    <div class="simpleform add_group_form">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="username">Name:</label>';
    dialog += '            <input type="text" id="name" value="' + ((old_data !== undefined) ? old_data.name : '') + '"></input>';
    dialog += '        </div>';
    dialog += '        <h4>Permissions</h4>';
    dialog += '         <div class="permission_checkbox">';
    $.each(phpminer.settings.possible_permissions, function(permission, help_text) {
        dialog += '        <div class="form-element">';
        dialog += '            <label for="permission_' + permission + '">' + permission + ':<i data-toggle="tooltip" title="' + help_text + '" data-placement="right" class="icon-help-circled"></i></label>';
        dialog += '            <input type="checkbox" class="permissions" id="permission_' + permission + '" value="' + permission + '"' + ((old_data !== undefined && !empty(old_data.permissions[permission])) ? ' checked="checked"' : '') + '"></input>';
        dialog += '        </div>';
    })
    dialog += '         </div>';
    dialog += '    </div>';

    make_modal_dialog('Add group', dialog, [
        {
            title: 'Save',
            type: 'primary',
            id: 'access_index_add_group',
            data: {
                "loading-text": 'Saving...'
            },
            click: function() {
               
                wait_dialog();
                var data = {name: $('#name').val(), permissions: []};
               
                $('.permissions').each(function() {
                    if ($(this).prop('checked')) {
                        data.permissions.push($(this).val());
                    }
                });
                if (old_data !== undefined) {
                    data['old_name'] = old_data.name;
                }
                ajax_request(murl('access', 'group_add'), data, function() {
                    $.alerts._hide();

                    if (old_data !== undefined) {
                        delete phpminer.settings.groups[old_data.name];
                    }

                    phpminer.settings.groups[data['name']] = {
                        name: data['name'],
                        permissions: {}
                    };
                    
                    $.each(data['permissions'], function(tmp, permission) {
                        phpminer.settings.groups[data['name']]['permissions'][permission] = true;
                    });

                    var success_msg = '';
                    if (old_data !== undefined) {
                        success_msg = 'Group changed successfully';
                        change_group(old_data.name, phpminer.settings.groups[data['name']]);
                    }
                    else {
                        success_msg = 'Group added successfully';
                        append_group(phpminer.settings.groups[data['name']]);
                    }
                    success_alert(success_msg, function() {
                        $('.modal').fadeOut('fast', function() {
                            $(this).remove();
                        });
                    });
                    
                    $('#access_index_add_group').button('reset');
                }, function() {
                    $('#access_index_add_group').button('reset');
                });
            }
            
        }
    ], {
        width: 660,
        show: function() {
            $('.permission_checkbox *[data-toggle="tooltip"]').tooltip();
        }
    });
}
