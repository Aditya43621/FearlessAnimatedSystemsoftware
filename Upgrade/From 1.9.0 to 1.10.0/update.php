<?php
include_once __DIR__ . '/system/db.php';
include_once __DIR__ . '/admin/functions.php';
$config = database::find_option("general_settings")["option_value"];
$config = json_decode($config, true);
$config["version"] = "MTc0NTIwLjU2NDAxLjEwNDIxMC4xMjU1MDA=6";
$config = json_encode($config);
database::update_option("general_settings", $config);
?>
Upgrade completed. 
You can delete this file.