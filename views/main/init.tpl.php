<?php $conf = $this->get_variable('config'); ?>
<?php if (empty($conf['pager_mepp'])) { $conf['pager_mepp'] = 5; } ?>
<div class="btn btn-primary" id="add_rig" style="margin-bottom: 15px;">Add a new rig</div>
<div style='float:right;text-align: right;'>
<?php if (!empty($conf['enable_paging'])):  ?>
    <label for='pager'>Rig's per page: &nbsp;</label>
    <select id='pager'>
        <?php foreach (array(1, 2, 5, 10, 15, 30, 50, 100) AS $p): ?>
        <option value="<?php echo $p; ?>" <?php if($conf['pager_mepp'] == $p) {echo ' selected="selected"'; } ?>><?php echo $p; ?></option>
        <?php endforeach; ?>
    </select><br />
    <div id='pager_init_device_pager' class="pager"></div>
<?php endif;  ?>
    
<div class="btn btn-primary" id="reset_all_rig_stats" style="margin-bottom: 15px;"><i class="icon-ccw"></i> Reset all rig stats</div>
</div>
<div class="clearfix"></div>
<div id="rigs"></div>