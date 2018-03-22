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
    
    
    // Creates a new post
    public function addPost($post_info)
    {
            // Add the post
        $query = array(
            'INSERT' => 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
            'INTO' => 'posts',
            'VALUES' => '\'' . $forum_db->escape($post_info['poster']) . '\', ' . $post_info['poster_id'] . ', \'' . $forum_db->escape(static::get_remote_address()) . '\', \'' . $forum_db->escape($post_info['message']) . '\', ' . $post_info['hide_smilies'] . ', ' . $post_info['posted'] . ', ' . $post_info['topic_id']
        );
        
        // If it's a guest post, there might be an e-mail address we need to include
        if ($post_info['is_guest'] && $post_info['poster_email'] !== null) {
            $query['INSERT'] .= ', poster_email';
            $query['VALUES'] .= ', \'' . $forum_db->escape($post_info['poster_email']) . '\'';
        }
        
        ($hook = static::get_hook('fn_add_post_qr_add_post')) ? eval($hook) : null;
        $forum_db->query_build($query) or static::error(__FILE__, __LINE__);
        $new_pid = $forum_db->insert_id();
        
        if (! $post_info['is_guest']) {
            // Subscribe or unsubscribe?
            if ($post_info['subscr_action'] == 1) {
                $query = array(
                    'INSERT' => 'user_id, topic_id',
                    'INTO' => 'subscriptions',
                    'VALUES' => $post_info['poster_id'] . ' ,' . $post_info['topic_id']
                );
                
                ($hook = static::get_hook('fn_add_post_qr_add_subscription')) ? eval($hook) : null;
                $forum_db->query_build($query) or static::error(__FILE__, __LINE__);
            } else if ($post_info['subscr_action'] == 2) {
                $query = array(
                    'DELETE' => 'subscriptions',
                    'WHERE' => 'topic_id=' . $post_info['topic_id'] . ' AND user_id=' . $post_info['poster_id']
                );
                
                ($hook = static::get_hook('fn_add_post_qr_delete_subscription')) ? eval($hook) : null;
                $forum_db->query_build($query) or static::error(__FILE__, __LINE__);
            }
        }
        
        // Count number of replies in the topic
        $query = array(
            'SELECT' => 'COUNT(p.id)',
            'FROM' => 'posts AS p',
            'WHERE' => 'p.topic_id=' . $post_info['topic_id']
        );
        
        ($hook = static::get_hook('fn_add_post_qr_get_topic_reply_count')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or static::error(__FILE__, __LINE__);
        $num_replies = $forum_db->result($result, 0) - 1;
        
        // Update topic
        $query = array(
            'UPDATE' => 'topics',
            'SET' => 'num_replies=' . $num_replies . ', last_post=' . $post_info['posted'] . ', last_post_id=' . $new_pid . ', last_poster=\'' . $forum_db->escape($post_info['poster']) . '\'',
            'WHERE' => 'id=' . $post_info['topic_id']
        );
        
        ($hook = static::get_hook('fn_add_post_qr_update_topic')) ? eval($hook) : null;
        $forum_db->query_build($query) or static::error(__FILE__, __LINE__);
        
        static::sync_forum($post_info['forum_id']);
        
        if (! defined('FORUM_SEARCH_IDX_static functionS_LOADED'))
            require FORUM_ROOT . 'include/search_idx.php';
        
        update_search_index('post', $new_pid, $post_info['message']);
        
        static::send_subscriptions($post_info, $new_pid);
        
        // Increment user's post count & last post time
        if (isset($post_info['update_user'])) {
            if ($post_info['is_guest']) {
                $query = array(
                    'UPDATE' => 'online',
                    'SET' => 'last_post=' . $post_info['posted'],
                    'WHERE' => 'ident=\'' . $forum_db->escape(static::get_remote_address()) . '\''
                );
            } else {
                $query = array(
                    'UPDATE' => 'users',
                    'SET' => 'num_posts=num_posts+1, last_post=' . $post_info['posted'],
                    'WHERE' => 'id=' . $post_info['poster_id']
                );
            }
            
            ($hook = static::get_hook('fn_add_post_qr_update_last_post')) ? eval($hook) : null;
            $forum_db->query_build($query) or static::error(__FILE__, __LINE__);
        }
        
        // If the posting user is logged in update his/her unread indicator
        if (! $post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread']) {
            $tracked_topics = static::get_tracked_topics();
            $tracked_topics['topics'][$post_info['topic_id']] = time();
            static::set_tracked_topics($tracked_topics);
        }

        return $new_pid;
    }
    
    
}

