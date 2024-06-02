<?php

class tiktok
{
    public $enable_proxies = false;
    private $tries = 0;
    private $maxTries = 2;
    private $tt_webid_v2 = "6852693877233714693"; //6887648743983597061
    private $tt_webid = "6852693877233714693";
    private $cookie_file = __DIR__ . "/../storage/temp/tiktok-cookie.txt";

    private function get_webid($video_url)
    {
        $curl = curl_init();
        $fields = array(
            "app_id" => 1888,
            "url" => $video_url,
            "user_agent" => _REQUEST_USER_AGENT,
            "referer" => "",
            "user_unique_id" => ""
        );
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://mcs-va.tiktokv.com/v1/user/webid",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_HTTPHEADER => array(
                "Host: mcs-va.tiktokv.com",
                "Connection: close",
                "User-Agent: " . _REQUEST_USER_AGENT,
                "Content-Type: application/json; charset=UTF-8",
                "Accept: */*",
                "Origin: https://www.tiktok.com",
                "Sec-Fetch-Site: cross-site",
                "Sec-Fetch-Mode: cors",
                "Sec-Fetch-Dest: empty",
                "Referer: https://www.tiktok.com/",
                "Accept-Encoding: gzip, deflate"
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        echo $response;
    }

    private function get_video($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Referer: https://www.tiktok.com/",
                "Cookie: tt_webid_v2=" . $this->tt_webid_v2 . "; tt_webid=" . $this->tt_webid
            ),
            //CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function get_redirect_url($url)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'okhttp',
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $url;
    }

    private function get_video_size($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_NOBODY => TRUE,
            CURLOPT_HTTPHEADER => array(
                "Referer: https://www.tiktok.com/"
            )
        ));
        $response = curl_exec($curl);
        $filesize = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($curl);
        return format_size($filesize);
    }

    private function get_key($playable)
    {
        $ch = curl_init();
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: en-US,en;q=0.9',
            'Range: bytes=0-200000',
            'Referer: https://www.tiktok.com/',
            'Cookie: tt_webid_v2=' . $this->tt_webid_v2 . '; tt_webid=' . $this->tt_webid
        ];
        $options = array(
            CURLOPT_URL => $playable,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $tmp = explode("vid:", $data);
        if (count($tmp) > 1) {
            $key = trim(explode("%", $tmp[1])[0]);
        } else {
            $key = "";
        }
        file_put_contents(__DIR__ . '/../storage/temp/tiktok.html', $data);
        return $key;
    }

    private function get_key_from_api($video_url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.wppress.net/tiktok/nwm/" . filter_var($video_url, FILTER_SANITIZE_NUMBER_INT),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        $key = "";
        if (isset($response["id"]) != "") {
            $key = $response["id"];
        }
        return $key;
    }

    function media_info($url)
    {
        $this->tries++;
        $url = unshorten($url, $this->enable_proxies);
        $web_page = $this->get_video($url);
        $wid = get_string_between($web_page, '{"$wid":"', '"');
        $this->tt_webid_v2 = $wid;
        $this->tt_webid = $wid;
        $data = json_decode(get_string_between($web_page, '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">', '</script>'), true);
        if (empty($data["props"]["pageProps"] ?? null)) {
            return false;
        }
        $video["source"] = "tiktok";
        $video["webid"] = $wid;
        $video["title"] = $data["props"]["pageProps"]["itemInfo"]["itemStruct"]["desc"];
        if (empty($video["title"]) && isset($data["props"]["pageProps"]["itemInfo"]["shareMeta"]["title"])) {
            $video["title"] = $data["props"]["pageProps"]["itemInfo"]["shareMeta"]["title"];
        }
        if (empty($video["title"])) {
            $video["title"] = "Tiktok Video";
        }
        $thumbnail = get_string_between($web_page, 'property="og:image" content="', '"');
        if (!empty($data["props"]["pageProps"]["itemInfo"]["itemStruct"]["video"]["reflowCover"] ?? "")) {
            $video["thumbnail"] = $data["props"]["pageProps"]["itemInfo"]["itemStruct"]["video"]["reflowCover"];
        } else if (!empty($thumbnail)) {
            $video["thumbnail"] = $thumbnail;
        } else {
            $video["thumbnail"] = "https://s16.tiktokcdn.com/musical/resource/wap/static/image/logo_144c91a.png?v=2";
        }
        $video["links"] = array();
        //$original_video = str_replace("&amp;", "&", get_string_between($web_page, 'property="og:video:secure_url" content="', '"/>'));
        $original_video = $data["props"]["pageProps"]["itemInfo"]["itemStruct"]["video"]["playAddr"];
        $track_id = rand(0, 4);
        $cache_file = __DIR__ . "/../storage/temp/tiktok-" . $track_id . ".mp4";
        $cache = fopen($cache_file, 'w+');
        $videoFile = $this->get_video($original_video);
        fwrite($cache, $videoFile);
        fclose($cache);
        $website_url = json_decode(option("general_settings"), true)["url"];
        array_push($video["links"], array(
            "url" => $website_url . "/system/storage/temp/tiktok-" . $track_id . ".mp4",
            "type" => "mp4",
            "quality" => "watermarked",
            "size" => format_size(filesize($cache_file)),
            "mute" => false
        ));
        $video_key = $this->get_key($data["props"]["pageProps"]["itemInfo"]["itemStruct"]["video"]["playAddr"]);
        if (empty($video_key)) {
            $video_key = $this->get_key_from_api($url);
        }
        if (!empty($video_key)) {
            $clean_video = "https://api2-16-h2.musical.ly/aweme/v1/play/?video_id=$video_key&vr_type=0&is_play_url=1&source=PackSourceEnum_PUBLISH&media_type=4";
            $clean_video = $this->get_redirect_url($clean_video);
            if (filter_var($clean_video, FILTER_VALIDATE_URL)) {
                array_push($video["links"], array(
                    "url" => $clean_video,
                    "type" => "mp4",
                    "quality" => "hd",
                    "size" => get_file_size($clean_video, $this->enable_proxies),
                    "mute" => false
                ));
            }
        }
        if ($video_key == "invalid license") {
            return false;
        }
        if (isset($data["props"]["pageProps"]["itemInfo"]["itemStruct"]["music"]["id"]) != "") {
            array_push($video["links"], array(
                "url" => $data["props"]["pageProps"]["itemInfo"]["itemStruct"]["music"]["playUrl"],
                "type" => "mp3",
                "quality" => "128 kbps",
                "size" => get_file_size($data["props"]["pageProps"]["itemInfo"]["itemStruct"]["music"]["playUrl"], $this->enable_proxies),
                "mute" => false
            ));
        }
        if (!filter_var($video["links"][0]["url"], FILTER_VALIDATE_URL)) {
            while ($this->tries++ < $this->maxTries) {
                $this->media_info($url);
            }
        }
        $video["orig"] = $original_video;
        $video["key"] = $video_key;
        return $video;
    }
}