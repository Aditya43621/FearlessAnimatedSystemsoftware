<?php

class soundcloud
{
    public $enable_proxies = false;
    public $api_key = "";
    public $api_key_file = __DIR__ . "/../storage/soundcloud-api-key.json";

    public function __construct()
    {
        if (file_exists($this->api_key_file)) {
            $array = json_decode(file_get_contents($this->api_key_file), true);
            if (isset($array["expires_at"]) && time() < $array["expires_at"]) {
                $this->api_key = $array["key"] ?? "";
            } else {
                $this->api_key = $this->get_api_key();
            }
        } else {
            $this->api_key = $this->get_api_key();
        }
    }

    public function get_api_key($js_file = "https://a-v2.sndcdn.com/assets/2-5e4e4418-3.js")
    {
        $js_file = url_get_contents($js_file, $this->enable_proxies);
        $api_key = get_string_between($js_file, '"web-auth?client_id=', '&device_id=');
        file_put_contents($this->api_key_file, json_encode(array("key" => $api_key, "expires_at" => time() + 10800), JSON_PRETTY_PRINT));
        return !empty($api_key) ? $api_key : $this->get_api_key("https://a-v2.sndcdn.com/assets/2-6b083daa-3.js");
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

    private function get_track_data($track_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api-v2.soundcloud.com/tracks?ids=$track_id&client_id=$this->api_key&app_version=1605107988&app_locale=en",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_HTTPHEADER => array(
                "Connection: keep-alive",
                "Accept: application/json, text/javascript, */*; q=0.1",
                "Content-Type: application/json",
                "Origin: https://soundcloud.com",
                "Sec-Fetch-Site: same-site",
                "Sec-Fetch-Mode: cors",
                "Sec-Fetch-Dest: empty",
                "Referer: https://soundcloud.com/",
                "Accept-Language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6"
            ),
        ));
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data, true)[0];
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
        $data = $this->get_track_data($track_id);
        if (empty($data["media"]["transcodings"])) {
            return false;
        }
        $website_url = json_decode(option("general_settings"), true)["url"];
        foreach ($data["media"]["transcodings"] as $stream) {
            if ($stream["format"]["protocol"] == "progressive") {
                $mp3_url = json_decode(url_get_contents($stream["url"] . "?client_id=" . $api_key, $this->enable_proxies), true)["url"] ?? null;
                $mp3_size = get_file_size($mp3_url, $this->enable_proxies);
                if (!empty($mp3_size)) {
                    array_push($track["links"], array(
                        "url" => $mp3_url,
                        "type" => "mp3",
                        "quality" => "128 kbps",
                        "size" => $mp3_size,
                        "mute" => false
                    ));
                }
                break;
            }
        }
        if (!isset($track["links"][0]["url"])) {
            foreach ($data["media"]["transcodings"] as $stream) {
                if ($stream["format"]["protocol"] == "hls") {
                    $file_ext = $stream["format"]["mime_type"] == "audio/mpeg" ? "mp3" : "ogg";
                    $merged_file = __DIR__ . "/../storage/temp/soundcloud-" . $track_id . "." . $file_ext;
                    if (!file_exists($merged_file) || filesize($merged_file) < 1000) {
                        $this->merge_parts($stream["url"], $merged_file);
                    }
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
        //array_push($track, array("transcodings" => $data["media"]["transcodings"]));
        return $track;
    }
}