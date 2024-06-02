<div class="main main-raised" id="download_area">
    <div class="container container-padding">
        <?php if (isset($template_config["ads"]) == "true") { ?>
            <div class="ad text-center">
                <?php option("ads.2", true); ?>
            </div>
        <?php } ?>
        <div id="alert"></div>
        <div class="row" id="links"></div>
        <?php
        $content = content("home");
        echo $content["content_text"];
        ?>
        <div class="section text-center">
            <div class="row">
                <?php include(__DIR__ . "/about.php") ?>
            </div>
        </div>
        <div class="section text-center pb-4">
            <?php if (isset($template_config["latest-downloads"]) == "true") { ?>
                <h2 class="title"><?php echo $lang["latest-downloads"]; ?></h2>
                <?php
                $downloads_list = database::list_downloads(6);
                $temp_array = array();
                $items = "";
                for ($i = 0; $i < count($downloads_list); $i++) {
                    $meta = json_decode($downloads_list[$i]["download_meta"], true);
                    if (!in_array($meta['thumbnail'], $temp_array)) {
                        $items .= '<div class="col-2"><img class="img-thumbnail" src="' . $meta['thumbnail'] . '" alt="slide ' . $i . '" onerror="this.src=\'https://via.placeholder.com/200\';"><a href="' . $meta["video_url"] . '" rel="nofollow">' . $meta['title'] . '</a></div>';
                        array_push($temp_array, $meta['thumbnail']);
                    }
                }
                ?>
                <div class="row justify-content-center">
                    <?php echo $items; ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>