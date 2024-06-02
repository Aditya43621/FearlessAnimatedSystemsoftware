<?php
include_once __DIR__ . '/system/db.php';
include_once __DIR__ . '/admin/functions.php';
$config = database::find_option("general_settings")["option_value"];
$config = json_decode($config, true);
$config["version"] = "MTI4MDIwLjc5MDEuNDgyMTIuNjE5MDA=2";
$config = json_encode($config);
database::update_option("general_settings", $config);
database::create_content([
    "slug" => "periscope-video-downloader",
    "text" => "",
    "title" => "Periscope Video Downloader",
    "description" => "Periscope Video Downloader",
    "opt" => null,
    "type" => "1"
]);
database::create_content([
    "slug" => "febspot-video-downloader",
    "text" => "",
    "title" => "Febspot Video Downloader",
    "description" => "Febspot Video Downloader",
    "opt" => null,
    "type" => "1"
]);
database::create_content([
    "slug" => "rumble-video-downloader",
    "text" => "",
    "title" => "Rumble Video Downloader",
    "description" => "Rumble Video Downloader",
    "opt" => null,
    "type" => "1"
]);
?>
You can delete the file.