<?php $conf = $this->get_variable('config'); ?>
<table class="layout_table">
    <tr>
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
                        <td class="key"><label for="ajax_refresh_intervall">Ajax refresh intervall (ms):</label></td>
                        <td class="value"><input type="text" id="ajax_refresh_intervall" name="ajax_refresh_intervall" value="<?php echo (!empty($conf['ajax_refresh_intervall']) ? $conf['ajax_refresh_intervall'] : 5000);?>" /></td>
                    </tr>
                    <tr>
                        <td class="key">Enable donation:<i data-toggle="tooltip" title="To support further updates and help to improve PHPMiner, I decided to implement an auto donation system which you can disable at any time. So what is auto-donation? PHPMiner will detect when your workers have mined 24 hours, then PHPMiner will switch to donation pools where your workers will mine for me for 15 Minuntes. After this time PHPMiner will switch back to your previous pool group. 15 Minutes within 24 Hours are just 1% if the hole mining time. So this will not have a real effect of your profit. It's just a little help to let me know that you want updates in the future and this tells me that my work with PHPMiner was useful." class="icon-help-circled"></i></td>
                        <td class="value"><div class="slider"><input type="checkbox" id="enable_donation" name="enable_donation" value="1" <?php echo (!isset($conf['enable_donation']) || $conf['enable_donation'] == "1" ? 'checked="checked"' : '');?> /><label for="enable_donation"></label></div></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td><td style="border-left:0px;"><div class="btn btn-primary" id="save_config">Save config</div></td>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width:50%">
            <h2>CGMiner config per rig</h2>
            <div class="tabs">
            <?php if (empty($conf['rigs'])): ?>
                No rigs are configurated, please configurate at least one rig.
            <?php else: ?>
                <?php foreach(array_keys($conf['rigs']) AS $rig): ?>
                    <?php $rig_conf = array(); if (isset($conf[$rig])) { $rig_conf = $conf[$rig]; } ?>
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
                                    <td></td><td colspan="2" style="border-left:0px;"><div class="btn btn-primary save_cgminer_config">Save cgminer config</div></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
            </div>
        </td>
    </tr>
</table>