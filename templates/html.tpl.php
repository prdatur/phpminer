<!DOCTYPE HTML>
<html>
    <head>
        <TITLE>PHPMiner - Mine better</TITLE>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="description" content="PHPMiner is fully implemented api client for CGMiner/SGMiner."/>
        <meta name="ROBOTS" content="INDEX, FOLLOW">

        <!-- Add CSS files -->
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/css/reset.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/bootstrap/css/bootstrap.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/fontello/css/phpminer.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/fontello/css/animation.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/css/jquery.nouislider.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/x-editable/bootstrap3-editable/css/bootstrap-editable.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/css/main.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/css/status_box.css" />
        <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot'); ?>/templates/css/popup.css" />
        <?php foreach ($this->get_variable('cssfiles') AS $file): ?>
            <link rel="StyleSheet" type="text/css" href="<?php echo $this->get_variable('docroot') . $file; ?>" />
        <?php endforeach; ?>

        <!-- Add Javascript files -->
        <script type="text/javascript" src="<?php echo $this->get_variable('docroot'); ?>/templates/js/jquery-1.10.2.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_variable('docroot'); ?>/templates/js/jquery.nouislider.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_variable('docroot'); ?>/templates/bootstrap/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_variable('docroot'); ?>/templates/x-editable/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_variable('docroot'); ?>/templates/js/common.js"></script>
        <script type="text/javascript" src="<?php echo $this->get_variable('docroot'); ?>/templates/js/core.js"></script>
        <?php foreach ($this->get_variable('jsfiles') AS $file): ?>
            <script type="text/javascript" src="<?php echo $this->get_variable('docroot') . $file; ?>"></script>
        <?php endforeach; ?>

        <?php if ($this->has_variable('jsconfig')): ?>
            <script type="text/javascript">
                var phpminer = {};
                phpminer.settings = <?php echo json_encode($this->get_variable('jsconfig')); ?>;
            </script>        
        <?php endif; ?>

    </head>
    <body>
        <div id="page">
            <section id="header">
                <h1>PHPMiner - Mine better<span id="global_hashrate"></span></h1>
                <ul class="clearfix">
                    <li><a href="<?php echo $this->get_variable('docroot'); ?>/"><i class="icon-home"></i>Home</a></li>
                    <li><a href="<?php echo murl('pools', 'main'); ?>"><i class="icon-group"></i>Pools</a></li>
                    <li><a href="<?php echo murl('notify', 'settings'); ?>"><i class="icon-beaker"></i>Notifications / Auto tasks</a></li>
                    <li><a href="<?php echo murl('main', 'settings'); ?>"><i class="icon-cogs"></i>Settings</a></li>
                    <li><a href="<?php echo murl('access', 'index'); ?>"><i class="icon-lock-alt"></i>Access management</a></li>
                </ul>
                <div class="donate">
                    <span style='display: inline-block;margin-right: 20px;text-align: right;'>
                        <b>Nice PHPMiner<br />Much work<br />Some donate</b>
                    </span>
                    <span style='display: inline-block;margin-right: 10px;'>
                    Doge: DCKWDLSocxn1t9TKpoknZnezCLAB6pkhiB<br />
                    BTC: 17dbqTnhn2qPLdSjaT7w2SkPLnCSMH4xFh<br />
                    LTC: Lh5sjSpN88N3PeG3vyQD9h6bz2jV4tdoke<br />
                    </span>
                </div>
            </section>
            <div id="header_border"></div>
            <div id="wrapper">
                <section id="status_messages">
                    <?php if ($this->has_variable('messages')): ?>
                        <?php foreach ($this->get_variable('messages') AS $type => $message_arrray): ?>
                            <?php include "status_message.tpl.php"; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
                <section id="content">
                    <?php if (empty($this->fatal_error) && file_exists(SITEPATH . '/views/' . $this->controller_name . '/' . $this->action_name . '.tpl.php')): ?>
                        <?php include SITEPATH . '/views/' . $this->controller_name . '/' . $this->action_name . '.tpl.php'; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </body>
</html>
