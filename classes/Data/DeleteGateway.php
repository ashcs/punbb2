<?php

namespace Punbb\Data;

use Punbb\ForumFunction;


class DeleteGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}

	
	public function getPostInfo($id, $forum_user) {
	    $query = array(
	        'SELECT'	=> 'f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies, p.posted',
	        'FROM'		=> 'posts AS p',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'topics AS t',
	                'ON'			=> 't.id=p.topic_id'
	            ),
	            array(
	                'INNER JOIN'	=> 'forums AS f',
	                'ON'			=> 'f.id=t.forum_id'
	            ),
	            array(
	                'LEFT JOIN'		=> 'forum_perms AS fp',
	                'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	            )
	        ),
	        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('dl_qr_get_post_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	}
	
	public function getPostForRedirect($id, $cur_post) {
	    $query = array(
	        'SELECT'	=> 'p.id',
	        'FROM'		=> 'posts AS p',
	        'WHERE'		=> 'p.topic_id = '.$cur_post['tid'].' AND p.id < '.$id,
	        'ORDER BY'	=> 'p.id DESC',
	        'LIMIT'		=> '1'
	    );
	    
	    ($hook = ForumFunction::get_hook('dl_post_deleted_get_prev_post_id')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	    
	}
	
}

