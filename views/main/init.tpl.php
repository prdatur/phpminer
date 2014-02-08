<?php $conf = $this->get_variable('config'); ?>
<?php if (empty($conf['pager_mepp'])) { $conf['pager_mepp'] = 1; } ?>
<div class="btn btn-primary" id="add_rig" style="margin-bottom: 15px;">Add a new rig</div>
<?php if (!empty($conf['enable_paging'])):  ?>
<div style='float:right;text-align: right;'>
    <label for='pager'>Rig's per page: &nbsp;</label>
    <select id='pager'>
        <?php foreach (array(5, 10, 15, 30, 50, 100) AS $p): ?>
        <option value="<?php echo $p; ?>" <?php if($conf['pager_mepp'] == $p) {echo ' selected="selected"'; } ?>><?php echo $p; ?></option>
        <?php endforeach; ?>
    </select><br />
    <div id='pager_init_device_pager' class="pager"></div>
</div>
<div class="clearfix"></div>
<?php endif;  ?>
<div id="rigs"></div>