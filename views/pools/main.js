Soopfw.behaviors.pools_main = function() {
    /**
     * Main class for pool groups.
     * @returns {undefined}
     */
    function PoolGroups() {

        /**
         * Holds the main container.
         * 
         * @type JQuery;$
         */
        var element = $('#accordion');

        /**
         * Holds all pool groups.
         * 
         * @type Object
         */
        var groups = {};

        /**
         * Back reference.
         * 
         * @type PoolGroups
         */
        var back_reference = this;

        /**
         * Adds a new pool group.
         * 
         * @param {Object} data
         *   The pool group data.
         *   
         * @returns {undefined}
         */
        this.add_pool_group = function(data) {
            var tmpid = uuid();
            groups[tmpid] = new PoolGroup(this, data, tmpid);
            element.append(groups[tmpid].render());
        };

        /**
         * Remove a pool group.
         * 
         * @param {String} uuid
         *   The pool group uuid.
         *   
         * @returns {undefined}
         */
        this.remove_pool_group = function(group_uuid) {
            var elm = groups[group_uuid].get_element();
            elm.fadeOut('fast', function() {
                elm.remove();
                delete groups[group_uuid];
                success_alert('Pool group successfully deleted');
            });
        };

        /**
         * Update a pool group.
         * 
         * @param {String} uuid
         *   The pool group uuid.
         * @param {Object} new_data
         *   The new data.
         *   
         * @returns {undefined}
         */
        this.update_pool_group = function(group_uuid, group_data) {
            groups[group_uuid].update(group_data);
        };

        // Prefill pool groups
        if (phpminer.settings.pools !== undefined) {
            $.each(phpminer.settings.pools, function(tmp, pool_group_data) {
                back_reference.add_pool_group(pool_group_data);
            })
        }
    }

    /**
     * Represents a single pool group.
     * 
     * @returns {undefined}
     */
    function PoolGroup(poolgroups, prefill_values, group_uuid) {

        /**
         * Holds a uniq identifier.
         * 
         * @type string
         */
        var group_uuid = group_uuid;

        /**
         * The main html element.
         * 
         * @type JQuery|$
         */
        var element = $('<div>', {class: 'panel panel-default'});

        /**
         * Back reference to pool groups.
         * 
         * @type PoolGroups
         */
        var pool_groups = poolgroups;

        /**
         * Holds the group data.
         * 
         * @type Object
         */
        var data = prefill_values.group;

        /**
         * Holds all pools.
         * 
         * @type Pool
         */
        var pools = {};

        /**
         * Back reference.
         * 
         * @type PoolGroup
         */
        var back_reference = this;

        /**
         * Returns the main element.
         * 
         * @returns {JQuery|$|$|PoolGroup.element}
         */
        this.get_element = function() {
            return element;
        };

        /**
         * Updates this pools group.
         * 
         * @param {Object} newdata
         *   The new data.
         *   
         * @returns {undefined}
         */
        this.update = function(newdata) {

            // Set new data.
            if (newdata !== undefined) {
                data = $.extend(true, data, newdata.group);
            }

            // Update each pool with the maybe new pool group name.
            $.each(pools, function(tmp, pool) {
                pool.update({
                    group: data.name
                });
            });
            
            // Tell all elements that data has changed.
            $('*', element).trigger('update');
            
            this.pool_table.tableDnD({
                dragHandle: 'handle_cell > .tabledrag-handle',
                enableIndent: false,
                indentSerializeAsObject: true,
                onDrop:function(table) {
                    var new_order = {};
                    var counter = 0;
                    $('tbody > tr', table).each(function() {
                        var uuid = $(this).data('pool_uuid');
                        var pool_data = pools[uuid].get_data();
                        new_order[pool_data.uuid] = counter;
                        counter++;
                    });
                    ajax_request(murl('pools', 'change_sort_order'), {new_order: new_order, group: data.name});
                }
            })
        };

        /**
         * Adds a new pool.
         * 
         * @param {Object} data
         *   The pool data.
         *   
         * @returns {undefined}
         */
        this.add_pool = function(data) {
            var tmpid = uuid();
            pools[tmpid] = new Pool(pool_groups, this, data, tmpid);
            this.pool_container.append(pools[tmpid].render());
        };

        /**
         * Remove a pool.
         * 
         * @param {String} uuid
         *   The pool uuid.
         *   
         * @returns {undefined}
         */
        this.remove_pool = function(pool_uuid) {
            var elm = pools[pool_uuid].get_element();
            elm.fadeOut('fast', function() {
                elm.remove();
                delete pools[pool_uuid];
                success_alert('Pool successfully deleted');
            });
        };

        /**
         * Renders a single pool group.
         * 
         * @returns {JQuery|$}
         */
        this.render = function() {

            // Create group edit link. Will only be visible if not default group.
            var group_edit = '';
            if (data.group !== 'default') {
                group_edit = $('<a>', {href: 'javascript:void(0);'})
                    .append($('<i>', {class: 'icon-edit'}))
                    .append('Edit')
                    .off('click').on('click', function() {
                        add_group({
                            uuid: group_uuid,
                            group: data.name,
                            strategy: data.strategy,
                            rotate_period: data.rotate_period,
                            miner: data.miner
                        });
                    })
                ;
            }
            this.panel_header = $('<div>', {class: 'panel-heading'})
                .append(
                    $('<h4>', {class: 'panel-title'})
                        .append(
                            $('<a>', {href: 'javascript: void(0);'})
                                .on('update', function() {
                                    $(this).html('Group: ' + data.name)
                                })
                                .on('click', function() {
                                    back_reference.collapse_container.collapse('toggle');
                                })
                        )
                        .append(group_edit)
                )
                .appendTo(element);

            this.collapse_container = $('<div>', {class: 'panel-collapse in'}).appendTo(element);
            this.panel_body = $('<div>', {class: 'panel-body'}).appendTo(this.collapse_container);

            // Check for change permission
            if (phpminer.settings.can_change) {

                // Create button to add a pool.
                this.add_pool_btn = $('<a>', {class: 'btn btn-primary btn-sm'})
                    .append($('<i>', {class: 'icon-plus'}))
                    .append('Add a pool')
                    .off('click').on('click', function() {

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

                        make_modal_dialog('Add a new pool for group <b>' + data.name + '</b>', dialog, [
                            {
                                title: 'Add pool',
                                type: 'primary',
                                id: 'pools_add_new_pool',
                                data: {
                                    "loading-text": 'Saving...'
                                },
                                click: function() {
                                    wait_dialog('Please wait');
                                    var pool_data = {url: $('#url').val(), user: $('#username').val(), pass: $('#password').val(), group: data.name, quota: $('#quota').val(), rig_based: $('#rig_based').prop('checked')};
                                    ajax_request(murl('pools', 'add_pool'), pool_data, function(result) {
                                        $.alerts._hide();
                                        pool_data['uuid'] = result.uuid;
                                        back_reference.add_pool(pool_data);
                                        $('.modal').modal('hide');
                                        $('#pools_add_new_pool').button('reset');
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

                    })
                    .appendTo(this.panel_body);

                // Create button to remove this group.
                this.remove_pool_group_btn = $('<a>', {class: 'btn btn-danger btn-sm'})
                    .append($('<i>', {class: 'icon-minus'}))
                    .append('Delete group')
                    .off('click').on('click', function() {
                        confirm('Do you really want to delete this group? All configurated pools within this group will also be deleted.', 'Delete pool group', function() {
                            wait_dialog('Please wait');
                            ajax_request(murl('pools', 'del_group'), {group: data.name}, function() {
                                $.alerts._hide();
                                pool_groups.remove_pool_group(group_uuid);
                            }); 
                        });
                    })
                    .appendTo(this.panel_body);
            }

            var option_th = '';
            var dnd_th = '';
            // If we have access, fill up options th.
            // Check for change permission
            if (phpminer.settings.can_change) {
                option_th = $('<th>').css('width', 120).html('Options');
                dnd_th = $('<th>').css('width', 24).html('');
            }

            // Create the container for pools.
            this.pool_container = $('<tbody>', {class: 'pools'});

            // Create main table which holds pools.
            this.pool_table = $('<table>')
                .append(
                    $('<thead>')
                        .append(
                            $('<tr>', {class: 'pool_table'})
                                .append(dnd_th)
                                .append($('<th>').html('Url'))
                                .append($('<th>').css('width', 200).html('Username'))
                                .append($('<th>').css('width', 200).html('Password'))
                                .append($('<th>').css('width', 60).html('Quota'))
                                .append($('<th>').css('width', 60).html('Rig based'))
                                .append(option_th)
                        )
                )
                .append(this.pool_container)
                .appendTo(this.panel_body);
        
            // Prefill pools
            if (phpminer.settings.pools[data.name] !== undefined) {
                $.each(phpminer.settings.pools[data.name].pools, function(tmp, pool_data) {
                    back_reference.add_pool(pool_data);
                })
            }
            
            // Tell all elements that data has changed.
            this.update();

            // Return the main container element.
            return element;
        };

        

    }

    /**
     * Represents a single pool.
     * 
     * @returns {undefined}
     */
    function Pool(poolgroups, poolgroup, prefill_values, pool_uuid) {

        /**
         * Holds a uniq identifier.
         * 
         * @type string
         */
        var pool_uuid = pool_uuid;

        /**
         * The main html element.
         * 
         * @type JQuery|$
         */
        var element = $('<tr>').data('pool_uuid', pool_uuid);

        /**
         * Back reference to pool groups.
         * 
         * @type PoolGroups
         */
        var pool_groups = poolgroups;

        /**
         * Back reference to pool group.
         * 
         * @type PoolGroup
         */
        var pool_group = poolgroup;

        /**
         * Holds the group name.
         * 
         * @type String
         */
        var data = prefill_values;
        
        /**
         * Back reference.
         * 
         * @type PoolGroup
         */
        var back_reference = this;
        
        /**
         * Returns the main element.
         * 
         * @returns {JQuery|$|$|PoolGroup.element}
         */
        this.get_element = function() {
            return element;
        };
        
        /**
         * Returns the data.
         * 
         * @returns {Object}
         */
        this.get_data = function() {
            return data;
        };

        /**
         * Holds the editable success callback.
         * 
         * @returns {undefined}
         */
        var editable_success = function(response, new_value) {

            parse_ajax_result(response, function(result) {
                data.uuid = result.new;
                data.url = result.url;
                data.user = result.user;
                data.pass = result.pass;
                data.quota = result.quota;
                data.rig_based = result.rig_based;
                back_reference.update();
            });
        };

        /**
         * Holds the options for the editable.
         * @type Object
         */
        var editable_options = {
            ajaxOptions: {
                dataType: 'json'
            },
            source: [
                {value: 'Enabled', text: 'Enabled'},
            ],
            emptytext: 'Disabled',
            success: function(response, new_value) {
                editable_success(response, new_value);
            }
        };

        /**
         * Updates this pool.
         * 
         * @param {Object} newdata
         *   The new data.
         *   
         * @returns {undefined}
         */
        this.update = function(newdata) {

            // Set new data if available..
            if (newdata !== undefined) {
                data = $.extend(true, data, newdata);
            }
                    
            $.each([this.td_url, this.td_user, this.td_pass, this.td_quota, this.td_rig_based], function(tmp, element) {
                
                var elm = $('a', element).attr('data-name');
                var new_value = data[elm];
                if (elm === 'rig_based') {
                    new_value = (data.rig_based === true) ? 'Enabled' : 'Disabled'
                }
                
                $('a', element)
                        .attr('data-pk', data.uuid + '|' + data.group)
                        .data('pk', data.uuid + '|' + data.group)
                        .editable('destroy')
                        .html(new_value)
                        .editable(editable_options);
            });
        };

        /**
         * Renders a single pool.
         * 
         * @returns {JQuery|$}
         */
        this.render = function() {
            
            // Check for change permission
            if (phpminer.settings.can_change) {
                
                // Append table drag and drop handle.
                $('<td>', {class: 'handle_cell'})
                    .append(
                        $('<a>', {class: 'tabledrag-handle', href: 'javascript:void(0);', title: 'drag and drop to move'}).append(
                            $('<i>', {class: 'handle icon-move'})
                        )
                    )
                    .appendTo(element);
                
                // Url.
                this.td_url = $('<td>', {class: 'nopadding'})
                    .append(
                        $('<a>', {href: 'javascript: void(0);', class: 'clickable'}).attr({'data-name': 'url', 'data-type': 'text', 'data-pk': data.uuid + '|' + data.group, 'data-url': murl('pools', 'change_pool', null, true), 'data-title': 'Enter pool url'}).html(data.url)
                    )
                    .appendTo(element);
            
                // User
                this.td_user = $('<td>', {class: 'nopadding'})
                    .append(
                        $('<a>', {href: 'javascript: void(0);', class: 'clickable'}).attr({'data-name': 'user', 'data-type': 'text', 'data-pk': data.uuid + '|' + data.group, 'data-url': murl('pools', 'change_pool', null, true), 'data-title': 'Enter worker username'}).html(data.user)
                    )
                    .appendTo(element);

                // Pass        
                this.td_pass = $('<td>', {class: 'nopadding'})
                    .append(
                        $('<a>', {href: 'javascript: void(0);', class: 'clickable'}).attr({'data-name': 'pass', 'data-type': 'text', 'data-pk': data.uuid + '|' + data.group, 'data-url': murl('pools', 'change_pool', null, true), 'data-title': 'Enter worker password'}).html(data.pass)
                    )
                    .appendTo(element);

                // Quota
                this.td_quota = $('<td>', {class: 'nopadding'})
                    .append(
                        $('<a>', {href: 'javascript: void(0);', class: 'clickable'}).attr({'data-name': 'quota', 'data-type': 'text', 'data-pk': data.uuid + '|' + data.group, 'data-url': murl('pools', 'change_pool', null, true), 'data-title': 'Pool Quota'}).html(data.quota)
                    )
                    .appendTo(element);
            
                

                // Rig based
                this.td_rig_based = $('<td>', {class: 'nopadding'})
                    .append(
                        $('<a>', {href: 'javascript: void(0);', class: 'clickable'}).attr({'data-name': 'rig_based', 'data-type': 'checklist', 'data-pk': data.uuid + '|' + data.group, 'data-url': murl('pools', 'change_pool', null, true), 'data-title': 'Enable rig based pool'}).html((data.rig_based === true) ? 'Enabled' : 'Disabled')
                    )
                    .appendTo(element);
                    
                // Make it editable.
                $.each([this.td_url, this.td_user, this.td_pass, this.td_quota, this.td_rig_based], function() {
                    $('a', this).editable('destroy').editable(editable_options);
                });
                
                // Create delete button.
                $('<td>')
                    .append(
                        $('<a>', {href: 'javascript: void(0);', class: 'option-action', title: 'Delete pool'}).append($('<i>', {class: 'icon-trash'})).off('click').on('click', function() {
                            confirm('Do you really want to delete pool <b>' + data.url + '</b> from group <b>' + data.group + '</b>', 'Delete pool from group', function() {
                                wait_dialog('Please wait');
                                ajax_request(murl('pools', 'delete_pool'), {uuid: data.uuid + '|' + data.group}, function() {
                                    $.alerts._hide();
                                    pool_group.remove_pool(pool_uuid);
                                });
                            });
                        })
                    )
                    .appendTo(element);

            }
            else {
                // Just add the values when change is not allowed.
                $('<td>', {class: 'nopadding'}).html(data.url).appendTo(element);
                $('<td>', {class: 'nopadding'}).html(data.user).appendTo(element);
                $('<td>', {class: 'nopadding'}).html(data.pass).appendTo(element);
                $('<td>', {class: 'nopadding'}).html((data.quota !== undefined) ? data.quota  : '1').appendTo(element);
                $('<td>', {class: 'nopadding'}).html((data.rig_based === true) ? 'Enabled'  : 'Disabled').appendTo(element);
            }
            
            this.update();
            
            return element;
        };
    }

    var pool_groups = new PoolGroups();
    
    $('#add-group').off('click').on('click', function() {
        add_group();
    });
        
    function add_group(old_data) {
        
        var edit_mode = (old_data !== undefined);
        old_data = $.extend({
            uuid: '',
            group: '',
            strategy: 0,
            rotate_period: '',
            miner: ''
        }, old_data);
        
        var dialog = "";
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="group">Group name:</label>';
        dialog += '            <input type="text" id="group" style="position: absolute;margin-left: 180px;width: 300px;" value="' + old_data.group + '"></input>';
        dialog += '        </div>';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="miner">Miner:</label>';
        dialog += '            <select id="miner" style="position: absolute;margin-left: 138px;width: 300px;">';
        $.each (phpminer.settings.available_miners, function(tmp, miner) {
            dialog += '                 <option value="' + miner + '"' + ((old_data.miner === miner) ? 'selected="selected"' : '') + '>' + miner+ '</option>';            
        });
        dialog += '            </select>';
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
                    var send_data = {
                        group: group,
                        miner: $('#miner').val()
                    };
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
                            pool_groups.add_pool_group({
                                group: {
                                    name: send_data['group'],
                                    strategy: send_data['strategy'],
                                    rotate_period: send_data['rotate_period'],
                                    miner: send_data['miner']
                                },
                                pools: {}
                            });
                        }
                        else {
                            pool_groups.update_pool_group(old_data.uuid, {
                                group: {
                                    name: send_data['group'],
                                    strategy: send_data['strategy'],
                                    rotate_period: send_data['rotate_period'],
                                    miner: send_data['miner']
                                },
                                pools: {}
                            })
                        }
                        $('#pools_add_new_group').button('reset');
                        $('.modal').modal('hide');
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