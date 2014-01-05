<?php $conf = $this->get_variable('config'); ?>
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
            <td class="key"><label for="cgminer_config_path">CGMiner config path:</label></td>
            <td class="value"><input type="text" id="cgminer_config_path" name="cgminer_config_path" value="<?php echo (!empty($conf['cgminer_config_path']) ? $conf['cgminer_config_path'] : '/etc/cgminer/cgminer.conf');?>" /></td>
        </tr>
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
<h2>CGMiner config</h2>
<table class="config_table" id="cgminer_settings">
    <thead>
        <tr>
            <th style="width:300px;">Variable</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($conf['cgminer_conf'])): ?>
            <?php foreach ($conf['cgminer_conf'] AS $key => $val): ?>
            <?php if ($key === 'pools') { continue; } ?>
            <tr>
                <td class="key"><label for="<?php echo $key; ?>"><?php echo $key; ?></label></td>
                <td class="value"><input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo $val; ?>" /></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
        <tr>
            <td colspan="2">No cgminer config available, please configurate the <b>CGMiner config path</b> first.</td></td>
        </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td></td><td style="border-left:0px;"><div class="btn btn-primary" id="save_cgminer_config">Save cgminer config</div></td>
        </tr>
    </tfoot>
</table>