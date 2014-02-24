<?php $conf = $this->get_variable('config'); ?>
<table class="layout_table">
    <tr>
        <td style="width:50%">
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
                        <td class="key">Enable Push.co notifications:<i data-toggle="tooltip" title="Push.co is an iOS push notification service, if you enable it and provide valid API details you will recieve rig problems instantly on your iOS device." class="icon-help-circled"></i></td>
                        <td class="value"><div class="slider"><input type="checkbox" id="pushco_enable" name="pushco_enable" value="1" <?php echo (isset($conf['pushco_enable']) && $conf['pushco_enable'] == "1" ? 'checked="checked"' : '');?> /><label for="pushco_enable"></label></div></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="pushco_api_key">Push.co API-Key:</label><i data-toggle="tooltip" title="Get your Developer Keys details at http://push.co/apps under Developer Tab" class="icon-help-circled"></i></td>
                        <td class="value"><input type="text" id="pushco_api_key" name="pushco_api_key" value="<?php echo (!empty($conf['pushco_api_key']) ? $conf['pushco_api_key'] : '');?>" /></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="pushco_api_secret">Push.co API-Secret:</label><i data-toggle="tooltip" title="Get your Developer Keys details at http://push.co/apps under Developer Tab" class="icon-help-circled"></i></td>
                        <td class="value"><input type="text" id="pushco_api_secret" name="pushco_api_secret" value="<?php echo (!empty($conf['pushco_api_secret']) ? $conf['pushco_api_secret'] : '');?>" /></td>
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
                        <td class="key"><label for="notify_email_smtp_from">SMTP From address:</label></td>
                        <td class="value"><input type="text" id="notify_email_smtp_from" name="notify_email_smtp_from" value="<?php echo (!empty($conf['notify_email_smtp_from']) ? $conf['notify_email_smtp_from'] : '');?>" /></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="notify_email_smtp_from_name">SMTP From address name:</label></td>
                        <td class="value"><input type="text" id="notify_email_smtp_from_name" name="notify_email_smtp_from_name" value="<?php echo (!empty($conf['notify_email_smtp_from_name']) ? $conf['notify_email_smtp_from_name'] : '');?>" /></td>
                    </tr>
                    <tr>
                        <td class="key"><label for="notify_email_smtp_security">SMTP Security:</label></td>
                        <td class="value">
                            <select id="notify_email_smtp_security" name="notify_email_smtp_security">
                                <option value='none'<?php echo (!isset($conf['notify_email_smtp_security']) || $conf['notify_email_smtp_security'] == "none" ? 'selected="selected"' : '');?>>None</option>
                                <option value='tls'<?php echo (isset($conf['notify_email_smtp_security']) && $conf['notify_email_smtp_security'] == "tls" ? 'selected="selected"' : '');?>>TLS/SSL</option>
                            </select>
                        </td>
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
                </tbody>
                <tfoot>
                    <tr>
                        <td></td><td style="border-left:0px;"><div class="btn btn-primary" id="save_config">Save config</div></td>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width:50%">
            <h2>Notifications per rig</h2>
            
            <div class="tabs">
            <?php if ($this->variable_is_empty('rigs')): ?>
                No rigs are configurated, please configurate at least one rig.
            <?php else: ?>
            <?php foreach($this->get_variable('rigs') AS $rig): ?>
                <?php $rig_conf = array(); if (isset($conf['rigs']) && isset($conf['rigs'][$rig])) { $rig_conf = $conf['rigs'][$rig]; } ?>
                <?php $rig_id = md5($rig); ?>
                <div class="rig_data" data-tab="<?php echo $rig_id; ?>" data-tab_title="<?php echo $rig; ?>">
                    <table class="config_table notification_settings" data-rig="<?php echo $rig; ?>">
                        <thead>
                            <tr>
                                <th style="width:300px;">Variable</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="key"><label for="notification_delay_<?php echo $rig_id; ?>">Notification delay (minutes):</label><i data-toggle="tooltip" title="The error needs to be exist as long as the here configurated minutes before notifications will be send out." class="icon-help-circled"></i></td>
                                <td class="value"><input type="text" id="notification_delay_<?php echo $rig_id; ?>" name="notification_delay" class="slider_toggle" data-min="1" data-max="60" data-steps="1" value="<?php echo (!empty($rig_conf['notification_delay']) ? $rig_conf['notification_delay'] : 1);?>" /></td>
                            </tr>
                            <tr>
                                <td class="key"><label for="notification_resend_delay_<?php echo $rig_id; ?>">Re-send notifications after (minutes):</label><i data-toggle="tooltip" title="When a notification is send, the same notification will only be send again after the here configurated minutes if the error still exist." class="icon-help-circled"></i></td>
                                <td class="value"><input type="text" id="notification_delay_<?php echo $rig_id; ?>" name="notification_resend_delay" class="slider_toggle" data-min="1" data-max="60" data-steps="1" value="<?php echo (!empty($rig_conf['notification_resend_delay']) ? $rig_conf['notification_resend_delay'] : 15);?>" /></td>
                            </tr>
                            <tr>
                                <td class="key">Notify if GPU is too cold:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_gpu_min_<?php echo $rig_id; ?>" name="notify_gpu_min" value="1" <?php echo (isset($rig_conf['notify_gpu_min']) && $rig_conf['notify_gpu_min'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_gpu_min_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Notify if GPU is too hot:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_gpu_max_<?php echo $rig_id; ?>" name="notify_gpu_max" value="1" <?php echo (isset($rig_conf['notify_gpu_max']) && $rig_conf['notify_gpu_max'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_gpu_max_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Notify if hashrate is too low:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_hashrate_<?php echo $rig_id; ?>" name="notify_hashrate" value="1" <?php echo (isset($rig_conf['notify_hashrate']) && $rig_conf['notify_hashrate'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_hashrate_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Notify if GPU load is too low:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_load_<?php echo $rig_id; ?>" name="notify_load" value="1" <?php echo (isset($rig_conf['notify_load']) && $rig_conf['notify_load'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_load_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Notify if too many hardware errors occure:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_hw_<?php echo $rig_id; ?>" name="notify_hw" value="1" <?php echo (isset($rig_conf['notify_hw']) && $rig_conf['notify_hw'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_hw_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Notify on auto reboot:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_reboot_<?php echo $rig_id; ?>" name="notify_reboot" value="1" <?php echo (isset($rig_conf['notify_reboot']) && $rig_conf['notify_reboot'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_reboot_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Notify on CGMiner/SGMiner restart:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="notify_cgminer_restart_<?php echo $rig_id; ?>" name="notify_cgminer_restart" value="1" <?php echo (isset($rig_conf['notify_cgminer_restart']) && $rig_conf['notify_cgminer_restart'] == "1" ? 'checked="checked"' : '');?> /><label for="notify_cgminer_restart_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key">Reboot on defunc processes (DEAD GPU's):</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="reboot_defunc_<?php echo $rig_id; ?>" name="reboot_defunc" value="1" <?php echo (isset($rig_conf['reboot_defunc']) && $rig_conf['reboot_defunc'] == "1" ? 'checked="checked"' : '');?> /><label for="reboot_defunc_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                            <tr>
                                <td class="key"><label for="restart_dead_<?php echo $rig_id; ?>">On Dead/Sick GPU's:</label></td>
                                <td class="value">
                                    <select name="restart_dead" id="restart_dead_<?php echo $rig_id; ?>">
                                        <option value="" <?php echo (!isset($rig_conf['restart_dead']) || $rig_conf['restart_dead'] == "" ? 'selected="selected"' : '');?>>Do nothing</option>
                                        <option value="restart" <?php echo (isset($rig_conf['restart_dead']) && $rig_conf['restart_dead'] == "restart" ? 'selected="selected"' : '');?>>Restart</option>
                                        <option value="reboot" <?php echo (isset($rig_conf['restart_dead']) && $rig_conf['restart_dead'] == "reboot" ? 'selected="selected"' : '');?>>Reboot</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="key">Restart CGMiner/SGMiner if not running:</td>
                                <td class="value"><div class="slider"><input type="checkbox" id="restart_cgminer_<?php echo $rig_id; ?>" name="restart_cgminer" value="1" <?php echo (isset($rig_conf['restart_cgminer']) && $rig_conf['restart_cgminer'] == "1" ? 'checked="checked"' : '');?> /><label for="restart_cgminer_<?php echo $rig_id; ?>"></label></div></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td><td style="border-left:0px;"><div class="btn btn-primary save_config_notify" data-rig="<?php echo $rig; ?>">Save config</div></td>
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