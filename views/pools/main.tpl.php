<?php if (AccessControl::getInstance()->has_permission(AccessControl::PERM_CHANGE_POOL_GROUP)): ?>
<a class="btn btn-primary" id="add-group" style="margin-bottom: 10px;padding-left: 5px;"><i class="icon-plus"></i>Add a group</a>
<?php endif; ?>
<div class="panel-group pool_groups" id="accordion">
</div>
