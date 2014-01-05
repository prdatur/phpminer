
<?php if ($this->has_variable('unconfigured_pools')): ?>
    You didn't configurated any pools yet.<br />
    PHPMiner can only read out the current configured pools without the password.<br/>
    So please look at the list below and provide your worker password. <br />
    <br />
    <b>Notice:</b> <br />
    Any pool where you don't provide a password will not be stored within the config <br />
    If you want to use the unconfigurated pools again, you have to manually add them within PHPMiner > Pools > Add Pool<br />
    As long as you stay on this page you can add the workers how you want it.
    For example within the first "save" round you could configure all the workers with password "123" and within the second round all with password "1234". <br />
    Only make sure after you successfully saved one of the pools to not reload this page, else it will find a valid pool config and this page will not be available.    
    <br /><br />
    <b>Groups:</b> <br />
    PHPMiner supports pool groups, you can define any groups with a set of mining pools. For example you can define for each coin type a different pool. So you can easy switch between coins.
    <br /><br />


    <table>
        <thead>
            <tr>
                <th style="width:400px;">Url</th>
                <th style="width:200px;">Username</th>
                <th style="width:200px;">Password</th>
                <th>Group</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$this->variable_is_empty('unconfigured_pools')): ?>
                <?php foreach ($this->get_variable('unconfigured_pools') AS $pool) : ?>
                    <tr>
                        <td class='url'><?php echo $pool['URL']; ?></td>
                        <td><?php echo $pool['User']; ?></td>
                        <td><input type="text" value="" data-pool_url="<?php echo $pool['URL']; ?>" data-pool_user="<?php echo $pool['User']; ?>"/></td>
                        <td><input type="text" value="default" class="pool_group"/></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan='3'>No pools configurated within cgminer</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br />
    <button class='btn btn-primary' id='save_unconfigurated_pools'>Save</button>
<?php else: ?>
    
    <a class="btn btn-primary" id="add-group" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a group</a>
    <div class="panel-group pool_groups" id="accordion">
        <?php foreach($this->pool_config->get_groups() AS $group): ?>
        <?php if ($group === 'donate') { continue; } ?>
        <div class="panel panel-default" data-grp="<?php echo $group; ?>">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href="#collapse_<?php echo preg_replace("[^a-zA-Z0-9]", "_", $group); ?>">
                        Group: <?php echo $group; ?>
                    </a>
                </h4>
            </div>
            <div id="collapse_<?php echo preg_replace("[^a-zA-Z0-9]", "_", $group); ?>" class="panel-collapse collapse in">
                <div class="panel-body">
                    <a class="btn btn-primary btn-sm" data-add-pool-group="<?php echo $group; ?>" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a pool</a>
                    <a class="btn btn-danger btn-sm" data-del-pool-group="<?php echo $group; ?>" style="margin-bottom: 10px;padding-left: 5px; float: right;"><i class="icon-minus"></i>Delete group</a>
                    <table>
                        <thead>
                            <tr>
                                <th>Url</th>
                                <th style="width:200px;">Username</th>
                                <th style="width:200px;">Password</th>
                                <th style="width:60px;">Quota</th>
                                <th style="width:120px;">Options</th>
                            </tr>
                        </thead>
                        <tbody class="pools" data-pool_group="<?php echo $group; ?>">
                            <?php foreach($this->pool_config->get_pools($group) AS $uuid => $pool): ?>
                            <tr data-uuid="<?php echo $uuid; ?>|<?php echo $group; ?>">
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="url" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="/pools/change_pool.json" data-title="Enter pool url"><?php echo $pool['url']; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="user" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="/pools/change_pool.json" data-title="Enter worker username"><?php echo $pool['user']; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="pass" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="/pools/change_pool.json" data-title="Enter worker password"><?php echo $pool['pass']; ?></a></td>
                                <td class="nopadding"><a href="javascript:void(0);" class="clickable" data-name="quota" data-type="text" data-pk="<?php echo $uuid; ?>|<?php echo $group; ?>" data-url="/pools/change_pool.json" data-title="Pool Quota"><?php echo (isset($pool['quota'])) ? $pool['quota'] : '1'; ?></a></td>
                                <td><a href="javascript:void(0);" class="option-action" data-uuid="<?php echo $uuid; ?>|<?php echo $group; ?>" data-name="<?php echo $pool['url']; ?>" data-group="<?php echo $group; ?>" data-action="delete-pool" title="Delete"><i class="icon-trash"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
