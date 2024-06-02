<?php

class soundcloud
{
    public $enable_proxies = false;

    function media_info($url)
    {
        $api_key = option("api_key.soundcloud");
        $web_page = url_get_contents($url, $this->enable_proxies);
        $track["title"] = get_string_between($web_page, 'property="og:title" content="', '"');
        $track["source"] = "soundcloud";
        $track["thumbnail"] = get_string_between($web_page, 'property="og:image" content="', '"');
        $track["duration"] = format_seconds(get_string_between($web_page, '"full_duration":', ',') / 1000);
        $track["links"] = array();
        $transcodings = get_string_between($web_page, '"media":{"transcodings":[', ']');
        $data["media"]["transcodings"] = json_decode("[" . $transcodings . "]", true);
        foreach ($data["media"]["transcodings"] as $stream) {
            if ($stream["format"]["protocol"] == "progressive") {
                $mp3_url = json_decode(url_get_contents($stream["url"] . "?client_id=" . $api_key, $this->enable_proxies), true)["url"];
                $mp3_size = get_file_size($mp3_url, $this->enable_proxies);
                if (!empty($mp3_size)) {
                    array_push($track["links"], array(
                        "url" => $mp3_url,
                        "type" => "mp3",
                        "quality" => "128 kbps",
                        "size" => $mp3_size,
                        "mute" => false
                    ));
                    break;
                }
            }
        }
        if (empty($track["links"][0]["url"])) {
            $track_id = $data["id"];
            $merged_file = __DIR__ . "/../storage/temp/soundcloud-" . $track_id . ".mp3";
            $website_url = json_decode(option("general_settings"), true)["url"];
            if (file_exists($merged_file) && filesize($merged_file) < 10000) {
                unlink($merged_file);
            } else if (file_exists($merged_file) && filesize($merged_file) > 10000) {
                $track["links"][0]["url"] = $website_url . "/system/storage/temp/soundcloud-" . $track_id . ".mp3";
                $track["links"][0]["type"] = "mp3";
                $track["links"][0]["size"] = format_size(filesize($merged_file));
                $track["links"][0]["quality"] = "128 kbps";
                $track["links"][0]["mute"] = "no";
            } else {
                $merged = fopen($merged_file, 'a+');
                foreach ($data["media"]["transcodings"] as $stream) {
                    if ($stream["format"]["protocol"] == "hls" && $stream["format"]["mime_type"] == "audio/mpeg") {
                        $m3u8_url = json_decode(url_get_contents($stream["url"] . "?client_id=" . $api_key), true)["url"];
                        $m3u8_data = url_get_contents($m3u8_url);
                        preg_match_all('/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/', $m3u8_data, $streams_raw);
                        $streams = $streams_raw[0];
                        foreach ($streams as $stream_part) {
                            fwrite($merged, url_get_contents($stream_part));
                        }
                        break;
                    }
                }
                fclose($merged);
                $track["links"][0]["url"] = $website_url . "/system/storage/temp/soundcloud-" . $track_id . ".mp3";
                $track["links"][0]["type"] = "mp3";
                $track["links"][0]["size"] = format_size(filesize($merged_file));
                $track["links"][0]["quality"] = "128 kbps";
                $track["links"][0]["mute"] = "no";
            }
        }
        return $track;
    }
}