<div class="defaultBox defaultBox_<?php echo $type; ?>">
    <?php if (isset($title)): ?>
        <div id="title">
            <div>
                <?php if ($type == "success"): ?>
                    <img src="<?php echo $this->get_variable('docroot'); ?>/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-check" alt="<?php echo 'Success'; ?>">
                <?php elseif ($type == "error"): ?>
                    <img src="<?php echo $this->get_variable('docroot'); ?>/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" alt="<?php echo 'Error'; ?>" />
                <?php elseif ($type == "info"): ?>
                    <img src="<?php echo $this->get_variable('docroot'); ?>/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-warning" alt="<?php echo 'Info'; ?>" />
                <?php endif; ?>

            </div>
            <div class="title-cell">
                <?php if ($type == "success"): ?>
                    <?php echo 'Success: '; ?>
                <?php elseif ($type == "error"): ?>
                    <?php echo 'Error: '; ?>
                <?php elseif ($type == "info"): ?>
                    <?php echo 'Info: '; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <div id="description">
        <ul class="error_box">
        <?php foreach ($message_arrray AS $message): ?>
            <li><?php echo $message; ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>