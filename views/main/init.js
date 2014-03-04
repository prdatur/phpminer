/**
 * Main handle for the overview.
 */
function RigOverview() {

    /**
     * Holds the global hashrate of all rigs.
     * 
     * @type Number
     */
    this.global_hashrate = 0;

    /**
     * The refresh interval.
     * 
     * @type Interval
     */
    this.refresh_device_list_timeout = null;

    /**
     * Internal counter for rigs.
     * 
     * @type Number
     */
    this.rig_counter = 0;

    /**
     * Holds the current settings.
     * 
     * @type Object
     */
    this.settings = $.extend(true, {}, phpminer.settings);
    
    /**
     * Back reference for private methods.
     * 
     * @type RigOverview
     */
    var myself = this;
    
    /**
     * Holds all rigs as RigDetails objects.
     * 
     * @type Object
     */
    this.rigs = {};
    
    /**
     * Start the interval to refresh the rigs.
     * 
     * @returns {undefined}
     */
    var start_refresh_interval = function() {
        // Clear exsting intervals to refresh the rigs
        if (myself.refresh_device_list_timeout !== null) {
            clearInterval(myself.refresh_device_list_timeout);
        }
        
        // Start interval.
        myself.refresh_device_list_timeout = setInterval(myself.refresh_update_device_list, myself.get_config(['ajax_refresh_intervall'], 5000));
    };
    
    /**
     * Clears the interval to refresh the rigs.
     * 
     * @returns {undefined}
     */
    var clear_refresh_interval = function() {
        // Clear exsting intervals to refresh the rigs
        if (myself.refresh_device_list_timeout !== null) {
            clearInterval(myself.refresh_device_list_timeout);
        }
    };
    
    /**
     * This is our main interval, which updates the current values, based on their values.
     * 
     * @returns {undefined}
     */
    this.refresh_update_device_list = function() {
                
        // Gather the rig's which we need to update.
        var rigs_to_update = [];
        $.each(myself.rigs, function(rig, data) {
            rigs_to_update.push(rig);
        });
        
        // Get fresh data for the given rig's.
        ajax_request(murl('main', 'get_device_list'), {rigs: rigs_to_update}, function(result) {

            // Update rig values.
            $.each(result, function(rig, devices) {
                var rig_o = myself.get_rig(rig);
                if (rig_o !== null) {
                    rig_o.update(devices);
                }
            });
            
            // Set the global hasrate.
            $('#global_hashrate').html(myself.get_hashrate_string(myself.get_global_hashrate()));
        });
    };
    
    /**
     * Init function.
     * 
     * @returns {undefined}
     */
    this.init = function() {
        
        // Back reference.
        var that = this;
        
        // Refresh settings.
        that.settings = $.extend(true, {}, phpminer.settings);

        // Clear exsting intervals to refresh the rigs.
        clear_refresh_interval();

        // Add all configurated rig's.
        $.each(that.settings.rig_data, function(rig, rig_data) {
            that.add_rig(rig, rig_data);
        });
        
        // Set global hashrate.
        $('#global_hashrate').html(this.get_hashrate_string(this.get_global_hashrate()));
        
        // Init pager.
        this.init_pager();
        
        // Start interval to get fresh data.
        start_refresh_interval();

    };
    
    /**
     * Refresh the list for example when switching the page.
     * 
     * @param {Number} page
     *   The page to retrieve.
     * @param {Number} mepp
     *   The max entries per page.
     *   
     * @returns {undefined}
     */
    this.refresh_device_list = function(page, mepp) {
        
        // Back reference.
        var that = this;
        
        // Clear exsting intervals to refresh the rigs
        clear_refresh_interval();
        
        // Get fresh data.
        ajax_request(murl('main', 'get_device_list'), {page: page, mepp: mepp}, function(result) {
            
            // Remove all rigs.
            $.each(that.rigs, function(rig, tmp) {
               that.delete_rig(rig); 
            });

            // Because we removed all rig's, add the rig's from the result. 
            $.each(result, function(rig, devices) {               
                that.add_rig(rig, devices);
            });
            
            // Set global hashrate.
            $('#global_hashrate').html(this.get_hashrate_string(this.get_global_hashrate()));
            
            // Restart the update interval.
            start_refresh_interval();
        });
    };
    
    /**
     * Add a new rig.
     * 
     * @param {string} rig
     *   The rig name.
     * @param {object} rig_data
     *   The rig data.
     *   
     * @returns {undefined}
     */
    this.add_rig = function(rig, rig_data) {

        // If we provide rig_data, then we want to override the current settings.
        if (rig_data !== undefined) {
            this.settings.rig_data[rig] = rig_data;
        }
        // Add the rig.
        this.rigs[rig] = new RigDetails(this, rig, rig_data);
        
        $('#rigs').append(this.rigs[rig].render());
    };
    
    /**
     * Deletes the given rig.
     * 
     * @param {String} rig
     *   The rig which should be deleted.
     *   
     * @returns {undefined}
     */
    this.delete_rig = function(rig) {
        var that = this;
        var the_rig = that.get_rig(rig);
        if (the_rig !== null) {
            the_rig.delete();
            delete that.rigs[rig];
        }
    };
    
    /**
     * Returns the count if configured rig's.
     * 
     * @returns {Number}
     *   The count.
     */
    this.get_rig_count = function() {
        var count = 0;
        foreach(this.rigs, function() {
            count++;
        });
        return count;
    };
    
    /**
     * Returns the given rig.
     * 
     * @param {String} rig
     *   The rig to retrieve.
     *   
     * @returns {RigDetails|null}
     *   Returns the rig or null if not found.
     */
    this.get_rig = function(rig) {
        if (this.rigs[rig] === undefined) {
            return null;
        }
        return this.rigs[rig];
    };
    
    /**
     * Reset the stats of one or all rig's.
     * 
     * @param {String} rig
     *   The rig to be resetted, if ommited all rig's will be resetted (Optional)
     *   
     * @returns {undefined}
     */
    this.reset_stats = function(rig) {
        var data = {};
        if (rig !== undefined) {
            data['rig'] = rig;
        }
        ajax_request(murl('main', 'reset_stats'), data, function() {
            success_alert('Stats resetted');
        });
    };
    
    /**
     * Returns the global hashrate of all rig's.
     * 
     * @returns {Number}
     *   The hashrate
     */
    this.get_global_hashrate = function() {
        var sum = 0;
        $.each(this.rigs, function(tmp, rig) {
            sum += rig.get_rig_hashrate();
        });
        return sum;
    };
    
    /**
     * Get the config value for the given path.
     * 
     * @param {Array} name
     *   The config key path as an array, each value represents on child of the config path.
     * @param {Mixed} default_val
     *   The default value which will be returned when the config key does not exists.
     * @param {Object} config_array
     *   When ommited, this.settings.config will be used. (Optional)
     * @param {Boolean} set_value_on_not_exists
     *   When set to true and the value does not exists, the set_config will also be called with the default value (Optional)
     *   
     * @returns {String}
     *   The config key or if not exists the default_val
     */
    this.get_config = function(name, default_val, config_array, set_value_on_not_exists) {
        if (config_array === undefined) {
            config_array = this.settings.config;
        }
        if (config_array === undefined) {
            if (set_value_on_not_exists === true) {
                this.set_config(name, default_val, config_array);
            }
            return default_val;
        }

        var cfg = $.extend(true, {}, config_array);
        for (var i = 0; i < name.length; i++) {
            if (cfg[name[i]] === undefined) {
                if (set_value_on_not_exists === true) {
                    this.set_config(name, default_val, config_array);
                }
                return default_val;
            }
            cfg = cfg[name[i]];
        }
        return cfg;
    };
    
    /**
     * Get the config value for the given path.
     * 
     * @param {Array} name
     *   The config key path as an array, each value represents on child of the config path.
     * @param {Mixed} value
     *   The value
     * @param {Object} config_array
     *   When ommited, this.settings.config will be used. (Optional)
     *   
     * @returns {undefined}
     */
    this.set_config = function(name, value, config_array) {
        
        if (config_array === undefined) {
            config_array = this.settings.config;
        }
        
        if (config_array === undefined) {
            config_array = {};
        }

        for (var i = 0; i < name.length; i++) {
            if (i+1 === name.length) {
                config_array[name[i]] = value;
                break;
            }
            if (config_array[name[i]] === undefined) {
                config_array[name[i]] = {};
            }
            config_array = config_array[name[i]];
        }
    };
    
    /**
     * Initialize the pager if enabled.
     * 
     * @returns {undefined}
     */
    this.init_pager = function() {
        
        // Back reference.
        var that = this;
        
        // Check if paging is enabled.
        var enable_paging = this.get_config(['enable_paging']);
        
        // If not, just return.
        if (enable_paging === undefined || enable_paging === "0") {
            return;
        }

        // Setup change event.
        var mepp = $('#pager').off('change').on('change', function() {
            
            // Get the selected value.
            var pager_mepp = $(this).val();
            
            // Save selected value into the config array.
            that.set_config(['pager_mepp'], pager_mepp);
            
            // Set the selected value also within the database.
            ajax_request(murl('main', 'set_pager_mepp'), {mepp: pager_mepp});
            
            // Refresh rigs.
            myself.refresh_device_list(1, pager_mepp);
            
            // Re-init pager with new max entries per page.
            that.init_pager();
        }).val();

        // Get the max entries per page.
        var pager_mepp = that.get_config(['pager_mepp']);
        
        // If not defined.
        if (pager_mepp === undefined) {
            pager_mepp = 1;
        }
        
        // Create the pager.
        var pager = new SoopfwPager({
            entries: that.get_rig_count(),
            max_entries_per_page: pager_mepp,
            is_ajax: true,
            uuid: "init_device_pager",
            callback: function(current_page) {
                // When we click on a page, change devices.
                myself.refresh_device_list((parseInt(current_page) + 1), mepp);
            }
        });
        
        // Build pager (Will create the clickable page entries.)
        pager.build_pager();
    };
    
    /**
     * Returns the parsed hashrate string with unit.
     * 
     * @param {Number} value
     *   The hashrate as MH.
     *   
     * @returns {String}
     *   The parsed hashrate string.
     */
    this.get_hashrate_string = function(value) {
        if (value > 1000) {
            return (Math.round(value / 10) / 100) + " Gh/s";
        }

        if (value < 1) {
            return (Math.round(value * 100000) / 100) + " Kh/s";
        }

        return (Math.round(value * 100) / 100) + " Mh/s";
    };
};

/**
 * Represents one rig.
 * 
 * @param {RigOverview} overview
 *   The back reference to the overview object.
 * @param {string} rig
 *   The rig name.
 * @param {object} rig_data
 *   The rig data.
 */
function RigDetails(overview, rig, rig_data) {

    /**
     * Back reference to the overview object.
     * 
     * @type RigOverview
     */
    var rig_overview = overview;
    
    /**
     * Holds the rigname.
     * 
     * @type String
     */
    this.rig_name = rig;
    
    /**
     * Holds a uniq identifier for this rig.
     * 
     * @type String
     */
    this.identifier = md5(rig);
    
    /**
     * Holds the rig data details.
     * 
     * @type Object
     */
    this.details = rig_data;
    
    /**
     * Holds the complete rig html as an JQuery object.
     * 
     * @type JQuery
     */
    this.html = null;
    
    /**
     * Wether the rig is collapsed or not.
     * 
     * @type Boolean
     */
    this.is_collapsed = (rig_data !== false && rig_data.collapsed !== undefined) ? rig_data.collapsed : false;
    
    /**
     * Holds a single device.
     * 
     * @type Object
     */
    this.devices = {};
    
    /**
     * Self reference for private methods.
     * 
     * @type RigDetails
     */
    var my_self = this;
    
    /**
     * Self delete.
     * Will remove all devices and own generated html markup.
     * 
     * @returns {undefined}
     */
    this.delete = function() {
        
        // Delete all devoces.
        delete this.devices;
        
        // Make sure devices is an object after delete.
        this.devices = {};        
        
        // Remove markup.
        this.html.fadeOut('fast', function() {
            $(this).remove();
        });
        
    };
    
    /**
     * Get the config value for the given path.
     * 
     * @param {Array} name
     *   The config key path as an array, each value represents on child of the config path.
     * @param {Mixed} default_val
     *   The default value which will be returned when the config key does not exists.
     * @param {Object} config_array
     *   When ommited or set to null, rig_overview.settings.config['rigs'][{current_rig}] will be used. (Optional)
     * @param {Boolean} set_value_on_not_exists
     *   When set to true and the value does not exists, the set_config will also be called with the default value (Optional)
     *   
     * @returns {String}
     *   The config key or if not exists the default_val
     */
    this.get_config = function(name, default_val, config_array, set_value_on_not_exists) {
        if (config_array === undefined || config_array === null) {
            config_array = rig_overview.settings.config['rigs'][this.rig_name];
        }
        return rig_overview.get_config(name, default_val, config_array, set_value_on_not_exists);
    };
    
    /**
     * Get the config value for the given path.
     * 
     * @param {Array} name
     *   The config key path as an array, each value represents on child of the config path.
     * @param {Mixed} value
     *   The value
     * @param {Object} config_array
     *   When ommited or set to null, rig_overview.settings.config['rigs'][{current_rig}] will be used. (Optional)
     *   
     * @returns {undefined}
     */
    this.set_config = function(name, value, config_array) {
        if (config_array === undefined || config_array === null) {
            config_array = rig_overview.settings.config['rigs'][this.rig_name];
        }
        
        // This determines that a config will be changed from average device, here we need to set it to all devices.

        if (name[0] === 'gpu_-1') {
            
            // Loop through each device.
            $.each(this.devices, function(tmp, device) {
                
                // Create a name clone.
                var new_name = $.extend([], name);
                
                // Set the first entry to the current gpu id.
                new_name[0] = 'gpu_' + device.status_info.gpu_id;
                
                // Set the config.
                rig_overview.set_config(new_name, value, config_array);
            });
            
        }
        else {
            rig_overview.set_config(name, value, config_array);
        }
    };
    
    /**
     * Add a new header action to the rig.
     * 
     * @param {String} title
     *   The action title.
     * @param {String} icon
     *   The action icon.
     * @param {Function} callback
     *   The callback which will be executed on click.
     *   
     * @returns {jQuery}
     */
    var render_header_action = function(title, icon, callback) {
        return $('<div class="rig_btn"><i class="icon-' + icon + '">' + title + '</i></div>').off('click').on('click', function() {
            callback();
        });
    };

    /**
     * Retrieve the sum of all hashrate's within this rig.
     * 
     * @return Number
     *   The hashrate.
     */
    this.get_rig_hashrate = function() {
        var sum = 0;
        $.each (this.devices, function(tmp, info) {
            
            // Only sum non avg devices.
            if (!info.is_avg_device) {
                sum += info.get_config(['mhs', 'cur']);
            }
        });
        return sum;
    };
    
    /**
     * Updates the values for the devices.
     * 
     * @param {Object} devices
     *   The new device data.
     *   
     * @returns {undefined}
     */
    this.update = function(devices) {
        
        // Back reference.
        var that = this;
        
        // Init the pools for the current active pool group.
        if (devices !== false && devices.disabled !== true) {
            //new_pool_groups
            this.pool_group_switch.trigger('new_pool_groups', [rig_overview.settings.pool_groups]);
            
            // Switch the select to the current active pool group.
            this.pool_group_switch.val(devices.active_pool_group);
            
            //this.pool_group_switch.val();
            this.pool_switch.trigger('new_pools', [devices.pools]);
                 
            // For average device we need to store all active pools, to can display them.
            var active_pools = {};
            
            // Iinit average data.
            var avg_data = {
                'Model': '',
                'ID': '',
                'Name': 'GPU',
                'pool': {
                    'Status': 'Alive',
                    'URL': ''
                },
                'is_running': true,
                'gpu_info': {
                    'Enabled': 'Y',
                    'GPU Activity': 0,
                    'Temperature': 0,
                    'MHS 5s': 0,
                    'MHS av': 0,
                    'Accepted': 0,
                    'Rejected': 0,
                    'Hardware Errors': 0,
                    'Fan Percent': 0,
                    'Fan Speed': 0,
                    'GPU Clock': 0,
                    'Memory Clock': 0,
                    'GPU Voltage': 0,
                    'Intensity': 0,
                }

            };
            // Add each device to our list.
            $.each(devices.device_list, function(tmp_index, device_data) {
                
                // Don't process any device which is avg, this will be calculated below.
                if (device_data.ID === '-1') {
                    return;
                }
                
                // Try to get the device data.
                var device = that.get_device(device_data.ID);
                
                // When we didn't added the device yet, add it.
                if (empty(device)) {
                    that.add_device(device_data);
                    
                    // Re-retrieve device after we have added it.
                    device = that.get_device(device_data.ID);
                }
                else {
                    // Else just update values.
                    device.update(device_data);
                }
                
                // The pool for this device is donating.
                if (device_data['donating'] !== undefined) {
                    if (active_pools['donating'] === undefined) {
                        active_pools['donating'] = [];
                    }
                    active_pools['donating'].push(device['Model']);
                }
                // The pool for this device is normal working.
                else if (!empty(device_data['pool'])) {
                    if (active_pools[device_data['pool']['URL']] === undefined) {
                        active_pools[device_data['pool']['URL']] = [];
                    }
                    active_pools[device_data['pool']['URL']].push(device_data['Model']);
                }
                // The pool for this device is not found.
                else {
                    if (active_pools['waiting'] === undefined) {
                        active_pools['waiting'] = [];
                    }
                    active_pools['waiting'].push(device['Model']);
                }
                
                // Sum all values for all devices to get the average value.
                avg_data['gpu_info']['GPU Activity'] += parseFloat(device_data['gpu_info']['GPU Activity']);
                avg_data['gpu_info']['Temperature'] += parseFloat(device_data['gpu_info']['Temperature']);
                avg_data['gpu_info']['MHS 5s'] += parseFloat(device_data['gpu_info']['MHS 5s']);
                avg_data['gpu_info']['MHS av'] += parseFloat(device_data['gpu_info']['MHS av']);
                avg_data['gpu_info']['Accepted'] += parseFloat(device_data['gpu_info']['Accepted']);
                avg_data['gpu_info']['Rejected'] += parseFloat(device_data['gpu_info']['Rejected']);
                avg_data['gpu_info']['Hardware Errors'] += parseFloat(device_data['gpu_info']['Hardware Errors']);
                avg_data['gpu_info']['Fan Percent'] += parseFloat(device_data['gpu_info']['Fan Percent']);
                avg_data['gpu_info']['Fan Speed'] += parseFloat(device_data['gpu_info']['Fan Speed']);
                avg_data['gpu_info']['GPU Clock'] += parseFloat(device_data['gpu_info']['GPU Clock']);
                avg_data['gpu_info']['Memory Clock'] += parseFloat(device_data['gpu_info']['Memory Clock']);
                avg_data['gpu_info']['GPU Voltage'] += parseFloat(device_data['gpu_info']['GPU Voltage']);
                avg_data['gpu_info']['Intensity'] += parseFloat(device_data['gpu_info']['Intensity']);
            });
            
            // Get the device count.
            var device_count = this.get_device_count();
            
            // We have to check if avg device is added right here because if so we have to decrease the device count by 1.
            // As avg device would be counted too.
            var avg_rig = that.get_device('-1');
           
            // Decrease device count by 1 when avg device is found.
            if (!empty(avg_rig)) {
                device_count--;
            }
            // If we have some devices add the average device too.
            if (device_count > 0) {
                
                /**
                 * Calculates the average for the given number based up on the device count and round it with the given decimals.
                 * 
                 * @param {Number} val
                 *   The number to calculate.
                 * @param {Number} decimals
                 *   How much decimals after comma. (Optional, default = 2)
                 * @returns {Number}
                 *   The rounded calculated value.
                 */
                function maxRound(val, decimals) {
                    if (decimals === undefined) {
                        decimals = 2;
                    }
                    
                    return Math.round((val / device_count) * Math.pow(10, decimals)) / Math.pow(10, decimals);
                }
                
                // Calculate the average value.
                avg_data['gpu_info']['GPU Activity'] = maxRound(avg_data['gpu_info']['GPU Activity']);
                avg_data['gpu_info']['Temperature'] = maxRound(avg_data['gpu_info']['Temperature']);
                avg_data['gpu_info']['MHS 5s'] = maxRound(avg_data['gpu_info']['MHS 5s']);
                avg_data['gpu_info']['MHS av'] = maxRound(avg_data['gpu_info']['MHS av']);
                avg_data['gpu_info']['Fan Percent'] = maxRound(avg_data['gpu_info']['Fan Percent']);
                avg_data['gpu_info']['Fan Speed'] = maxRound(avg_data['gpu_info']['Fan Speed']);
                avg_data['gpu_info']['GPU Clock'] = maxRound(avg_data['gpu_info']['GPU Clock']);
                avg_data['gpu_info']['Memory Clock'] = maxRound(avg_data['gpu_info']['Memory Clock']);
                avg_data['gpu_info']['GPU Voltage'] = maxRound(avg_data['gpu_info']['GPU Voltage'], 3);
                avg_data['gpu_info']['Intensity'] = maxRound(avg_data['gpu_info']['Intensity']);
                
                // Make sure average device has name and id set correctly.
                avg_data['Model'] = device_count + ' GPUs';
                avg_data['ID'] = '-1';
                
                // Now have to build up the pool string, because if some devices has different pools we have to display them seperate (pools, not devices).
                var active_pool_count = 0;
                
                // Init pool string.
                var active_key = '';
                
                // Now loop to all active pools found from the check above and add the first pool to the pool string.
                // If we have more than one active pool break now.
                for (var i in active_pools) {
                    if (active_pools.hasOwnProperty(i)) {
                        active_pool_count++;
                        if (active_pool_count > 1) {
                            break;
                        }
                        active_key += i;
                    }
                }

                // We now check if we had more than one pool for this rig.
                if (active_pool_count > 1) {
                    
                    // We loop through all active pools and add it like 3 Devices: Pool XY
                    var tmp_join_array = [];
                    $.each(active_pools, function(url, devices) {
                        tmp_join_array.push(devices.length + " Devices: " + url);
                    });
                    
                    // Join all data together with a new line.
                    active_key = tmp_join_array.join('<br />');
                }
                
                // Set the pool data now.
                avg_data['pool']['URL'] = active_key;
                
                // Just normal behavour, add if it is not already, else update.
                if (empty(avg_rig)) {
                    that.add_device(avg_data);
                }
                else {
                    avg_rig.update(avg_data);
                }
            }
            
            // After adding all devices, we have to enable the rig.
            enable_rig();
        }
        else {
            disable_rig(this.get_config(['disabled'], false));
        }
        
        
    };
    
    /**
     * Renders this rig with all actions and devices.
     * It will return the generated html as an jquery object.
     * 
     * @return {JQuery}
     *   The html as an jquery object.
     */
    this.render = function() {
        
        // Back reference.
        var back_reference = this;
        
        // Main rig container.
        this.html = $('<div>', {class: 'rig'});
        
        
        this.start_stop_header_action = render_header_action('Start rig', 'off', function() {
            var disabled = back_reference.get_config(['disabled'], false);
            var cgminer_is_running = back_reference.get_config(['is_running'], true);
            confirm('Do you really want to ' + ((disabled || !cgminer_is_running) ? 'start' : 'stop') + ' mining at rig "' + back_reference.rig_name + '"?', 'Stop mining / Disable rig', function() {
                ajax_request(murl('main', 'start_stop_mining'), {rig: back_reference.rig_name, stop: !(disabled || !cgminer_is_running)}, function() {
                    back_reference.set_config(['disabled'], !(disabled || !cgminer_is_running));
                    rig_overview.refresh_update_device_list();
                });
            });
        });
        
        // Add header bar.
        this.header = $('<div>', {class: 'rig_header'})
            // Ttitle + Hashrate.
            .append(
                $('<h2>')
                    .append(
                        $('<span>', {class: 'swap_rig'})
                            .append(Soopfw.create_icon((back_reference.is_collapsed) ? 'plus' : 'minus'))
                            .off('click').on('click', function() {
                                var i = $('i', this);
                                if (back_reference.is_collapsed) {
                                    i.removeClass('icon-plus')
                                            .addClass('icon-minus');
                                    
                                }
                                else {
                                    i.removeClass('icon-minus')
                                            .addClass('icon-plus');
                                }
                                back_reference.is_collapsed = !back_reference.is_collapsed;
                                ajax_request(murl('main', 'set_rig_collapse'), {rig: back_reference.rig_name, collapsed: back_reference.is_collapsed});
                                
                                // Trigger update so the devices can hide / show them.
                                $.each(back_reference.devices, function(tmp, device) {
                                    device.update();
                                });
                            })
                    )
                    .append($('<span>').html(back_reference.rig_name))
                    .append(
                        $('<span>').off('rig_hashrate_changed').on('rig_hashrate_changed', function() {
                            $(this).html(back_reference.get_rig_hashrate());
                        })
                    )
            )
    
            // Edit action.
            .append(render_header_action('Edit', 'edit', function() {
                ajax_request(murl('main', 'get_rig_data'), {rig: back_reference.rig_name}, function(rig_results) {
                    add_rig_dialog(true, rig_results);
                });
            }))
    
            // Delete action.
            .append(render_header_action('Delete', 'trash', function() {
                confirm('Do you really want to delete the rig: <b>' + back_reference.rig_name + '</b>', 'Delete rig ' + back_reference.rig_name + '?', function() {
                    ajax_request(murl('main', 'delete_rig'), {rig: back_reference.rig_name}, function() {
                        rig_overview.delete_rig(back_reference.rig_name);
                    });
                });
            }))
    
            // Reset stats action.
            .append(render_header_action('Reset stats', 'ccw', function() {
                rig_overview.reset_stats(back_reference.rig_name);
            }))
    
            // Start/Stop action.
            .append(this.start_stop_header_action)
    
            // Reboot action.
            .append(render_header_action('Reboot', 'ccw', function() {
                confirm('Do you really want to reboot the rig: <b>' + back_reference.rig_name + '</b>', 'Reboot rig ' + back_reference.rig_name + '?', function() {
                    ajax_request(murl('main', 'reboot_rig'), {rig: back_reference.rig_name}, function() {
                        success_alert('Rig rebooted');
                    });
                });
            }))
            ;
       
        // Add pool switch.
        this.pool_switcher = $('<div>').html('');
        
        // We only add the pool switch bar when we have pools to switch, this happens when no pool groups are configurated
        // Or if the user does not have the permission to change it.
        if (!empty(phpminer.settings.pool_groups)) {
            
            // Create pool switch select.
            this.pool_switch = $('<select>', {class: "pool_switch", id: this.identifier + '_current_pool_pool'}).append('<option value="">Please wait, loading pools.</option>')
                .off('new_pools').on('new_pools', function(event, pools) {
  
                    // Back reference.
                    var that = this;

                    // Clear select.
                    $(this).html("").append($('<option>', {value: ''}).html(''));
                    
                    // Add all pools.
                    $.each(pools, function(tmp, pool) {
                        // Append new option and if old value equals current added group, preselect it.
                        $(that).append($('<option>', {value: pool['url'] + '|' + pool['user']}).html(pool['url']));
                    });
                    $(this).val("");
                })
                .off('change').on('change', function() {
                    ajax_request(murl('main', 'switch_pool'), {rig: rig, pool: $(this).val()}, function() {
                        success_alert('Pool switched successfully, Within the overview, the pool will be updated after the first accepted share was send to the new pool, this can take some time.', null, null, 10000);
                    });
                    $(this).val("");
                });
            
            // Create pool group switch select.
            this.pool_group_switch = $('<select>', {id: this.identifier + '_current_pool_group'}).append('<option value="">Please wait, loading pools.</option>')
                    .off('new_pool_groups').on('new_pool_groups', function(event, groups) {
                        
                        // Back reference.
                        var that = this;
                        
                        // Save old value.
                        var old_val = $(this).val();

                        // Clear select.
                        $(that).html("");

                        // Add all groups.
                        $.each(groups, function(tmp, group) {

                            // Donate group should not be displayed.
                            if (group === 'donate') {
                                return;
                            }
                            
                            // Append new option and if old value equals current added group, preselect it.
                            $(that).append($('<option>', {value: group, selected: (old_val === group)}).html(group));
                        });
                    })

                    // On change we have to refill the pool select and change to the group
                    .off('change').on('change', function() {
                        
                        // Save old value.
                        var value = $(this).val();
                        
                        // Only do something when choosen a group, not the "please select".
                        if (!empty(value)) {
                            
                            // Display wait dialog while switching.
                            wait_dialog('<img style="margin-top: 7px;margin-bottom: 7px;" src="/templates/ajax-loader.gif"/><br>Please wait until the new pool group is activated. This takes some time because PHPMiner needs to verify that the last active pool is one of the newly added one.');
                            
                            // Try to switch to the given pool group.
                            ajax_request(murl('main', 'switch_pool_group'), {rig: back_reference.rig_name, group: value}, function(data) {
                                
                                // Anyways hide the wait dialog.
                                $.alerts._hide();
                                
                                // If we had some errors where we couldn't switch, display error.
                                if (data.errors !== undefined) {
                                    
                                    // Create main error string.
                                    var err_str = 'There are some rig\'s which produced errors. Here is a list of all errors which occured:\n';
                                    
                                    // Append all rigs which had errors.
                                    $.each(data.errors, function(k, v) {
                                        err_str += " - " + v + "\n";
                                    });
                                    
                                    // Display error.
                                    alert(err_str);
                                }
                                
                                // The rig's which had no errors.
                                if (data.success !== undefined) {
                                    
                                    // Update all rigs where no errors occured.
                                    $.each(data.success, function(k, v) {
                                        
                                        // Set the new pools within the switched grouped to the rig.
                                        rig_overview.get_rig(k).pool_switch.trigger('new_pools', [data.new_pools]);
                                    });
                                }
                            });
                        }
                    });
            
            // Create the pool switcher widget.
            this.pool_switcher
                .append(
                    $('<div>', {class: 'pool_switching_container'})
                        // Due to float right we have to configurate add they in reverse order.

                        // Pools select.
                        .append(this.pool_switch)
                
                        // Label for pools.
                        .append('<label for="' + this.identifier + '_current_pool_pool">Change mining pool:&nbsp;&nbsp;</label>')
                
                        // Pool groups select
                        .append(this.pool_group_switch)
                            
                        // Label for pool groups.
                        .append('<label for="' + this.identifier + '_current_pool_group">Change mining group:&nbsp;&nbsp;</label>')
                );
        }
        
        this.device_container = $('<tbody>');
        
        this.device_table = $('<table>', {class: 'device_list'})
            .append(
                $('<thead>')
                    .append(
                        $('<tr>')
                            .append($('<th>', {class: 'center info_enabled',    style: "width: 70px"})  .append('Enabled'))
                            .append($('<th>', {class: 'info_name'})                                     .append('Name'))
                            .append($('<th>', {class: 'right info_load',        style: "width: 70px"})  .append(Soopfw.create_icon('signal')).append('Load'))
                            .append($('<th>', {class: 'right info_temp',        style: "width: 65px"})  .append(Soopfw.create_icon('thermometer')).append('Temp'))
                            .append($('<th>', {class: 'right info_hashrate',    style: "width: 140px"}) .append(Soopfw.create_icon('chart-line')).append('Hashrate'))
                            .append($('<th>', {class: 'right info_shares',      style: "width: 145px"}) .append(Soopfw.create_icon('link-ext')).append('Shares'))
                            .append($('<th>', {class: 'right info_hw',          style: "width: 65px"})  .append(Soopfw.create_icon('attention')).append('HW'))
                            .append($('<th>', {class: 'right info_fan',         style: "width: 60px"})  .append(Soopfw.create_icon('air')).append('Fan'))
                            .append($('<th>', {class: 'right info_engine',      style: "width: 75px"})  .append(Soopfw.create_icon('clock')).append('Engine'))
                            .append($('<th>', {class: 'right info_memory',      style: "width: 83px"})  .append(Soopfw.create_icon('clock')).append('Memory'))
                            .append($('<th>', {class: 'right info_voltage',     style: "width: 80px"})  .append(Soopfw.create_icon('flash')).append('Voltage'))
                            .append($('<th>', {class: 'right info_intensity',   style: "width: 85px"})  .append(Soopfw.create_icon('fire')).append('Intensity'))
                            .append($('<th>', {class: 'right info_cp',          style: "width: 310px"}) .append(Soopfw.create_icon('group')).append('Current pool'))
                    )
            )
            .append(this.device_container);
           
        // Append all generated containers to the main rig html.
        this.html.append(this.header);
        this.html.append(this.pool_switcher);
        this.html.append(this.device_table);

        // Init devices.
        this.update(this.details);
        
        // Return the html for the rig.
        return this.html;
    };
    
    /**
     * Enable the rig.
     * 
     * @returns {undefined}
     */
    var enable_rig = function() {
        if (my_self.get_device_count() <= 0) {
            my_self.disable_text = $('<tr><td colspan="13" class="center">No devices found or rig is not alive</td></tr>');
            my_self.device_container.html('').append(my_self.disable_text); 
        }
        else if (my_self.disable_text !== undefined && my_self.disable_text !== '') {
            my_self.disable_text.remove();
            my_self.disable_text = '';
        }
        my_self.set_config(['is_running'], true);
        my_self.pool_switcher.removeClass('hidden');
        $('i', my_self.start_stop_header_action).html('Stop rig');
    };
    
    /**
     * Disable the rig.
     * 
     * @param {Boolean} disabled
     *   When set to true, it is not offline, instead it is just disabled.
     * 
     * @returns {undefined}
     */
    var disable_rig = function(disabled) {
        my_self.disable_text = $('<tr><td colspan="13" class="center">' + ((disabled) ? 'Rig is disabled' : 'No devices found or rig is not alive') + '</td></tr>');
        my_self.device_container.html(my_self.disable_text);
        my_self.pool_switcher.addClass('hidden');
        delete my_self.devices;
        my_self.devices = {};
        if (disabled) {
            $('i', my_self.start_stop_header_action).html('Start rig');
        }
        else {;
            my_self.set_config(['is_running'], false);
        }
    };
    
    /**
     * Add a new device.
     * 
     * @param {object} device_data
     *   The device data.
     * @param {Boolean} is_avg
     *   Set to true when the provided data is the average data device.
     *   
     * @returns {undefined}
     */
    this.add_device = function(device_data, is_avg) {
        // Add the device.
        this.devices['id_' + device_data.ID] = new RigDevice(rig_overview, this, device_data);

        // If we have avg device, set to to it.
        if (device_data.ID === '-1') {
            this.devices['id_' + device_data.ID].is_avg_device = true;
        }
        
        // Re-calculate.
        this.devices['id_' + device_data.ID].calculate_status();
        
        // Add rendered output.
        this.device_container.append(this.devices['id_' + device_data.ID].render());
    };
    
    /**
     * Deletes the given device.
     * 
     * @param {String} device_id
     *   The device which should be deleted.
     *   
     * @returns {undefined}
     */
    this.delete_rig = function(device_id) {
        var that = this;
        var the_device = that.get_device(device_id);
        if (the_device !== null) {
            the_device.delete();
            delete that.devices['id_' + device_id];
        }
    };
    
    /**
     * Returns the count if configured device's.
     * 
     * @returns {Number}
     *   The count.
     */
    this.get_device_count = function() {
        var count = 0;
        foreach(this.devices, function() {
            count++;
        });
        return count;
    };
    
    /**
     * Returns the given device.
     * 
     * @param {String} device_id
     *   The device to retrieve.
     *   
     * @returns {RigDevice|null}
     *   Returns the device or null if not found.
     */
    this.get_device = function(device_id) {
        if (this.devices['id_' + device_id] === undefined) {
            return null;
        }
        return this.devices['id_' + device_id];
    };
};

/**
 * Represents one device.
 * 
 * @param {RigOverview} overview
 *   The back reference to the overview object.
 * @param {RigDetail} rig
 *   The back reference to the rig object.
 * @param {Object} device_data
 *   The device data.
 */
function RigDevice(overview, rig, device_data) {
  
    /**
     * Holds the device data.
     * 
     * @type Object
     */
    var data = device_data;
    
    /**
     * Back reference to the overview object.
     * 
     * @type RigOverview
     */
    var rig_overview = overview;
    
    /**
     * Back reference to the rig detail object.
     * 
     * @type RigDetail
     */
    var rig_details = rig;
    
    /**
     * Holds the hashrate for this device.
     * 
     * @type Number
     */
    this.hashrate = 0;
   
    /**
     * When set to true, this device will be treated as avg.
     * 
     * @type Boolean
     */
    this.is_avg_device = false;
    
    /**
     * Holds the status info.
     * 
     * @type Object
     */
    this.status_info = {};
    
    /**
     * Backreference for private methods.
     * 
     * @type RigDevice
     */
    var myself = this;
    
    /**
     * Holds wether the device has errors or not.
     * 
     * @type Boolean
     */
    this.device_ok = true;
    
    /**
     * Get the config value for the given path.
     * 
     * @param {Array} name
     *   The config key path as an array, each value represents on child of the config path.
     * @param {Mixed} default_val
     *   The default value which will be returned when the config key does not exists.
     * @param {Object} config_array
     *   When ommited, this.calculate_status will be used. (Optional)
     *   
     * @returns {String}
     *   The config key or if not exists the default_val
     */
    this.get_config = function(name, default_val, config_array) {
        if (config_array === undefined) {
            config_array = this.status_info;
        }
        return rig_overview.get_config(name, default_val, config_array);
    };
    
    /**
     * Get the config value for the given path.
     * 
     * @param {Array} name
     *   The config key path as an array, each value represents on child of the config path.
     * @param {Mixed} value
     *   The value
     * @param {Object} config_array
     *   When ommited, this.calculate_status will be used. (Optional)
     *   
     * @returns {undefined}
     */
    this.set_config = function(name, value, config_array) {
        if (config_array === undefined) {
            config_array = this.status_info;
        }
        rig_overview.set_config(name, value, config_array);
    };
    
    /**
     * Generates the status info object.
     * 
     * @returns {undefined}
     */
    this.calculate_status = function() {
        this.status_info = {
            name: data['Model'],
            gpu_id: data['ID'],
            pool: data['pool'],
            donating: data['donating'],
            enabled: data['gpu_info']['Enabled'] === 'Y',
            load: parseFloat(data['gpu_info']['GPU Activity']),
            temp: parseFloat(data['gpu_info']['Temperature']),
            mhs: {
                cur: parseFloat(data['gpu_info']['MHS 5s']),
                avg: parseFloat(data['gpu_info']['MHS av'])
            },
            shares: {
                accepted: parseFloat(data['gpu_info']['Accepted']),
                rejected: parseFloat(data['gpu_info']['Rejected'])
            },
            hw: parseFloat(data['gpu_info']['Hardware Errors']),
            fan: parseFloat(data['gpu_info']['Fan Percent']),
            fan_speed: parseFloat(data['gpu_info']['Fan Speed']),
            engine: parseFloat(data['gpu_info']['GPU Clock']),
            memory: parseFloat(data['gpu_info']['Memory Clock']),
            voltage: parseFloat(data['gpu_info']['GPU Voltage']),
            intensity: parseFloat(data['gpu_info']['Intensity'])
        };
    };
    
    /**
     * Updates this device.
     * 
     * @param {Object} device_data
     *   The new device_data. If ommited, no re-caclulation is made. (Optional)
     * 
     * @returns {undefined}
     */
    this.update = function(device_data) {
        
        if (device_data !== undefined) {
            // Set the new data.
            data = device_data;
            
            // Recalculate
            this.calculate_status();
        }
        
        this.device_ok = true;
        // Tell that we have changed our values.
        $('td', this.html).trigger('value_change');
        
        if ((rig_details.is_collapsed && this.status_info.gpu_id !== '-1' && this.device_ok === true) || (!rig_details.is_collapsed && this.status_info.gpu_id === '-1')) {
            this.html.hide();
        }
        else {
            this.html.show();
        }
        
    };
    
    
    /**
     * Renders this device with all actions.
     * It will return the generated html as an jquery object.
     * 
     * @return {JQuery}
     *   The html as an jquery object.
     */
    this.render = function() {
        
        // Back reference
        var backreference = this; 
               
        // Geneate default css class strings.
        var css_normal = "nowrap center";
        var css_right = "nowrap right";
        var css_clickable = "nowrap right clickable";
        
        // Create main container.
        this.html = $('<tr>');
        
        // Generate enabled/disabled.
        this.td_enable = $('<td>', {class: 'info_enabled nowrap center clickable'})
            .off('value_change').on('value_change', function() {
                var enabled = backreference.get_config(['enabled'], true);
                $(this).html("").append(Soopfw.create_icon((enabled) ? 'check' : 'attention'));
                if (enabled) {
                    $(this)
                        .removeClass('disabled')
                        .addClass('enabled');
                }
                else {
                    backreference.device_ok = false;
                    $(this)
                        .removeClass('enabled')
                        .addClass('disabled');
                }
            })
            .off('click').on('click', function() {
                getEnableDialog();
            })
            .appendTo(this.html);
            
        // Generate model td
        this.td_model = $('<td>', {class: 'info_name nowrap'})
            .off('value_change').on('value_change', function() {
                $(this).html("").append(backreference.get_config(['name'], '').replace("Series", "").replace("AMD", "").replace("Radeon", "").trim());
            })
            .appendTo(this.html);;
            
        // Generate load td
        this.td_load = $('<td>', {class: 'info_load clickable ' + css_normal})
            .off('value_change').on('value_change', function() {

                var min_load = rig_details.get_config(['gpu_' + data['ID'], 'load', 'min'], 90, null, true);

                var load = backreference.get_config(['load'], 0);
                var load_ok = backreference.is_avg_device || (load >= min_load);
                
                $(this).html("")
                    .append(Soopfw.create_icon(load_ok ? 'check' : 'attention'))
                    .append(load + ' %');
            
                if (load_ok) {
                    $(this).removeClass('disabled');
                }
                else {
                    backreference.device_ok = false;
                    $(this).addClass('disabled');
                }
            })
            .off('click').on('click', function() {
                getLoadConfigDialog();
            })
            .appendTo(this.html);
    
        // Generate temperature td
        this.td_temp = $('<td>', {class: 'info_temp ' + css_clickable})
            .off('value_change').on('value_change', function() {
                
                var min_temp = rig_details.get_config(['gpu_' + data['ID'], 'temperature', 'min'], 50, null, true);
                var max_temp = rig_details.get_config(['gpu_' + data['ID'], 'temperature', 'max'], 85, null, true);
                var temp = backreference.get_config(['temp'], 0);
                var temp_ok = backreference.is_avg_device || (temp >= min_temp && temp <= max_temp);
                $(this).html("")
                    .append(Soopfw.create_icon(temp_ok ? 'check' : 'attention'))
                    .append(temp + ' c');
            
                if (temp_ok) {
                    $(this).removeClass('disabled');
                }
                else {
                    backreference.device_ok = false;
                    $(this).addClass('disabled');
                }
            })
            .off('click').on('click', function() {
                getTempConfigDialog();
            })
            .appendTo(this.html);
    
        // Generate hashrate td
        this.td_hashrate = $('<td>', {class: 'info_hashrate ' + css_clickable})
            .off('value_change').on('value_change', function() {
                var min_hashrate = rig_details.get_config(['gpu_' + data['ID'], 'hashrate', 'min'], 100, null, true);
                var hashrate = backreference.get_config(['mhs'], {cur: 0, avg: 0});
                var hashrate_ok = backreference.is_avg_device || (hashrate.cur * 1000 >= min_hashrate);
                $(this).html("")
                    .append(Soopfw.create_icon(hashrate_ok ? 'check' : 'attention'))
                    .append(rig_overview.get_hashrate_string(hashrate.cur))
                    .append('<span class="info_hashrate_avg">  (' + rig_overview.get_hashrate_string(hashrate.avg) + ')</span>');
                
                if (hashrate_ok) {
                    $(this).removeClass('disabled');
                }
                else {
                    backreference.device_ok = false;
                    $(this).addClass('disabled');
                }
            })
            .off('click').on('click', function() {
                getHashrateConfigDialog();
            })
            .appendTo(this.html);
    
        // Generate shares td
        this.td_shares = $('<td>', {class: 'info_shares shares' + css_right})
            .off('value_change').on('value_change', function() {
                var shares = backreference.get_config(['shares'], {accepted: 0, rejected: 0});
                $(this).html("")
                    .append(Soopfw.create_icon('check'))
                    .append(shares.accepted)
                    .append(Soopfw.create_icon('cancel'))
                    .append(shares.rejected)
                    .append(' (' + Math.round((100 / shares.accepted) * shares.rejected, 2) + '%)');
            })
            .appendTo(this.html);
          
        // Generate hardware error td
        this.td_hw = $('<td>', {class: 'info_hw ' + css_clickable})
            .off('value_change').on('value_change', function() {
                var max_hw = rig_details.get_config(['gpu_' + data['ID'], 'hw', 'max'], 5, null, true);
                var hw = backreference.get_config(['hw'], 0);
                var hw_ok = backreference.is_avg_device || (hw <= max_hw);
                $(this).html("")
                    .append(Soopfw.create_icon(hw_ok ? 'check' : 'attention'))
                    .append(hw);
            
                if (hw_ok) {
                    $(this).removeClass('disabled');
                }
                else {
                    backreference.device_ok = false;
                    $(this).addClass('disabled');
                }
            })
            .off('click').on('click', function() {
                getHWConfigDialog();
            })
            .appendTo(this.html);
    
        // Generate fan td
        this.td_fan = $('<td>', {class: 'info_fan ' + css_clickable})
            .off('value_change').on('value_change', function() {
                $(this).html("").append(backreference.get_config(['fan'], 0) + ' % (' + backreference.get_config(['fan_speed'], 0) + ' RPM)');
            })
            .off('click').on('click', function() {
                getFanChangeDialog(data['ID'], data['Model']);
            })
            .appendTo(this.html);
    
        // Generate engine td
        this.td_engine = $('<td>', {class: 'info_engine ' + css_clickable})
            .off('value_change').on('value_change', function() {
                $(this).html("").append(backreference.get_config(['engine'], 0) + ' Mhz');
            })
            .off('click').on('click', function() {
                getChangeDialog('engine');
            })
            .appendTo(this.html);
    
        // Generate memory td
        this.td_memory = $('<td>', {class: 'info_memory ' + css_clickable})
            .off('value_change').on('value_change', function() {
                $(this).html("").append(backreference.get_config(['memory'], 0) + ' Mhz');
            })
            .off('click').on('click', function() {
                getChangeDialog('memory');
            })
            .appendTo(this.html);
    
        // Generate voltage td
        this.td_voltage = $('<td>', {class: 'info_voltage ' + css_clickable})
            .off('value_change').on('value_change', function() {
                $(this).html("").append(backreference.get_config(['voltage'], 0) + ' V');
            })
            .off('click').on('click', function() {
                getVoltageChangeDialog();
            })
            .appendTo(this.html);
    
        // Generate intensity td
        this.td_intensity = $('<td>', {class: 'info_intensity ' + css_clickable})
            .off('value_change').on('value_change', function() {
                $(this).html("").append(backreference.get_config(['intensity'], 0));
            })
            .off('click').on('click', function() {
                getIntensityChangeDialog();
            })
            .appendTo(this.html);
    
        // Generate pool td
        this.td_pool = $('<td>', {class: 'info_cp ' + css_right})
        
        .off('value_change').on('value_change', function() {
            
            var donating = backreference.get_config(['donating']);
            var pool = backreference.get_config(['pool'], '');
            $(this).html("");
            if (donating !== undefined || !empty(pool)) {
                $(this)
                    .addClass((pool['Status'] === 'Alive') ? '' : 'disabled')
                    .append(Soopfw.create_icon((pool['Status'] === 'Alive') ? 'check' : 'attention'))
                    .append(donating !== undefined ? 'Donating (' + donating + ' minutes left)' : pool['URL']);
            }
            else {
                $(this).append('Waiting for pool');
            }
            
        });
        
        
        this.td_pool.appendTo(this.html);

        this.update();
        return this.html;
    };
    
    /**
     * Generates the config dialog to enable or disable a gpu.
     * 
     * @returns {undefined}
     */
    var getEnableDialog = function() {
        var message = 'Enable ';

        if (myself.status_info.enabled) {
            message = "Disable ";
        }
        var title = 'Enable/Disable ';
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = message + " ALL GPUS";
            title = 'ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            message += " GPU";
            title += 'GPU ' + myself.status_info.name + ' (' + myself.status_info.gpu_id + ')';
        }
        confirm(message, title, function() {
            ajax_request(murl('gpu', 'enable_gpu'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, value: (myself.status_info.enabled) ? 0 : 1});
        });
    };
    
    var getHashrateConfigDialog = function() {
        var val = rig_details.get_config(['gpu_' + myself.status_info.gpu_id, 'hashrate']);

        var title = 'Set min. hashrate for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title = 'ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title = '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }

        var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="min_hashrate">Min. hashrate:</label>';
        dialog += '            <div id="min_hashrate"></div> <span id="min_hashrate_new_value"></span> kh/s';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, null, {
            width: 660,
            show: function() {
                $('#min_hashrate').noUiSlider({
                    range: [0, 1500],
                    start: val['min'],
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
                    ajax_request(murl('gpu', 'set_hashrate_config'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, min: $(this).val()}, function() {
                        $.alerts._hide();
                        rig_details.set_config(['gpu_' + myself.status_info.gpu_id, 'hashrate', 'min'], $('#min_hashrate').val());
                        myself.update();
                    });
                });
            }
        });
    };
    
    var getLoadConfigDialog = function() {
        var val = rig_details.get_config(['gpu_' + myself.status_info.gpu_id, 'load']);
       
        var title = 'Set min. load for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }
        
        var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="min_load">Min. load:</label>';
        dialog += '            <div id="min_load"></div> % <span id="min_load_new_value"></span>';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, null, {
            width: 660,
            show: function() {
                $('#min_load').noUiSlider({
                    range: [0, 100],
                    start: val['min'],
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
                    ajax_request(murl('gpu', 'set_load_config'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, min: $(this).val()}, function() {
                        $.alerts._hide();
                        rig_details.set_config(['gpu_' + myself.status_info.gpu_id, 'load', 'min'], $('#min_load').val());
                        myself.update();
                    });
                });


            }
        });
    };
    
    var getHWConfigDialog = function() {

        var val = rig_details.get_config(['gpu_' + myself.status_info.gpu_id, 'hw']);

        var title = 'Set max. hardware errors for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }

        var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="max_hw">Max. Hardware errors:</label>';
        dialog += '            <input type="text" value="' + val['max'] + '" id="max_hw"></input>';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, [
            {
                title: 'Save',
                type: 'primary',
                id: 'main_init_set_hw_error',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    wait_dialog();
                    ajax_request(murl('gpu', 'set_hw_config'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, max: $('#max_hw').val()}, function() {
                        $.alerts._hide();
                        $('.modal').modal('hide');
                        $('#main_init_set_hw_error').button('reset');
                        rig_details.set_config(['gpu_' + myself.status_info.gpu_id, 'hw', 'max'], $('#max_hw').val());
                        myself.update();
                    }, function() {
                        $('#main_init_set_hw_error').button('reset');
                    });
                }
            }
        ], {
            width: 660
        });
    };
    
    var getTempConfigDialog = function() {

        var val = rig_details.get_config(['gpu_' + myself.status_info.gpu_id, 'temperature'], {
            min: 50,
            max: 85
        });
        
        var title = 'Set temperature for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }

        var dialog = "<div>This setting is for minitoring, it is not used to overclock something automatically</div>";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
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

        make_modal_dialog(title, dialog, null, {
            width: 660,
            show: function() {
                $('#min_temp').noUiSlider({
                    range: [0, 100],
                    start: val.min,
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
                    ajax_request(murl('gpu', 'set_temp_config'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, min: $(this).val(), max: $('#max_temp').val()}, function() {
                        $.alerts._hide();
                        rig_details.set_config(['gpu_' + myself.status_info.gpu_id, 'temperature', 'min'], $('#min_temp').val());
                        myself.update();
                    });
                });

                $('#max_temp').noUiSlider({
                    range: [0, 100],
                    start: val.max,
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
                    ajax_request(murl('gpu', 'set_temp_config'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, min: $('#min_temp').val(), max: $(this).val()}, function() {
                        $.alerts._hide();
                        rig_details.set_config(['gpu_' + myself.status_info.gpu_id, 'temperature', 'max'], $('#max_temp').val());
                        myself.update();
                    });
                });

            }
        });
    };
    
    function change_btn(hide) {
        if (hide === undefined) {
            $('#save_cg_miner_config_container').fadeIn('slow');
        }
        else {
            $('#save_cg_miner_config_container').fadeOut('slow');
        }
    }
    
    var getFanChangeDialog = function() {
        
        var title = 'Set fanspeed for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }
        
        var dialog = "";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="value">Fanspeed:</label>';
        dialog += '            <div id="sample-update-slider"></div> % <span id="new_value"></span>';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, null, {
            width: 660,
            show: function() {

                $('#sample-update-slider').noUiSlider({
                    range: [0, 100],
                    start: myself.get_config(['fan']),
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
                    ajax_request(murl('gpu', 'set_fan_speed'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, speed: $(this).val()}, function() {
                        $.alerts._hide();
                        change_btn();
                        
                    });
                });

            }
        });
    };
    
    var getVoltageChangeDialog = function() {
        
        var title = 'Set voltage for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }
        
        var dialog = "When using the text field to enter the value, please click outside when you are finish so the 'change' event can be fired";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="value">Voltage:</label>';
        dialog += '            <div id="sample-update-slider"></div> <input type="text" id="new_value" style="width:60px" /> V';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, null, {
            width: 860,
            show: function() {

                $('#sample-update-slider').noUiSlider({
                    range: [0.8, 1.3],
                    start: myself.get_config(['voltage']),
                    handles: 1,
                    margin: 2,
                    step: 0.001,
                    serialization: {
                        to: [$('#new_value'), 'val'],
                        resolution: 0.001
                    }
                }).change(function() {
                    wait_dialog();
                    ajax_request(murl('gpu', 'set_voltage'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, value: $(this).val()}, function() {
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
    };
    
    var getIntensityChangeDialog = function() {
        var title = 'Set intensity for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }
        
        var dialog = "";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="value">Intensity:</label>';
        dialog += '            <div id="sample-update-slider"></div> <span id="new_value"></span>';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, null, {
            width: 660,
            show: function() {

                $('#sample-update-slider').noUiSlider({
                    range: [8, 20],
                    start: myself.get_config(['intensity']),
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
                    ajax_request(murl('gpu', 'set_intensity'), {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, value: $(this).val()}, function() {
                        $.alerts._hide();
                        change_btn();
                    });
                });

            }
        });
    };
    
    var getChangeDialog = function(type) {
        
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
        
        title += ' for ';
        var message = "";
        if (rig_details.is_collapsed) {
            myself.status_info.gpu_id = -1;
            message = "YOU ARE IN COLLAPSED MODE, YOU WILL CHANGE ALL GPUS IN THIS RIG";
            title += ' ALL GPUS ON RIG ' + rig_details.rig_name;
        }
        else {
            title += '<b>' + myself.status_info.name + '</b> (GPU: <b>' + myself.status_info.gpu_id + '</b>)';
        }

        var dialog = "";
        if (!empty(message)) {
            dialog += '    <div>' + message + '</div>';
        }
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element">';
        dialog += '            <label for="value">' + label + ':</label>';
        dialog += '            <input type="text" value="' + myself.get_config([type]) + '" id="value"></input> <span class="unit">' + unit + '</span>';
        dialog += '        </div>';
        dialog += '    </div>';

        make_modal_dialog(title, dialog, [
            {
                title: 'Save',
                type: 'primary',
                id: 'main_init_set_value',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    wait_dialog();
                    ajax_request(url, {rig: rig_details.rig_name, gpu: myself.status_info.gpu_id, value: $('#value').val()}, function() {
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
    };
};

Soopfw.behaviors.main_init = function() {
    var overview = new RigOverview();
    overview.init();
    
    $('#add_rig').off('click').on('click', function() {
        add_rig_dialog(true);
    });
    
    $('#reset_all_rig_stats').off('click').on('click', function() {
        confirm('Do you really want to reset stats for all rigs?', 'Resets stats', function() {
            overview.reset_stats();
        });
    });
    
    var getDonationChangeDialog = function() {

        var dialog = "";
        var message = '<br><b>So what is auto-donation?</b><br>PHPMiner will detect when your workers have mined 24 hours, then PHPMiner will switch to donation pools where your workers will mine for me for <span class="donation_new_value"></span> Minutes. After this time PHPMiner will switch back to your previous pool group.<br><span class="donation_new_value"></span> Minutes within 24 Hours are just <span class="donation_new_value_percent"></span> % of the hole mining time. It\'s just a little help to let me know that you want updates in the future and this tells me that my work with PHPMiner was useful.';
        dialog += '    <div>';
        dialog += '    You have used PHPMiner now for about a week or more.<br><br>';
        dialog += '    If you like it and if it helped you a bit better to monitor and configure your mining rigs, is it worth to support the work behind?<br><br>';
        dialog += '    I decided to implement an auto donation system which you can disable at any time. By default it is disabled.<br>';
        dialog += '    </div>';
        dialog += '    <div>' + message + '</div>';
        dialog += '    <div class="simpleform">';
        dialog += '        <div class="form-element" style="white-space: nowrap;">';
        dialog += '            <label for="value">Donate minutes per day:</label>';
        dialog += '            <div id="donation" style="width: 60%"></div> <span class="donation_new_value"></span> Minutes (<span class="donation_new_value_percent"></span> % of day)<input name="donation" id="donation_val" type="hidden" value="15">';
        dialog += '        </div>';
        dialog += '    </div>';

        var pre_val = 15;
        make_modal_dialog("Support PHPMiner", dialog, [
            {
                title: 'Don\'t support PHPMiner',
                type: 'danger',
                id: 'not_support_phpminer',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    wait_dialog();
                    ajax_request(murl('main', 'set_donate'), {donation: 0}, function() {
                        $.alerts._hide();
                        $('.modal').modal('hide');
                        $('#not_support_phpminer').button('reset');
                        alert('Ok, maybe I developed not good enough, however if you change your mind you can enable it under main settings.');
                    }, function() {
                        $('#not_support_phpminer').button('reset');
                    });
                }
            },
            {
                title: 'Support PHPMiner',
                type: 'success',
                id: 'support_phpminer',
                data: {
                    "loading-text": 'Saving...'
                },
                click: function() {
                    wait_dialog();
                    ajax_request(murl('main', 'set_donate'), {donation: $('#donation_val').val()}, function() {
                        $.alerts._hide();
                        $('.modal').modal('hide');
                        $('#support_phpminer').button('reset');
                        success_alert('Thank you... I like you :)');
                    }, function() {
                        $('#support_phpminer').button('reset');
                    });
                }
            }
        ], {
            width: 860,
            show: function() {

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

            },
            cancelable: false
        });
    };
    if (phpminer.settings['display_support'] !== undefined) {
        getDonationChangeDialog();
    }
    
};