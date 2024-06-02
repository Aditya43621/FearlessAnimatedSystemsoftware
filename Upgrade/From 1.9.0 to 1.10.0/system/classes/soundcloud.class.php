<?php

class soundcloud
{
    public $enable_proxies = false;
    public $api_key = "";
    public $api_key_file = __DIR__ . "/../storage/soundcloud-api-key.json";

    public function get_api_key()
    {
        if (file_exists($this->api_key_file)) {
            $array = json_decode(file_get_contents($this->api_key_file), true);
            if (isset($array["expires_at"]) && time() > $array["expires_at"]) {
                $this->api_key = $array["key"] ?? "";
            }
        }
        if (empty($this->api_key)) {
            $js_file = url_get_contents("https://a-v2.sndcdn.com/assets/2-6b083daa-3.js", $this->enable_proxies);
            $api_key = get_string_between($js_file, '"web-auth?client_id=', '&device_id=');
            $this->api_key = $api_key;
            file_put_contents($this->api_key_file, json_encode(array("key" => $api_key, "expires_at" => time() + 10800), JSON_PRETTY_PRINT));
        }
        if (empty($this->api_key)) {
            $this->api_key = option("api_key.soundcloud");
        }
    }

    private function merge_parts($stream_url, $merged_file)
    {
        $m3u8_url = json_decode(url_get_contents($stream_url . "?client_id=" . $this->api_key), true)["url"];
        $m3u8_data = url_get_contents($m3u8_url);
        preg_match_all('/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/', $m3u8_data, $streams_raw);
        $merged = "";
        foreach ($streams_raw[0] as $stream_part) {
            $merged .= url_get_contents($stream_part, $this->enable_proxies);
        }
        file_put_contents($merged_file, $merged);
    }

    function media_info($url)
    {
        $this->get_api_key();
        $api_key = $this->api_key;
        $web_page = url_get_contents($url, $this->enable_proxies);
        $track_id = get_string_between($web_page, 'content="soundcloud://sounds:', '">');
        $track["title"] = get_string_between($web_page, 'property="og:title" content="', '"');
        $track["source"] = "soundcloud";
        $track["thumbnail"] = get_string_between($web_page, 'property="og:image" content="', '"');
        $track["duration"] = format_seconds(get_string_between($web_page, '"full_duration":', ',') / 1000);
        $track["links"] = array();
        $transcodings = get_string_between($web_page, '"media":{"transcodings":[', ']');
        $data["media"]["transcodings"] = json_decode("[" . $transcodings . "]", true);
        if (empty($data["media"]["transcodings"])) {
            return false;
        }
        $website_url = json_decode(option("general_settings"), true)["url"];
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
            } else if ($stream["format"]["protocol"] == "hls") {
                $file_ext = $stream["format"]["mime_type"] == "audio/mpeg" ? "mp3" : "ogg";
                $merged_file = __DIR__ . "/../storage/temp/soundcloud-" . $track_id . "." . $file_ext;
                if (!file_exists($merged_file) || filesize($merged_file) < 1000) {
                    $this->merge_parts($stream["url"], $merged_file);
                }
                if (($file_ext == "mp3" && !isset($track["links"][0]["url"])) || $file_ext == "ogg") {
                    array_push($track["links"], array(
                        "url" => $website_url . "/system/storage/temp/soundcloud-" . $track_id . "." . $file_ext,
                        "type" => $file_ext,
                        "quality" => "128 kbps",
                        "size" => format_size(filesize($merged_file)),
                        "mute" => false
                    ));
                }
            }
        }
        array_push($track, array("transcodings" => $data["media"]["transcodings"]));
        return $track;
    }
}