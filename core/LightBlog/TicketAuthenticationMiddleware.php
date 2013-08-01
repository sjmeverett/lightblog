<?php

namespace LightBlog;

class TicketAuthenticationMiddleware extends \Slim\Middleware
{
    public function call()
    {
        $cookie = $this->app->getCookie('ticket');
        
        if ($cookie != null)
	    {
		    //fetch the ticket from the db
		    $ticket = $db->tickets->findOne(array(
			    '_id' => $_COOKIE['ticket']
		    ));

		    //delete if expired
		    if ($ticket['expires'] < time())
		    {
			    $db->tickets->remove(array(
				    '_id' => $_COOKIE['ticket']
			    ));
		    }
		    else
		    {
			    //update if ticket has aged
			    if ($ticket['expires'] - 10 * 60 < time())
			    {
				    $ticket['expires'] = time() + 20 * 60;
				    $db->tickets->save($ticket);
			    }

			    //current login active
			    $this->next->call();
			    return;
		    }
	    }

	    http_response_code(401);
       	die();
    }
}
