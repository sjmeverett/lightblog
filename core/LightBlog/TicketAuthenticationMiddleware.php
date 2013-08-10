<?php

namespace LightBlog;

class TicketAuthenticationMiddleware extends \Slim\Middleware
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function call()
    {
        $cookie = $this->app->getCookie('ticket');
        
        if ($cookie != null)
	    {
		    //fetch the ticket from the db
		    $ticket = $this->db->tickets->findOne(array(
			    '_id' => $_COOKIE['ticket']
		    ));

		    //delete if expired
		    if ($ticket['expires'] < time())
		    {
			    $this->db->tickets->remove(array(
				    '_id' => $_COOKIE['ticket']
			    ));
		    }
		    else
		    {
			    //update if ticket has aged
			    if ($ticket['expires'] - 10 * 60 < time())
			    {
				    $ticket['expires'] = time() + 20 * 60;
				    $this->db->tickets->save($ticket);
			    }

			    //current login active
			    $this->next->call();
			    return;
		    }
	    }

	    $this->app->redirect('/login?url=' . $_SERVER['REQUEST_URI']);
    }
}
