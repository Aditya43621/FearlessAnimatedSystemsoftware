<?php
include_once __DIR__ . '/system/db.php';
include_once __DIR__ . '/admin/functions.php';
$config = database::find_option("general_settings")["option_value"];
$config = json_decode($config, true);
$config["version"] = "MTU1MjIwLjk1MzAxLjg1OTExLjMwMDA=2";
$config = json_encode($config);
database::update_option("general_settings", $config);
?>
Upgrade completed. 
You can delete this (update.php) file.