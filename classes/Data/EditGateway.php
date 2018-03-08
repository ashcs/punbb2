<?php

namespace Punbb\Data;

use Punbb\ForumFunction;

class EditGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}

	public function getPostInfo($id, $forum_user) {
	    $query = array(
	        'SELECT'	=> 'f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies',
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
	    
	    ($hook = ForumFunction::get_hook('ed_qr_get_post_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	}
	
	public function setTopicSubject($subject, $cur_post) {
	    $query = array(
	        'UPDATE'	=> 'topics',
	        'SET'		=> 'subject=\''.$this->forum_db->escape($subject).'\'',
	        'WHERE'		=> 'id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']
	    );
	    
	    ($hook = ForumFunction::get_hook('ed_qr_update_subject')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setPostMessage($id, $message, $hide_smilies, $forum_user, $silent = false ) {
	    $query = array(
	        'UPDATE'	=> 'posts',
	        'SET'		=> 'message=\''.$this->forum_db->escape($message).'\', hide_smilies=\''.$hide_smilies.'\'',
	        'WHERE'		=> 'id='.$id
	    );
	    
	    if (!$silent)
	        $query['SET'] .= ', edited='.time().', edited_by=\''.$this->forum_db->escape($forum_user['username']).'\'';
	        
        ($hook = ForumFunction::get_hook('ed_qr_update_post')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}

}

