<?php
$database["host"] = "{{host}}";
$database["name"] = "{{name}}";
$database["user"] = "{{user}}";
$database["password"] = "{{pass}}";
require_once __DIR__ . "/../admin/classes/database.class.php";
database::connect("mysql:host=" . $database["host"] . ";dbname=" . $database["name"] . ";charset=utf8mb4", $database["user"], $database["password"]);