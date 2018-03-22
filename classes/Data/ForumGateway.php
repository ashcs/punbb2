<?php

namespace Punbb\Data;


class ForumGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}

	
	/**
	 * 
	 * @param unknown $id
	 * @param unknown $forum_user
	 * @return unknown
	 */
	public function getForum($id, $forum_user, $forum_config) {
	    // Fetch some info about the forum
	    $query = array(
	        'SELECT'	=> 'f.id, f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, f.forum_desc',
	        'FROM'		=> 'forums AS f',
	        'JOINS'		=> array(
	            array(
	                'LEFT JOIN'		=> 'forum_perms AS fp',
	                'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	            )
	        ),
	        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id
	    );
	    
	    if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1')
	    {
	        $query['SELECT'] .= ', fs.user_id AS is_subscribed';
	        $query['JOINS'][] = array(
	            'LEFT JOIN'	=> 'forum_subscriptions AS fs',
	            'ON'		=> '(f.id=fs.forum_id AND fs.user_id='.$forum_user['id'].')'
	        );
	    }
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vf_qr_get_forum_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	    
	}
	
	/**
	 * 
	 * @param unknown $forum
	 * @param unknown $limit
	 * @return unknown[]
	 */
	public function getTopicIDs($forum, $limit) {
	    // 1. Retrieve the topics id
	    $query = array(
	        'SELECT'	=> 't.id',
	        'FROM'		=> 'topics AS t',
	        'WHERE'		=> 't.forum_id='.$forum['id'],
	        'ORDER BY'	=> 't.sticky DESC, '.(($forum['sort_by'] == '1') ? 't.posted' : 't.last_post').' DESC',
	        'LIMIT'		=> $limit['start_from'].', '.$limit['disp_topics']
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vt_qr_get_topics_id')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    
	    $topics_id = array();
	    while ($row = $this->forum_db->fetch_assoc($result)) {
	        $topics_id[] = $row['id'];
	    }
	    return $topics_id;
	}
	
	
	/**
	 * 
	 * @param unknown $topics_id
	 * @param unknown $forum_user
	 * @param unknown $sort_by
	 * @param unknown $forum_config
	 * @return unknown[]
	 */
	public function getTopics($topics_id, $forum_user, $sort_by, $forum_config ) {
	    /*
	     * Fetch list of topics
	     * EXT DEVELOPERS
	     * If you modify SELECT of this query - than add same columns in next query (has posted) in GROUP BY
	     */
	    $query = array(
	        'SELECT'	=> 't.id, t.poster, t.subject, t.posted, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to',
	        'FROM'		=> 'topics AS t',
	        'WHERE'		=> 't.id IN ('.implode(',', $topics_id).')',
	        'ORDER BY'	=> 't.sticky DESC, '.(($sort_by == '1') ? 't.posted' : 't.last_post').' DESC',
	    );
	    
	    // With "has posted" indication
	    if (!$forum_user['is_guest'] && $forum_config['o_show_dot'] == '1')
	    {
	        $query['SELECT'] .= ', p.poster_id AS has_posted';
	        $query['JOINS'][]	= array(
	            'LEFT JOIN'		=> 'posts AS p',
	            'ON'			=> '(p.poster_id='.$forum_user['id'].' AND p.topic_id=t.id)'
	        );
	        
	        // Must have same columns as in prev SELECT
	        $query['GROUP BY'] = 't.id, t.poster, t.subject, t.posted, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id';
	        
	        ($hook = \Punbb\ForumFunction::get_hook('vf_qr_get_has_posted')) ? eval($hook) : null;
	    }
	    
	    ($hook = \Punbb\ForumFunction::get_hook('vf_qr_get_topics')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    
	    $topics = array ();
	    
	    while ($cur_topic = $this->forum_db->fetch_assoc($result))
	    {
	        $topics[] = $cur_topic;
	    }
	    
	    return $topics;
	}
}

