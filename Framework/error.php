<?php

$template = new Template('error.html', MVT::$mvt_dir);

$context = array(
    'error' => array(
        'id' => $id,
        'name' => $name,
        'description' => $description
    )
);

$template->render($context);
