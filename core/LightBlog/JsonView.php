<?php

namespace LightBlog;

class JsonView extends \Slim\View
{
    public function render($obj)
    {
        $app = \Slim\Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type: application/json');
        $response->body(json_encode($obj));
        $app->stop();
    }
}

