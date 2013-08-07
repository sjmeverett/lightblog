<?php

require "setup.php";

$app->get('/admin', function () use ($app)
{
    $app->render('dashboard.html');
});


$app->get('/admin/articles', function () use ($app)
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

	$app->render('articles.html', array('articles' => $articles));
});


$app->post('/admin/articles', function () use ($app, $db)
{
	$request = $app->request();
	$content = $request->params('content');
	$article = array();

	//bit of a hack - it's easier to find headings in HTML than in markdown
	$html = \Michelf\MarkdownExtra::defaultTransform($content);
	preg_match('/<h[0-9]>([^<]+?)</h[0-9]>/i', $html, $matches);

	$title = $matches[1];
	$url = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $title);
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
	$article['content'] = $content;
    $db->articles->save($article);
});

$app->run();

