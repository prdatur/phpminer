<?php if (!$this->variable_is_empty('pool_groups')): ?>
<div id='pool_switching_container' style='<?php echo (!$this->variable_is_empty('donating')) ? 'display: none;' : ''?>'>
    <select id="current_pool_pool" style="float: right;"><option value="">Please wait, loading pools.</option></select>
    <label for="current_pool_pool" style="float: right; margin-left: 10px;">Change mining pool:&nbsp;&nbsp;</label>

    <select id="current_pool_group" style="float: right;">
        <?php foreach ($this->get_variable('pool_groups') AS $group): ?>
            <?php if ($group === 'donate') { continue; } ?>
            <option value="<?php echo $group; ?>"<?php if (!$this->variable_is_empty('current_group') && $this->get_variable('current_group') === $group): ?> selected="selected"<?php endif; ?>  ><?php echo $group; ?></option>
        <?php endforeach; ?>    
    </select>
    <label for="current_pool_group" style="float: right;">Change mining group:&nbsp;&nbsp;</label>
</div>
<?php endif; ?>    
<table id="device_list">
    <thead>
        <tr>
            <th style="width:70px;" class="center">Enabled</th>
            <th>Name</th>
            <th style="width: 70px;" class="right"><i class="icon-signal"></i>Load</th>
            <th style="width: 140px;" class="right"><i class="icon-thermometer"></i>Temperature</th>
            <th style="width: 140px;" class="right"><i class="icon-chart-line"></i>Hashrate 5s (avg)</th>
            <th style="width: 180px;" class="right"><i class="icon-link-ext"></i>Shares</th>
            <th style="width: 140px;" class="right"><i class="icon-air"></i>Fan</th>
            <th style="width: 120px;" class="right"><i class="icon-clock"></i>GPU Clock</th>
            <th style="width: 120px;" class="right"><i class="icon-clock"></i>Memory Clock</th>
            <th style="width: 120px;" class="right"><i class="icon-flash"></i>Voltage</th>
            <th style="width: 100px;" class="right"><i class="icon-fire"></i>Intensity</th>
            <th style="width: 320px;" class="right"><i class="icon-group"></i>Current pool</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>