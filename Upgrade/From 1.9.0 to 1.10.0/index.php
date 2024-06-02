<?php
require_once __DIR__ . "/system/config.php";
$template = $config["template"];
if (isset(parse_url($config["url"])["path"]) != "") {
    $path = parse_url($config["url"])["path"];
} else {
    $path = "";
}
$slug = substr(str_replace($path, "", $_SERVER["REQUEST_URI"]), 1);
include(__DIR__ . "/template/" . $template . "/functions.php");
$content["content_type"] = -1;
preg_match('/lang=(\w{1,4})/', $slug, $langSlug);
preg_match('/u=(.*)/', $slug, $shareSlug);
preg_match('/watch\?v=(.*)/', $slug, $watchSlug);
$canonical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
if (isset($langSlug[1]) != "") {
    if (language_exists(clear_string($langSlug[1])) === true) {
        $_SESSION["current_language"] = clear_string($langSlug[1]);
        require_once(__DIR__ . "/language/" . $_SESSION["current_language"] . ".php");
    } else {
        if (isset($_SERVER["HTTP_REFERER"]) != "") {
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            redirect($config["url"]);
        }
    }
} else {
    $canonical_url .= "?lang=" . $config["language"];
}
switch (true) {
    default:
        include(__DIR__ . "/template/" . $template . "/header.php");
        include(__DIR__ . "/template/" . $template . "/main.php");
        include(__DIR__ . "/template/" . $template . "/footer.php");
        break;
    case($slug == "sitemap.xml"):
        include(__DIR__ . "/sitemap.php");
        break;
    case(isset($shareSlug[1]) != ""):
        $newURL = $config["url"] . "/#url=" . $shareSlug[1];
        redirect($newURL);
        break;
    case(!empty($watchSlug[1])):
        if (isset($_GET["v"]) != "") {
            $videoId = $_GET["v"];
            $newURL = $config["url"] . "/#url=https://www.youtube.com/watch?v=" . $videoId;
            redirect($newURL);
        } else {
            redirect($config["url"]);
        }
        break;
    case($slug != "" && substr($slug, 0, 1) != "?"):
        $page_exists = database::slug_exists($slug);
        if ($page_exists === 0 || $slug == "home") {
            header('HTTP/1.0 404 Not Found');
            $slug = "";
            $content["content_type"] = 0;
            include(__DIR__ . "/template/" . $template . "/header.php");
            include(__DIR__ . "/template/" . $template . "/404.php");
            include(__DIR__ . "/template/" . $template . "/footer.php");
        } else {
            include(__DIR__ . "/template/" . $template . "/header.php");
            include(__DIR__ . "/template/" . $template . "/page.php");
            include(__DIR__ . "/template/" . $template . "/footer.php");
        }
        break;
}