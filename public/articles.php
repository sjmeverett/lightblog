<?php

require "setup.php";


$app->post('/articles', 'authenticate', 'json', function () use ($db, $app)
{
    $article = $app->environment()['slim.input'];

    $url = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $article['title']);
    $url = strtolower(rtrim($url, '-'));
    $article['url'] = $url;
    
    $p = $db->articles->findOne(array('url' => $url));
    
    while (!empty($p))
    {
        $r = rand(0, 1000);
        $article['url'] = $url . '-' . $r;
        $p = $db->articles->findOne(array('url' => $article['url']));
    }
    
    $article['date'] = new MongoDate();
    $db->articles->save($article);
});


$app->get('/articles/:id', 'json', function ($id) use ($db, $app)
{
    $article = $db->articles->findOne(array('_id' => new MongoId($id)));

    if (!empty($article))
    {
        $app->render($article);
    }
    else
    {
        $app->response()->status(404);
    }
});


$app->post('/articles/:id', 'authenticate', 'json', function ($id) use ($db, $app)
{
    $article = $db->articles->findOne(array('_id' => new MongoId($id)));
    
    if (!empty($article))
    {
        $data = $app->environment()['slim.input'];;
        $article['title'] = $data['title'];
        $article['content'] = $data['content'];
        $db->articles->save($article);
    }
    else
    {
        $app->response()->status(404);
    }
});


$app->get('/articles', 'json', function () use ($db, $app)
{
    $cursor = $db->articles
        ->find()
        ->sort(array('date' => -1));
        
    $articles = array_map(
        function ($article) {
            $article['date'] = date('Y-m-d H:i', $article['date']->sec);
            
            return $article;
        },
        
        array_values(iterator_to_array($cursor))
    );
        
    $app->render($articles);
});


$app->run();

