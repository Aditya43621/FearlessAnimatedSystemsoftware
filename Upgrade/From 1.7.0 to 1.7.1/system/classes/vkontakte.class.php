<?php

class vk
{
    public $enable_proxies = false;

    function url_get_contents($url)
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
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Mobile Safari/537.36"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    function media_info($url)
    {
        $page_source = url_get_contents($url);
        $embed_hash = get_string_between($page_source, '"embed_hash":"', '"');
        $video_id = get_string_between($page_source, '"vid":', ',');
        $author_id = get_string_between($page_source, '"oid":', ',');
        preg_match_all('/property="og:video" content="(.*?)"\/>/', $page_source, $video_url);
        if (!empty($video_url[1][0])) {
            $embed_url = str_replace("&amp;", "&", $video_url[1][0]);
        }
        if (($embed_hash != "" && $video_id != "" && $author_id != "") || !empty($embed_url)) {
            if (empty($embed_url)) {
                $query = array(
                    "oid" => $author_id,
                    "id" => $video_id,
                    "hash" => $embed_hash
                );
                $embed_url = "https://vk.com/video_ext.php?" . http_build_query($query);
            }
            $embed_source = $this->url_get_contents($embed_url);
            $video_title = get_string_between($embed_source, '"md_title":"', '"');
            $video["title"] = (!empty($video_title)) ? $video_title : get_string_between($page_source, "<title>", "</title>");
            $video["source"] = "vkontakte";
            $video["thumbnail"] = get_string_between($embed_source, 'poster="', '"');
            $video["duration"] = format_seconds(get_string_between($embed_source, 'data-duration="', '"'));
            preg_match_all('/<source src="(.*?)" type="video\/mp4"/', $embed_source, $streams_raw);
            $streams_raw = $streams_raw[1];
            $video["links"] = array();
            foreach ($streams_raw as $stream) {
                $parse_url = parse_url($stream);
                $url_path = $parse_url["path"];
                $type = pathinfo($url_path, PATHINFO_EXTENSION);
                $quality = get_string_between($url_path, ".", ".mp4");
                if (!empty($type)) {
                    array_push($video["links"], array(
                        "url" => $stream,
                        "type" => $type,
                        "size" => get_file_size($stream),
                        "quality" => $quality . "p",
                        "mute" => false
                    ));
                }
            }
            usort($video["links"], "sort_by_quality");
            return $video;
        } else {
            return false;
        }
    }
}