<?php $conf = $this->get_variable('config'); ?>
<h2>Settings</h2>
<table class="config_table" id="system_settings">
    <thead>
        <tr>
            <th style="width:300px;">Variable</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="key">Enable RapidPush notifications:<i data-toggle="tooltip" title="RapidPush is an Android push notification service, if you enable it and provide a valid api-key you will recieve rig problems instantly on your Android device." class="icon-help-circled"></i></td>
            <td class="value"><div class="slider"><input type="checkbox" id="enable_rapidpush" name="enable_rapidpush" value="1" <?php echo (isset($conf['enable_rapidpush']) && $conf['enable_rapidpush'] == "1" ? 'checked="checked"' : '');?> /><label for="enable_rapidpush"></label></div></td>
        </tr>
        <tr>
            <td class="key"><label for="rapidpush_apikey">RapidPush API-Key:</label><i data-toggle="tooltip" title="For RapidPush you need a so called api-key which you get under http://rapidpush.com" class="icon-help-circled"></i></td>
            <td class="value"><input type="text" id="rapidpush_apikey" name="rapidpush_apikey" value="<?php echo (!empty($conf['rapidpush_apikey']) ? $conf['rapidpush_apikey'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key">Enable E-Mail notifications:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="enable_email" name="enable_email" value="1" <?php echo (isset($conf['enable_email']) && $conf['enable_email'] == "1" ? 'checked="checked"' : '');?> /><label for="enable_email"></label></div></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_email">Email address to which the notification will be send:</label></td>
            <td class="value"><input type="text" id="notify_email" name="notify_email" value="<?php echo (!empty($conf['notify_email']) ? $conf['notify_email'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_email_smtp_server">SMTP Server to use for sending emails:</label></td>
            <td class="value"><input type="text" id="notify_email_smtp_server" name="notify_email_smtp_server" value="<?php echo (!empty($conf['notify_email_smtp_server']) ? $conf['notify_email_smtp_server'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_email_smtp_port">SMTP Server port:</label></td>
            <td class="value"><input type="text" id="notify_email_smtp_port" name="notify_email_smtp_port" value="<?php echo (!empty($conf['notify_email_smtp_port']) ? $conf['notify_email_smtp_port'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_email_smtp_security">SMTP Security:</label></td>
            <td class="value">
                <select id="notify_email_smtp_security" name="notify_email_smtp_security">
                    <option value='none'<?php echo (!isset($conf['notify_email_smtp_security']) || $conf['notify_email_smtp_security'] == "none" ? 'selected="selected"' : '');?>>None</option>
                    <option value='tls'<?php echo (isset($conf['notify_email_smtp_security']) && $conf['notify_email_smtp_security'] == "tls" ? 'selected="selected"' : '');?>>TLS/SSL</option>
                </select></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_email_smtp_user">SMTP User to authenticate with SMTP Server:</label></td>
            <td class="value"><input type="text" id="notify_email_smtp_user" name="notify_email_smtp_user" value="<?php echo (!empty($conf['notify_email_smtp_user']) ? $conf['notify_email_smtp_user'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_email_smtp_pass">SMTP Password:</label></td>
            <td class="value"><input type="text" id="notify_email_smtp_pass" name="notify_email_smtp_pass" value="<?php echo (!empty($conf['notify_email_smtp_pass']) ? $conf['notify_email_smtp_pass'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key">Enable Custom HTTP-Post notifications:<i data-toggle="tooltip" title="If you enable this, a http-post request will be send to the below configurated url. The payload is 'type' for the notification type (temp_min, temp_max, hashrate, load, cgminer_restart, reboot) and 'msg' which holds the message." class="icon-help-circled"></i></td>
            <td class="value"><div class="slider"><input type="checkbox" id="enable_post" name="enable_post" value="1" <?php echo (isset($conf['enable_post']) && $conf['enable_post'] == "1" ? 'checked="checked"' : '');?> /><label for="enable_post"></label></div></td>
        </tr>
        <tr>
            <td class="key"><label for="notify_url">HTTP-POST Url:</label></td>
            <td class="value"><input type="text" id="notify_url" name="notify_url" value="<?php echo (!empty($conf['notify_url']) ? $conf['notify_url'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key">Reboot on defunc processes (DEAD GPU's):</td>
            <td class="value"><div class="slider"><input type="checkbox" id="reboot_defunc" name="reboot_defunc" value="1" <?php echo (isset($conf['reboot_defunc']) && $conf['reboot_defunc'] == "1" ? 'checked="checked"' : '');?> /><label for="reboot_defunc"></label></div></td>
        </tr>
        <tr>
            <td class="key">Restart CGMiner if not running:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="restart_cgminer" name="restart_cgminer" value="1" <?php echo (isset($conf['restart_cgminer']) && $conf['restart_cgminer'] == "1" ? 'checked="checked"' : '');?> /><label for="restart_cgminer"></label></div></td>
        </tr>
        <tr>
            <td class="key"><label for="cgminer_path">CGMiner path:</label></td>
            <td class="value"><input type="text" id="cgminer_path" name="cgminer_path" value="<?php echo (!empty($conf['cgminer_path']) ? $conf['cgminer_path'] : '');?>" /></td>
        </tr>
        <tr>
            <td class="key"><label for="cgminer_amd_sdk_path">CGMiner AMD SDK Path:</label></td>
            <td class="value"><input type="text" id="cgminer_amd_sdk_path" name="cgminer_amd_sdk_path" value="<?php echo (!empty($conf['cgminer_amd_sdk_path']) ? $conf['cgminer_amd_sdk_path'] : '');?>" /></td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td></td><td style="border-left:0px;"><div class="btn btn-primary" id="save_config">Save config</div></td>
        </tr>
    </tfoot>
</table>

<h2>Notifications</h2>
<table class="config_table" id="notification_settings">
    <thead>
        <tr>
            <th style="width:300px;">Variable</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="key"><label for="notification_delay">Notification delay (minutes):</label><i data-toggle="tooltip" title="The error needs to be exist as long as the here configurated minutes before notifications will be send out." class="icon-help-circled"></i></td>
            <td class="value"><input type="text" id="notification_delay" name="notification_delay" class="slider_toggle" data-min="1" data-max="60" data-steps="1" value="<?php echo (!empty($conf['notification_delay']) ? $conf['notification_delay'] : 1);?>" /></td>
        </tr>
        <tr>
            <td class="key"><label for="notification_resend_delay">Re-send notifications after (minutes):</label><i data-toggle="tooltip" title="When a notification is send, the same notification will only be send again after the here configurated minutes if the error still exist." class="icon-help-circled"></i></td>
            <td class="value"><input type="text" id="notification_delay" name="notification_resend_delay" class="slider_toggle" data-min="1" data-max="60" data-steps="1" value="<?php echo (!empty($conf['notification_resend_delay']) ? $conf['notification_resend_delay'] : 15);?>" /></td>
        </tr>
        <tr>
            <td class="key">Notify if GPU is too cold:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="notify_gpu_min" name="notify_gpu_min" value="1" <?php echo (isset($conf['notify_gpu_min']) && $conf['notify_gpu_min'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_gpu_min"></label></div></td>
        </tr>
        <tr>
            <td class="key">Notify if GPU is too hot:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="notify_gpu_max" name="notify_gpu_max" value="1" <?php echo (isset($conf['notify_gpu_max']) && $conf['notify_gpu_max'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_gpu_max"></label></div></td>
        </tr>
        <tr>
            <td class="key">Notify if hashrate is too low:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="notify_hashrate" name="notify_hashrate" value="1" <?php echo (isset($conf['notify_hashrate']) && $conf['notify_hashrate'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_hashrate"></label></div></td>
        </tr>
        <tr>
            <td class="key">Notify if GPU load is too low:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="notify_load" name="notify_load" value="1" <?php echo (isset($conf['notify_load']) && $conf['notify_load'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_load"></label></div></td>
        </tr>
        <tr>
            <td class="key">Notify on auto reboot:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="notify_reboot" name="notify_reboot" value="1" <?php echo (isset($conf['notify_reboot']) && $conf['notify_reboot'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_reboot"></label></div></td>
        </tr>
        <tr>
            <td class="key">Notify on cgminer restart:</td>
            <td class="value"><div class="slider"><input type="checkbox" id="notify_cgminer_restart" name="notify_cgminer_restart" value="1" <?php echo (isset($conf['notify_cgminer_restart']) && $conf['notify_cgminer_restart'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_cgminer_restart"></label></div></td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td></td><td style="border-left:0px;"><div class="btn btn-primary" id="save_config_notify">Save config</div></td>
        </tr>
    </tfoot>
</table>