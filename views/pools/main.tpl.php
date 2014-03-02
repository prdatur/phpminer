<?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)): ?>
<a class="btn btn-primary" id="add-group" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a group</a>
<?php endif; ?>
<div class="panel-group pool_groups" id="accordion">
    <?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_VIEW_POOL_GROUP)): ?>
    <?php foreach($this->pool_config->get_groups() AS $group): ?>
    <?php if ($group === 'donate') { continue; } ?>
    <div class="panel panel-default" data-grp="<?php echo $group; ?>">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" href="#collapse_<?php echo preg_replace("/[^a-zA-Z0-9]/", "_", $group); ?>">
                    Group: <?php echo $group; ?>
                </a>
                <?php if ($group !== 'default'): ?>
                <a href="javascript:void(0)" class="edit-group" data-group="<?php echo $group; ?>" data-strategy="<?php echo $this->pool_config->get_strategy($group); ?>" data-rotate_period="<?php echo $this->pool_config->get_period($group); ?>"><i class="icon-edit"></i>Edit</a>
                <?php endif; ?>
            </h4>
        </div>
        <div id="collapse_<?php echo preg_replace("/[^a-zA-Z0-9]/", "_", $group); ?>" class="panel-collapse collapse in">
            <div class="panel-body">
                <?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)): ?>
                <a class="btn btn-primary btn-sm" data-add-pool-group="<?php echo $group; ?>" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a pool</a>
                <a class="btn btn-danger btn-sm" data-del-pool-group="<?php echo $group; ?>" style="margin-bottom: 10px;padding-left: 5px; float: right;"><i class="icon-minus"></i>Delete group</a>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr class="pool_table">
                            <th>Url</th>
                            <th style="width:200px;">Username</th>
                            <th style="width:200px;">Password</th>
                            <th style="width:60px;">Quota</th>
                            <th style="width:60px;">Rig based</th>
                            <?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)): ?><th style="width:120px;">Options</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="pools" data-pool_group="<?php echo $group; ?>">
                        <?php foreach($this->pool_config->get_pools($group) AS $uuid => $pool): ?>
                            <?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)): ?>
                            <tr data-uuid="<?php echo $uuid; ?>|<?php echo $group; ?>">
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="url" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="<?php echo murl('pools', 'change_pool', null, true); ?>" data-title="Enter pool url"><?php echo $pool['url']; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="user" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="<?php echo murl('pools', 'change_pool', null, true); ?>" data-title="Enter worker username"><?php echo $pool['user']; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="pass" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="<?php echo murl('pools', 'change_pool', null, true); ?>" data-title="Enter worker password"><?php echo $pool['pass']; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="quota" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="<?php echo murl('pools', 'change_pool', null, true); ?>" data-title="Pool Quota"><?php echo (isset($pool['quota'])) ? $pool['quota'] : '1'; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="rig_based" data-type="checklist" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="<?php echo murl('pools', 'change_pool', null, true); ?>" data-title="Enable rig based pool"><?php echo (isset($pool['rig_based']) && $pool['rig_based'] === true) ? 'Enabled' : 'Disabled'; ?></a></td>
                                <td><a href="javascript:void(0);" class="option-action" data-uuid="<?php echo $uuid; ?>|<?php echo $group; ?>" data-name="<?php echo $pool['url']; ?>" data-group="<?php echo $group; ?>" data-action="delete-pool" title="Delete"><i class="icon-trash"></i></a></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td class="nopadding"><?php echo $pool['url']; ?></td>
                                <td class="nopadding"><?php echo $pool['user']; ?></td>
                                <td class="nopadding"><?php echo $pool['pass']; ?></td>
                                <td class="nopadding"><?php echo (isset($pool['quota'])) ? $pool['quota'] : '1'; ?></td>
                                <td class="nopadding"><?php echo (isset($pool['rig_based']) && $pool['rig_based'] === true) ? 'Enabled' : 'Disabled'; ?></td>
                            </tr>

                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
