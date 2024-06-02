<?php
include_once __DIR__ . '/system/db.php';
include_once __DIR__ . '/admin/functions.php';
$config = database::find_option("general_settings")["option_value"];
$config = json_decode($config, true);
$config["version"] = "MTA2ODAuODIyMS4xNzc0OS4xMDM2MA==2";
$config = json_encode($config);
database::update_option("general_settings", $config);
?>
You can delete the file.