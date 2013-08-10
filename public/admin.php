<?php

require "setup.php";

$app->add(new \LightBlog\TicketAuthenticationMiddleware($db));

$app->get('/admin', function () use ($app)
{
    $app->render('dashboard.html');
});


$app->get('/admin/articles', function () use ($app, $db)
{
	$cursor = $db->articles
        ->find()
        ->sort(array('date' => -1));
        
    $articles = array_map(
        function ($article) {
            
            $article['date'] = date('Y-m-d H:i', $article['date']->sec);
            $article['id'] = $article['_id']->{'$id'};
            return $article;
        },
        
        array_values(iterator_to_array($cursor))
    );

	$app->render('articles.html', array('articles' => $articles));
});


$app->get('/admin/editarticle/', function () use ($app)
{
    $app->render('editarticle.html');
});


$app->post('/admin/editarticle/', function () use ($app, $db)
{
    $request = $app->request();
    $content = $request->params('content');
    $article = array();
    
    if ($request->params('import') != null)
    {
        $article['content'] = importHtml($content);
        $app->render('editarticle.html', array('article' => $article));
        return;
    }

    $article['title'] = getTitle($content);

    $url = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $article['title']);
    $url = strtolower(rtrim($url, '-'));
    $article['url'] = $url;
    
    $p = $db->articles->findOne(array('url' => $url));
    
    while ($p != null)
    {
        $article['url'] = uniqid($url);
        $p = $db->articles->findOne(array('url' => $article['url']));
    }
    
    $article['date'] = new MongoDate();
	$article['content'] = $content;
    $db->articles->save($article);
    
    $app->response()->redirect('/admin/articles');
});


$app->get('/admin/editarticle/:id', function ($id) use ($app, $db)
{
    $article = $db->articles->findOne(array('_id' => new MongoId($id)));
    
    if ($article == null)
    {
        $app->response(404);
    }
    else
    {
        $article['id'] = $article['_id']->{'$id'};
        $app->render('editarticle.html', array('article' => $article));
    }
});


$app->post('/admin/editarticle/:id', function ($id) use ($app, $db)
{
    $article = $db->articles->findOne(array('_id' => new MongoId($id)));
    
    if ($article == null)
    {
        $app->response(404);
    }
    else
    {
        $request = $app->request();
        $article['content'] = $request->params('content');
        $article['title'] = getTitle($article['content']);
        $db->articles->save($article);
        
        $app->response()->redirect('/admin/articles');
    }
});


function getTitle($content)
{
    //bit of a hack - it's easier to find headings in HTML than in markdown
    //find a heading in the first 300 characters, it's not relevant after that
    $html = \Michelf\MarkdownExtra::defaultTransform(substr($content, 0, 300));
    
    if (preg_match('/<h[0-9]>([^<]+?)<\/h[0-9]>/i', $html, $matches))
    {
        return $matches[1];
    }
    else
    {
        $snippet = preg_replace('/<[^>]+?>/', '', substr($html, 0, 300));
        return substr($snippet, 0, 40) . '...';
    }
}


function importHtml($content)
{
    //convert headers
    $content = preg_replace_callback(
        '|<h([0-9])>(.+?)</h[0-9]>|i',
        function ($matches)
        {
            return "\n" . str_repeat('#', $matches[1]) . ' ' . $matches[2];
        },
        $content
    );
    
    //convert emphasis
    $content = preg_replace(
        array('|<em>(.+?)</em>|is', '|<i>(.+?)</i>|is', '|<b>(.+?)</b>|is', '|<strong>(.+?)</strong>|is'),
        array('*$1*', '*$1*', '**$1**', '**$1**'),
        $content
    );
        
    
    //convert links
    $content = preg_replace(
        '|<a.+?href="([^"]+)".*?>(.+?)</a>|i',
        '[$2]($1)',
        $content
    );
    
    //convert images
    $content = preg_replace(
        '|\[caption.+?\]([^>]*?>).*?\[/caption\]|is',
        '$1',
        $content
    );
    
    $content = preg_replace(
        '|<img.+?title="([^"]*)".+?src="([^"]+)".+?/?>|i',
        '![$1]($2)',
        $content
    );
    
    //convert wordpress break
    $content = str_replace(
        '<!--more-->',
        '--more--',
        $content
    );
    
    //convert unordered lists
    $content = preg_replace_callback(
        '|<ul>(.*?)</ul>|is',
        function ($matches)
        {
            preg_match_all('|<li>(.*?)</li>|is', $matches[1], $items);
            unset($items[0]);
            
            return "\n" . array_reduce(
                $items[1],
                function ($result, $item)
                {
                    $result .= "\n* $item";
                    return $result;
                }
            ) . "\n";
        },
        $content
    );
    
    //convert code snippets
    $content = preg_replace_callback(
        '|<pre class="(.*?)">(.*?)</pre>|is',
        function ($matches)
        {
            if (preg_match('/first-line: ?([0-9]+)/', $matches[1], $m))
            {
                $h = '<?prettify linenums=' . $m[1] . '?>';
            }
            else
            {
                $h = '<?prettify?>';
            }
            
            $code = str_replace(array('&gt;', '&lt;'), array('>', '<'), $matches[2]);
            
            return "\n$h\n~~~~\n$code\n~~~~\n";
        },
        $content
    );
    
    return $content;
}

$app->run();

