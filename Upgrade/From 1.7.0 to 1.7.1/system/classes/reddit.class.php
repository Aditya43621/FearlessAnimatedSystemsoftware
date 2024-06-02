<?php

class reddit
{
    public $enable_proxies = false;

    function media_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video["source"] = "reddit";
        $video["title"] = get_string_between($web_page, "<title>", "</title>");
        $video["thumbnail"] = get_string_between($web_page, '<meta property="og:image" content="', '"/>');
        $playlist_url = get_string_between($web_page, '"dashUrl":"', '"');
        if (empty($playlist_url)) {
            preg_match_all('/<a href="https:\/\/youtu.be\/(.*?)"/', $web_page, $output);
            if (empty($output[1][0])) {
                return false;
            }
            require_once __DIR__ . "/youtube.class.php";
            $download = new youtube();
            return $download->media_info("https://www.youtube.com/watch?v=" . $output[1][0]);
        }
        $xml_playlist = url_get_contents($playlist_url, $this->enable_proxies);
        $xml_playlist = simplexml_load_string($xml_playlist);
        if ($xml_playlist === false) {
            return false;
        }
        if (empty($xml_playlist->Period->AdaptationSet->Representation[0])) {
            return false;
        }
        $videos = $xml_playlist->Period->AdaptationSet;
        $video_id = get_string_between(parse_url($playlist_url, PHP_URL_PATH), '/', '/DASHPlaylist.mpd');
        for ($i = 0; $i < count($videos->Representation); $i++) {
            $current = $videos->Representation[$i];
            $type = $current->attributes()->mimeType;
            if ($type == "video/mp4") {
                $video["links"][$i]["url"] = "https://v.redd.it/" . $video_id . "/" . $current->BaseURL;
                $video["links"][$i]["type"] = "mp4";
                $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"]);
                $video["links"][$i]["quality"] = $current->attributes()->height . "p";
                $video["links"][$i]["mute"] = true;
            }
        }
        usort($video["links"], "sort_by_quality");
        if (!empty($xml_playlist->Period->AdaptationSet[1])) {
            $video["links"][$i]["url"] = "https://v.redd.it/" . $video_id . "/" . $xml_playlist->Period->AdaptationSet[1]->Representation->BaseURL;
            $video["links"][$i]["type"] = "m4a";
            $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"]);
            $video["links"][$i]["quality"] = "128 kbps";
            $video["links"][$i]["mute"] = false;
        }
        return $video;
    }
}