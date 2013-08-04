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
            $pos = strpos($content, '~~~~');
            
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
            $article['content'] = \Michelf\Markdown::defaultTransform($article['content']);
            
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
        $parser = new \Michelf\Markdown();
        $parser->header_offset = 1;
        $article['content'] = $parser->transform($article['content']);
        $article['content'] = str_replace('~~~~', '', $article['content']);
        $app->render('article.html', array('article' => $article));
    }
});

$app->run();
