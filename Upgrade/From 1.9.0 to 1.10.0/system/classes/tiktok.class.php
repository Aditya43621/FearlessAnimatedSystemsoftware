<?php

class tiktok
{
    public $enable_proxies = false;
    private $tries = 0;
    private $maxTries = 10;

    private function get_video($url)
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
            CURLOPT_MAXREDIRS => 10,
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    private function get_redirect_url($url)
    {
        /*
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlInfo = curl_getinfo($ch);
        while ($this->tries++ < $this->maxTries && !filter_var($curlInfo["redirect_url"], FILTER_VALIDATE_URL)) {
            $curlInfo["redirect_url"] = $this->get_redirect_url($url);
        }
        return $curlInfo["redirect_url"];
        */
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
            CURLOPT_MAXREDIRS => 10,
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
            ),
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
            'Range: bytes=0-200000'
        ];

        $options = array(
            CURLOPT_URL => $playable,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
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
        return $key;
    }

    function media_info($url)
    {
        $this->tries++;
        $url = unshorten($url, $this->enable_proxies);
        $web_page = url_get_contents($url, $this->enable_proxies);
        $data = json_decode(get_string_between($web_page, '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">', '</script>'), true);
        if (empty($data["props"]["pageProps"] ?? null)) {
            return false;
        }
        $video["source"] = "tiktok";
        $video["title"] = $data["props"]["pageProps"]["videoData"]["itemInfos"]["text"];
        if (empty($video["title"]) && isset($data["props"]["pageProps"]["shareMeta"]["title"])) {
            $video["title"] = $data["props"]["pageProps"]["shareMeta"]["title"];
        }
        if (empty($video["title"])) {
            $video["title"] = "Tiktok Video";
        }
        $thumbnail = get_string_between($web_page, 'property="og:image" content="', '"');
        if (!empty($data["props"]["pageProps"]["videoData"]["itemInfos"]["coversOrigin"] ?? "")) {
            $video["thumbnail"] = $data["props"]["pageProps"]["videoData"]["itemInfos"]["coversOrigin"][0];
        } else if (!empty($thumbnail)) {
            $video["thumbnail"] = $thumbnail;
        } else {
            $video["thumbnail"] = "https://s16.tiktokcdn.com/musical/resource/wap/static/image/logo_144c91a.png?v=2";
        }
        $video["links"] = array();
        $original_video = $data["props"]["pageProps"]["videoData"]["itemInfos"]["video"]["urls"][0];
        //$track_id = sha1($url);
        $track_id = rand(0, 4);
        $cache_file = __DIR__ . "/../storage/temp/tiktok-" . $track_id . ".mp4";
        $cache = fopen($cache_file, 'a+');
        fwrite($cache, $this->get_video($original_video));
        fclose($cache);
        $website_url = json_decode(option("general_settings"), true)["url"];
        array_push($video["links"], array(
            "url" => $website_url . "/system/storage/temp/tiktok-" . $track_id . ".mp4",
            "type" => "mp4",
            "quality" => "watermarked",
            "size" => format_size(filesize($cache_file)),
            "mute" => false
        ));
        $video_key = $this->get_key($original_video);
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
        $audio_url = $data['props']['pageProps']['videoObjectPageProps']['videoProps']['audio']['mainEntityOfPage']['@id'] ?? null;
        if (!empty($audio_url)) {
            $audio_page = url_get_contents($audio_url, $this->enable_proxies);
            $audio_data = get_string_between($audio_page, '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">', '</script>');
            $audio_data = json_decode($audio_data, true);
            if (!empty($audio_data)) {
                array_push($video["links"], array(
                    "url" => $audio_data['props']['pageProps']['musicData']['playUrl']['UrlList'][0],
                    "type" => "mp3",
                    "quality" => "128 kbps",
                    "size" => get_file_size($audio_data['props']['pageProps']['musicData']['playUrl']['UrlList'][0], $this->enable_proxies),
                    "mute" => false
                ));
            }
        }
        if (!filter_var($video["links"][0]["url"], FILTER_VALIDATE_URL)) {
            while ($this->tries++ < $this->maxTries) {
                $this->media_info($url);
            }
        }
        return $video;
    }
}