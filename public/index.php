<?php

require "setup.php";

$app->get('/', function () use ($db, $app)
{    
    $cursor = $db->articles
        ->find()
        ->sort(array('date' => -1))
        ->limit(20);
        
    $articles = array_map(
        function ($article)
        {
            $content = $article['content'];
    
            //find a thumbnail image
            $article['img'] = false;
            $pos = strpos($content, '![');
            
            preg_match('/!\[[^\]>]*\]\(([^\)]+)\)/', $content, $matches);
            
            if (count($matches) > 0)
            {
                $article['img'] = $matches[1];
            }
            
            //get a snippet of the post content
            $pos = strpos($content, '--more--');
            
            if ($pos !== false)
            {
                $article['content'] = substr($content, 0, $pos);
            }
            else if (strlen($content) > 500)
            {
                $pos = strpos($content, ' ', 500);
                
                if ($pos !== false)
                {
                    $article['content'] = substr($content, 0, $pos) + "...";
                }
            }
            
            //transform content to html
            $article['content'] = \Michelf\MarkdownExtra::defaultTransform($article['content']);
            $article['pubdate'] = date('Y-m-d H:i', $article['date']->sec); 
            $article['published'] = time_ago($article['date']->sec);
            return $article;
        },
        
        iterator_to_array($cursor)
    );

    $app->render('index.html', array('articles' => $articles));
});


$app->get('/article/:url', function ($url) use ($db, $app)
{
    $article = $db->articles->findOne(array('url' => $url));
    
    if (empty($article))
    {
        $app->response()->status(404);
    }
    else
    {
        $article['content'] = \Michelf\MarkdownExtra::defaultTransform($article['content']);
        $article['content'] = str_replace('--more--', '', $article['content']);
        
        //michelf is not up for adding header offset to the parser, but suggested this hack instead
        $article['content'] = preg_replace(
            "!(</?h)([1-6])(>|\\s)!ie",
            "'\\1'.(\\2+1 >= 6 ? 6 : \\2+1).'\\3'",
            $article['content']);
        
        $article['pubdate'] = date('Y-m-d H:i', $article['date']->sec); 
        $article['published'] = time_ago($article['date']->sec);
        $app->render('article.html', array('article' => $article));
    }
});

$app->run();
