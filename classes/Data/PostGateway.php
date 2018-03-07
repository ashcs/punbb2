<?php

namespace Punbb\Data;

use \Punbb\ForumFunction;

class PostGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}
	
	public function getPostingInfo($tid, $fid, $forum_user) {
	    // Fetch some info about the topic and/or the forum
	    if ($tid)
	    {
	        $query = array(
	            'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.subject, t.closed, s.user_id AS is_subscribed',
	            'FROM'		=> 'topics AS t',
	            'JOINS'		=> array(
	                array(
	                    'INNER JOIN'	=> 'forums AS f',
	                    'ON'			=> 'f.id=t.forum_id'
	                ),
	                array(
	                    'LEFT JOIN'		=> 'forum_perms AS fp',
	                    'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	                ),
	                array(
	                    'LEFT JOIN'		=> 'subscriptions AS s',
	                    'ON'			=> '(t.id=s.topic_id AND s.user_id='.$forum_user['id'].')'
	                )
	            ),
	            'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid
	        );
	        
	        ($hook = ForumFunction::get_hook('po_qr_get_topic_forum_info')) ? eval($hook) : null;
	    }
	    else
	    {
	        $query = array(
	            'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics',
	            'FROM'		=> 'forums AS f',
	            'JOINS'		=> array(
	                array(
	                    'LEFT JOIN'		=> 'forum_perms AS fp',
	                    'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
	                )
	            ),
	            'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid
	        );
	        
	        ($hook = ForumFunction::get_hook('po_qr_get_forum_info')) ? eval($hook) : null;
	    }
	    
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	}
	
	public function getQuoteAndPoster($qid, $tid) {
	    // Get the quote and quote poster
	    $query = array(
	        'SELECT'	=> 'p.poster, p.message',
	        'FROM'		=> 'posts AS p',
	        'WHERE'		=> 'id='.$qid.' AND topic_id='.$tid
	    );
	    
	    ($hook = ForumFunction::get_hook('po_qr_get_quote')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	}
	
	public function getAmountPost($tid) {
	    // Get the amount of posts in the topic
	    $query = array(
	        'SELECT' => 'count(p.id)',
	        'FROM' => 'posts AS p',
	        'WHERE' => 'topic_id=' . $tid
	    );
	    
	    ($hook = ForumFunction::get_hook('po_topic_review_qr_get_post_count')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    return $this->forum_db->result($result, 0);
	    
	}

	public function getTopicPreview($tid, $forum_config) {
        // Get posts to display in topic review
        $query = array(
            'SELECT' => 'p.id, p.poster, p.message, p.hide_smilies, p.posted',
            'FROM' => 'posts AS p',
            'WHERE' => 'topic_id=' . $tid,
            'ORDER BY' => 'id DESC',
            'LIMIT' => $forum_config['o_topic_review']
        );
        
        ($hook = ForumFunction::get_hook('po_topic_review_qr_get_topic_review_posts')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        $posts = array();
        while ($cur_post = $this->forum_db->fetch_assoc($result)) {
            $posts[] = $cur_post;
        }
        
        return $posts;
    }
}

