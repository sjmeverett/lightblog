<?php

require "setup.php";

$app->get('/admin', function () use ($app)
{
    $app->render('admin.html');
});

$app->run();

