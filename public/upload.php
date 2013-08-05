<?php

require "setup.php";

$app->post('/upload', 'authenticate', function () use ($app) {
    $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    
    $file = $_FILES['file'];
    $filename = basename($file['name']);
    $filepath = $uploaddir . basename($file['name']);
    
    if (file_exists($filepath)) {
        $filename = uniqid() . $filename;
        $filepath = $uploaddir . $filename;
    }

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        echo '/uploads/' . $filename;
    }
    else {
        $app->response()->status(400);
    }
});

$app->run();

