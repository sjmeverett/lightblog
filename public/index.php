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

$app->run();