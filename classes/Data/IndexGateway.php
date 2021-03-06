<?php

namespace Punbb\Data;

class IndexGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}
	
	/**
	 * 
	 * @param unknown $forum_user
	 * @return array|unknown
	 */
	public function getNewTopic($forum_user) {
		$query = array(
			'SELECT'	=> 't.forum_id, t.id, t.last_post',
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
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.last_post>'.$forum_user['last_visit'].' AND t.moved_to IS NULL'
		);

		($hook = \Punbb\ForumFunction::get_hook('in_qr_get_new_topics')) ? eval($hook) : null;
		$result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);

		$new_topics = array();
		while ($cur_topic = $this->forum_db->fetch_assoc($result))
			$new_topics[$cur_topic['forum_id']][$cur_topic['id']] = $cur_topic['last_post'];

		return $new_topics;
	}
	
	/**
	 * 
	 * @param unknown $forum_user
	 * @return unknown
	 */
	public function getForums($forum_user) {
	    // Print the categories and forums
	    $query = array(
	        'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster',
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
	        'WHERE'		=> 'fp.read_forum IS NULL OR fp.read_forum=1',
	        'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
	    );
	    
	    ($hook = \Punbb\ForumFunction::get_hook('in_qr_get_cats_and_forums')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
	    
	    while ($cur_forum = $this->forum_db->fetch_assoc($result))
	        $forums[] = $cur_forum;
	        
	        return $forums;
	}
}

