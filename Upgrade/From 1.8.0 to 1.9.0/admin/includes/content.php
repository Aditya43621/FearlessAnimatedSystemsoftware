<?php if (isset($_SESSION["logged"]) === true) { ?>
    <div class="panel-header panel-header-sm"></div>
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="title">Contents</h5>
                        <p class="category"><a href="?view=content-create">Create a new content</a></p>
                    </div>
                    <div class="card-body">
                        <?php
                        $config = json_decode(option(), true);
                        $contents_list = database::list_contents();
                        $content_per_column = (int)ceil(count($contents_list) / 3);
                        ?>
                        <div class="row">
                            <?php
                            for ($i = 0; $i < 3; $i++) {
                                $colum_content = "";
                                for ($j = 0; $j < $content_per_column; $j++) {
                                    $k = $j + ($i * 10);
                                    $content = $contents_list[$k];
                                    $buttons = '<a target="_blank" class="btn btn-sm btn-info" href="' . $config["url"] . '/' . $content["content_slug"] . '"><i class="fas fa-link"></i></a>';
                                    $buttons .= ' <a class="btn btn-sm btn-primary" href="?view=content-edit&id=' . $content["ID"] . '"><i class="fas fa-pencil-alt"></i></a>';
                                    $buttons .= ' <a class="btn btn-sm btn-danger" href="?view=content-delete&id=' . $content["ID"] . '"><i class="fas fa-trash-alt"></i></a>';
                                    $colum_content .= sprintf("<tr><td>%s</td><td>%s</td></tr>", $content["content_title"], $buttons);
                                }
                                printf('<div class="col-4"><table class="table table-striped"><thead><tr><th>Content Title</th><th>Actions</th></tr></thead><tbody>%s</tbody></table></div>', $colum_content);
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } else {
    http_response_code(403);
} ?>