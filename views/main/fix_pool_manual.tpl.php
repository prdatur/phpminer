Here you can merge an existing group with the current CGMiner/SGMiner configurated pools.<br />
PHPMiner could not find a group where the same pools are configurated as currently in CGMiner/SGMiner, so you have to create a pool where this match.<br /><br />
<b>You have the following possibilities:</b><br />
- You can delete a non-active pool from CGMiner/SGMiner<br />
- You can add a pool to CGMiner/SGMiner which is only within the selected group.<br />
- You can delete a pool within the selected group which is not configurated within CGMiner/SGMiner.<br />
- You can add a pool within the selected group which is configurated within CGMiner/SGMiner but no in the group.<br />
<br />
<br />
<b>
    The result must be, that in both systems (CGMiner/SGMiner and group) the same pools exist. The order is NOT important.<br />
    You can proceed if all pool entries have a green background.</b>
    <br /><br />
<b>CGMiner/SGMiner configurated pools:</b>
<table>
    <thead>
        <tr>
            <th>Url</th>
            <th style="width:250px;">Username</th>
            <th style="width:250px;">options</th>
        </tr>
    </thead>
    <tbody id="cgminer_pools">
<?php foreach($this->get_variable('cgminer_pools') AS $pool): ?>
        <tr data-uuid="<?php echo $pool['URL']; ?>|<?php echo $pool['User']; ?>">
            <td><?php echo $pool['URL']; ?></td>
            <td><?php echo $pool['User']; ?></td>
            <td class="options"><a href="javascript:void(0);" class="btn btn-success add_to_group">Add to group</a> - <a href="javascript:void(0);" class="btn btn-danger remove_from_cgminer">Remove from CGMiner/SGMiner</a></td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
<br />
<b>Group configurated pools:</b><select id='cfg_groups' style="margin-bottom: 4px; margin-left: 10px;">
    <option value="">- Please select a group - </option>
    <?php foreach($this->get_variable('cfg_groups') AS $cfg_group): ?>
    <option value="<?php echo $cfg_group; ?>"><?php echo $cfg_group; ?></option>
    <?php endforeach; ?>
</select> - <a href="javascript:void(0);" id="search_best_match">Search for best match</a>
<table>
    <thead>
        <tr>
            <th>Url</th>
            <th style="width:250px;">Username</th>
            <th style="width:250px;">options</th>
        </tr>
    </thead>
    <tbody id='group_pools'>
        <tr>
            <td colspan="3">You didn't selected a group yet, or this group does not have any pools configurated.</td>
        </tr>
    </tbody>
</table>
