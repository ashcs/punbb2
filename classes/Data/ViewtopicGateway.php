<?php

namespace Punbb\Data;

class ViewtopicGateway {
	
	private $forum_db;
	private $topic_id;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}
	
	/**
	 * 
	 * @param unknown $id
	 * @param unknown $forum_user
	 * @param unknown $forum_config
	 * @return unknown
	 */
	public function getTopicInfo($id, $forum_user, $forum_config) {
	    // Fetch some info about the topic
	    $query = array(
	        'SELECT'	=> 't.subject, t.first_post_id, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies',
	        'FROM'		=> 'topics AS t',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'forums AS f',
	                'ON'			=> 'f.id=t.forum_id'
	            ),
	            array(
	                'LEFT JOIN'		=> 'forum_perms AS fp',
	                'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	            )
	        ),
	        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL'
	    );
	    
	    if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1')
	    {
	        $query['SELECT'] .= ', s.user_id AS is_subscribed';
	        $query['JOINS'][] = array(
	            'LEFT JOIN'	=> 'subscriptions AS s',
	            'ON'		=> '(t.id=s.topic_id AND s.user_id='.$forum_user['id'].')'
	        );
	    }
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_topic_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    return $this->forum_db->fetch_assoc($result);
	}
	
	
	/**
	 * 
	 * @param unknown $id
	 * @param unknown $forum_page
	 * @param unknown $forum_user
	 * @return array|unknown
	 */
	public function getPosts($id, $forum_page, $forum_user) {
	    // 1. Retrieve the posts ids
	    $query = array(
	        'SELECT'	=> 'p.id',
	        'FROM'		=> 'posts AS p',
	        'WHERE'		=> 'p.topic_id='.$id,
	        'ORDER BY'	=> 'p.id',
	        'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_posts_id')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    
	    $posts_id = array();
	    while ($row = $this->forum_db->fetch_assoc($result)) {
	        $posts_id[] = $row['id'];
	    }
	    
	    if (empty($posts_id)) {
	        return [];
	    }
	    
	    //2. Retrieve the posts (and their respective poster/online status) by known id`s
	    $query = array(
	        'SELECT'	=> 'u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, u.avatar, u.avatar_width, u.avatar_height, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online',
	        'FROM'		=> 'posts AS p',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'users AS u',
	                'ON'			=> 'u.id=p.poster_id'
	            ),
	            array(
	                'INNER JOIN'	=> 'groups AS g',
	                'ON'			=> 'g.g_id=u.group_id'
	            ),
	            array(
	                'LEFT JOIN'		=> 'online AS o',
	                'ON'			=> '(o.user_id=u.id AND o.user_id!=1 AND o.idle=0)'
	            ),
	        ),
	        'WHERE'		=> 'p.id IN ('.implode(',', $posts_id).')',
	        'ORDER BY'	=> 'p.id'
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_posts')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    
	    $user_data_cache = array();
	    while ($cur_post = $this->forum_db->fetch_assoc($result)) {
	        $posts[] = $cur_post;
	    }
	    
	    return $posts;
	}
	
	
	public function getTopicId() {
	    return $this->topic_id;
	}
	
	public function getNumPost($pid) {
	    $query = array(
	        'SELECT'	=> 'p.topic_id, p.posted',
	        'FROM'		=> 'posts AS p',
	        'WHERE'		=> 'p.id='.$pid
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_post_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    $topic_info = $this->forum_db->fetch_assoc($result);

	    if (!$topic_info)
	    {
	        return false;
	    }

	    $this->topic_id = $topic_info['topic_id'];
	    
	    // Determine on what page the post is located (depending on $forum_user['disp_posts'])
	    $query = array(
	        'SELECT'	=> 'COUNT(p.id)',
	        'FROM'		=> 'posts AS p',
	        'WHERE'		=> 'p.topic_id='.$topic_info['topic_id'].' AND p.posted<'.$topic_info['posted']
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_post_page')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    return $this->forum_db->result($result) + 1;
	    
	}
	
	public function getFirstNewPost($id, $last_viewed) {
	    $query = array(
	        'SELECT'	=> 'MIN(p.id)',
	        'FROM'		=> 'posts AS p',
	        'WHERE'		=> 'p.topic_id='.$id.' AND p.posted>'.$last_viewed
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_first_new_post')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	   return $this->forum_db->result($result);
	}
	
	public function getLastPost($id) {
	    $query = array(
	        'SELECT'	=> 't.last_post_id',
	        'FROM'		=> 'topics AS t',
	        'WHERE'		=> 't.id='.$id
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_last_post')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    return $this->forum_db->result($result);
	}
}

