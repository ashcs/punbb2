<?php

namespace Punbb\Data;

use Punbb\ForumFunction;

class SearchGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}

	public function getCatsAndForums($forum_user) {
	    // Get the list of categories and forums
	    $query = array(
	        'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url',
	        'FROM'		=> 'categories AS c',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'forums AS f',
	                'ON'			=> 'c.id=f.cat_id'
	            ),
	            array(
	                'LEFT JOIN'		=> 'forum_perms AS fp',
	                'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	            )
	        ),
	        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL',
	        'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
	    );
	    
	    ($hook = ForumFunction::get_hook('se_qr_get_cats_and_forums')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $forums = array();
	    while ($cur_forum = $this->forum_db->fetch_assoc($result))
	    {
	        $forums[] = $cur_forum;
	    }
	    
	    return $forums;
	}
}

