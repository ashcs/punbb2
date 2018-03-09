<?php

namespace Punbb\Data;

use Punbb\ForumFunction;

class MiscGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}


	public function setMarkread($forum_user) {
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'last_visit='.$forum_user['logged'],
	        'WHERE'		=> 'id='.$forum_user['id']
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_markread_qr_update_last_visit')) ? eval($hook) : null;
	    $thid->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}

	public function getForumName($fid, $forum_user) {
	    $query = array(
	        'SELECT'	=> 'f.forum_name',
	        'FROM'		=> 'forums AS f',
	        'JOINS'		=> array(
	            array(
	                'LEFT JOIN'		=> 'forum_perms AS fp',
	                'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	            )
	        ),
	        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_markforumread_qr_get_forum_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    return $this->forum_db->result($result);
	}
	
	public function getRecipientInfo($recipient_id) {
	    $query = array(
	        'SELECT'	=> 'u.username, u.email, u.email_setting',
	        'FROM'		=> 'users AS u',
	        'WHERE'		=> 'u.id='.$recipient_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_email_qr_get_form_email_data')) ? eval($hook) : null;
	    
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    return $this->forum_db->fetch_assoc($result);
	    
	}
	
	public function setLastEmailSent($forum_user) {
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'last_email_sent='.time(),
	        'WHERE'		=> 'id='.$forum_user['id'],
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_email_qr_update_last_email_sent')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function getReportedTopicInfo($post_id) {
	    $query = array(
	        'SELECT'	=> 't.id, t.subject, t.forum_id',
	        'FROM'		=> 'posts AS p',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'topics AS t',
	                'ON'			=> 't.id=p.topic_id'
	            )
	        ),
	        'WHERE'		=> 'p.id='.$post_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_report_qr_get_topic_data')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	}
	
	public function insertReport($post_id, $topic_info, $forum_user, $reason) {
	    $query = array(
	        'INSERT'	=> 'post_id, topic_id, forum_id, reported_by, created, message',
	        'INTO'		=> 'reports',
	        'VALUES'	=> $post_id.', '.$topic_info['id'].', '.$topic_info['forum_id'].', '.$forum_user['id'].', '.time().', \''.$this->forum_db->escape($reason).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_report_add_report')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	}
	
	public function getTopicSubject($topic_id, $forum_user) {
	    $query = array(
	        'SELECT'	=> 'subject',
	        'FROM'		=> 'topics AS t',
	        'JOINS'		=> array(
	            array(
	                'LEFT JOIN'		=> 'forum_perms AS fp',
	                'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
	            )
	        ),
	        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$topic_id.' AND t.moved_to IS NULL'
	    );
	    ($hook = ForumFunction::get_hook('mi_subscribe_qr_topic_exists')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->result($result);
	}
	
	public function getTopicSubscrCount($topic_id, $forum_user) {
	    $query = array(
	        'SELECT'	=> 'COUNT(s.user_id)',
	        'FROM'		=> 'subscriptions AS s',
	        'WHERE'		=> 'user_id='.$forum_user['id'].' AND topic_id='.$topic_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_subscribe_qr_check_subscribed')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    	    
	    return $this->forum_db->result($result);
	}
	
	public function setTopicSubscription($topic_id, $forum_user) {
	    $query = array(
	        'INSERT'	=> 'user_id, topic_id',
	        'INTO'		=> 'subscriptions',
	        'VALUES'	=> $forum_user['id'].' ,'.$topic_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_subscribe_add_subscription')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function getSubscribedSubject($topic_id, $forum_user) {
	    $query = array(
	        'SELECT'	=> 't.subject',
	        'FROM'		=> 'topics AS t',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'subscriptions AS s',
	                'ON'			=> 's.user_id='.$forum_user['id'].' AND s.topic_id=t.id'
	            )
	        ),
	        'WHERE'		=> 't.id='.$topic_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_unsubscribe_qr_check_subscribed')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->result($result);
	}
	
	public function deleteTopicSubscribtion($topic_id, $forum_user) {
	    $query = array(
	        'DELETE'	=> 'subscriptions',
	        'WHERE'		=> 'user_id='.$forum_user['id'].' AND topic_id='.$topic_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_unsubscribe_qr_delete_subscription')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function getForumSubscrCount($forum_id, $forum_user) {
	    $query = array(
	        'SELECT'	=> 'COUNT(fs.user_id)',
	        'FROM'		=> 'forum_subscriptions AS fs',
	        'WHERE'		=> 'user_id='.$forum_user['id'].' AND forum_id='.$forum_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_forum_subscribe_qr_check_subscribed')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->result($result);
	}
	
	public function setForumSubscription($forum_id, $forum_user) {
	    $query = array(
	        'INSERT'	=> 'user_id, forum_id',
	        'INTO'		=> 'forum_subscriptions',
	        'VALUES'	=> $forum_user['id'].' ,'.$forum_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_forum_subscribe_add_subscription')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function deleteForumSubscription($forum_id, $forum_user) {
	    $query = array(
	        'DELETE'	=> 'forum_subscriptions',
	        'WHERE'		=> 'user_id='.$forum_user['id'].' AND forum_id='.$forum_id
	    );
	    
	    ($hook = ForumFunction::get_hook('mi_unsubscribe_qr_delete_subscription')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
}

