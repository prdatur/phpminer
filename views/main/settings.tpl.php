
<table class="layout_table">
    <tr>
        <?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_MAIN_SETTINGS)): ?>
        <?php $conf = $this->get_variable('config'); ?>
        <td style="width:50%">
            <h2>System settings</h2>
            <table class="config_table" id="system_settings">
                <thead>
                    <tr>
                        <th style="width:300px;">Variable</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="key" style="padding: 5px;vertical-align: top;">Support PHPMiner:</td>
                        <td class="value" style="padding: 5px;">
                            To support further updates and help to improve PHPMiner, I decided to implement an auto donation system which you can disable at any time. By default it is disabled.<br><b>So what is auto-donation?</b><br>PHPMiner will detect when your workers have mined 24 hours, then PHPMiner will switch to donation pools where your workers will mine for me for <span class="donation_new_value"></span> Minutes. After this time PHPMiner will switch back to your previous pool group.<br><span class="donation_new_value"></span> Minutes within 24 Hours are just <span class="donation_new_value_percent"></span> % of the whole mining time. It's just a little help to let me know that you want updates in the future and this tells me that my work with PHPMiner was useful.
                            <br>
                            <div id="donation"></div> <span class="donation_new_value"></span> Minutes (<span class="donation_new_value_percent"></span> % of day)<input name="donation" id="donation_val" type="hidden" value="<?php echo (!isset($conf['donation']) ? '0' : $conf['donation']);?>"></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="ajax_refresh_intervall">Ajax refresh intervall (ms):</label></td>
                        <td class="value"><input type="text" id="ajax_refresh_intervall" name="ajax_refresh_intervall" value="<?php echo (!empty($conf['ajax_refresh_intervall']) ? $conf['ajax_refresh_intervall'] : 5000);?>" /></td>
                    </tr>
                    <tr>
                        <td class="key">Allow offline pools:<i data-toggle="tooltip" title="Enable this option to allow adding pools which are offline or credentials are invalid." class="icon-help-circled"></i></td>
                        <td class="value"><div class="slider"><input type="checkbox" id="allow_offline_pools" name="allow_offline_pools" value="1" <?php echo (isset($conf['allow_offline_pools']) && $conf['allow_offline_pools'] == "1" ? 'checked="checked"' : '');?> /><label for="allow_offline_pools"></label></div></td>
                    </tr>
                    <tr>
                        <td class="key">Enable access control:<i data-toggle="tooltip" title="Enable this option will allow you to create access groups with different permissions." class="icon-help-circled"></i></td>
                        <td class="value"><div class="slider"><input type="checkbox" id="enable_access_control" name="enable_access_control" value="1" <?php echo (isset($conf['enable_access_control']) && $conf['enable_access_control'] == "1" ? 'checked="checked"' : '');?> /><label for="enable_access_control"></label></div></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="socket_timout">Rig socket timeout:</label><i data-toggle="tooltip" title="People who know what this means may change it to the needs, other people just don't change this setting" class="icon-help-circled"></i></td>
                        <td class="value"><input type="text" id="socket_timout" name="socket_timout" value="<?php echo (!empty($conf['socket_timout']) ? $conf['socket_timout'] : 5);?>" /></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="overview_sort_mode">Overview rig sortmode:</label><i data-toggle="tooltip" title="Set the sort mode for the rig overview page." class="icon-help-circled"></i></td>
                        <td class="value">
                            <select id="overview_sort_mode" name="overview_sort_mode">
                                <option value='configured'<?php echo (!isset($conf['overview_sort_mode']) || $conf['overview_sort_mode'] == "configured" ? 'selected="selected"' : '');?>>As configurated</option>
                                <option value='name'<?php echo (isset($conf['overview_sort_mode']) && $conf['overview_sort_mode'] == "name" ? 'selected="selected"' : '');?>>By name</option>
                                <option value='error'<?php echo (isset($conf['overview_sort_mode']) && $conf['overview_sort_mode'] == "error" ? 'selected="selected"' : '');?>>Rig's with error's first</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="key"><label for="enable_paging">Enable rig paging:</label><i data-toggle="tooltip" title="When you have many rig's it will be much cleaner and more has more performance to enable paging within the overview." class="icon-help-circled"></i></td>
                        <td class="value"><div class="slider"><input type="checkbox" id="enable_paging" name="enable_paging" value="1" <?php echo (isset($conf['enable_paging']) && $conf['enable_paging'] == "1" ? 'checked="checked"' : '');?> /><label for="enable_paging"></label></div></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td><td style="border-left:0px;"><div class="btn btn-primary" id="save_config">Save config</div></td>
                    </tr>
                </tfoot>
            </table>
        </td>
        <?php endif; ?>
        <?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_MINER_SETTINGS)): ?>
        <?php $rigs = $this->get_variable('rigs'); ?>
        <td style="width:50%">
            <h2>CGMiner/SGMiner config per rig</h2>
            <div class="tabs">
            <?php if (empty($rigs)): ?>
                No rigs are configurated, please configurate at least one rig.
            <?php else: ?>
                <?php foreach(array_keys($rigs) AS $rig): ?>
                    <?php $rig_id = md5($rig); ?>
                    <div class="rig_data" data-tab="<?php echo $rig_id; ?>" data-tab_title="<?php echo $rig; ?>">
                        <table class="config_table cgminer_settings" data-rig="<?php echo $rig; ?>">
                            <thead>
                                <tr>
                                    <th style="width:300px;">Variable</th>
                                    <th>Value</th>
                                    <th style="width:90px;text-align: center;">Options</th>
                                </tr>
                            </thead>
                            <tbody class="cgminer_settings_container" data-rig="<?php echo $rig; ?>">

                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="border-left:0px;"><select class="add_config_key" data-rig="<?php echo $rig; ?>"><option value="">Select config key which you want to add.</option></select></td>
                                </tr>
                                <tr>
                                    <td></td><td colspan="2" style="border-left:0px;"><div class="btn btn-primary save_cgminer_config">Save CGMiner/SGMiner config</div></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
            </div>
        </td>
        <?php endif; ?>
    </tr>
</table>