<?php

namespace Punbb\Data;

use \Punbb\ForumFunction;

class ProfileGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}
	
	public function getUserWhoViewing($id) {
	    // Fetch info about the user whose profile we're viewing
	    $query = array(
	        'SELECT'	=> 'u.*, g.g_id, g.g_user_title, g.g_moderator',
	        'FROM'		=> 'users AS u',
	        'JOINS'		=> array(
	            array(
	                'LEFT JOIN'	=> 'groups AS g',
	                'ON'		=> 'g.g_id=u.group_id'
	            )
	        ),
	        'WHERE'		=> 'u.id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_qr_get_user_info')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->fetch_assoc($result);
	}
	
	public function setPassword($id,$new_password_hash, $drop_activate = true) {
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'password=\''.$new_password_hash.'\'',
	        'WHERE'		=> 'id='.$id
	    );
	    
	    if ($drop_activate) {
	        $query['SET'] .= ', activate_key=NULL';
	    }
	    
	    ($hook = ForumFunction::get_hook('pf_change_pass_key_qr_update_password')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setActivateEmail($id) {
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'email=activate_string, activate_string=NULL, activate_key=NULL',
	        'WHERE'		=> 'id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_email_key_qr_update_email')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function getUsersByEmail($new_email) {
	    // Check if someone else already has registered with that e-mail address
	    $query = array(
	        'SELECT'	=> 'u.id, u.username',
	        'FROM'		=> 'users AS u',
	        'WHERE'		=> 'u.email=\''.$this->forum_db->escape($new_email).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_email_normal_qr_check_email_dupe')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $dupe_list = array();
	    while ($cur_dupe = $his->forum_db->fetch_assoc($result))
	    {
	        $dupe_list[] = $cur_dupe['username'];
	    }
	    
	    return $dupe_list;
	}
	
	public function setEmail($id, $new_email) {
	    // We have no confirmed e-mail so we change e-mail right now
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'email=\''.$this->forum_db->escape($new_email).'\'',
	        'WHERE'		=> 'id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_email_key_qr_update_email')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setActivationString($id, $new_email, $new_email_key) {
	    
	    // Save new e-mail and activation key
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'activate_string=\''.$this->forum_db->escape($new_email).'\', activate_key=\''.$new_email_key.'\'',
	        'WHERE'		=> 'id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_email_normal_qr_update_email_activation')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setGroup($id, $new_group_id) {
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'group_id='.$new_group_id,
	        'WHERE'		=> 'id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_group_qr_update_group')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function getGroupModerator($new_group_id) {
	    $query = array(
	        'SELECT'	=> 'g.g_moderator',
	        'FROM'		=> 'groups AS g',
	        'WHERE'		=> 'g.g_id='.$new_group_id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_group_qr_check_new_group_mod')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    return $this->forum_db->result($result);
	}
	
	public function getModerators() {
	    // Loop through all forums
	    $query = array(
	        'SELECT'	=> 'f.id, f.moderators',
	        'FROM'		=> 'forums AS f'
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_forum_moderators_qr_get_all_forum_mods')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    $moderators = [];
	    while ($cur_forum = $this->forum_db->fetch_assoc($result)) {
	        $moderators[] = $cur_forum;
	    }
	    return $moderators;
	}
	
	public function setModerators($forum_id, $cur_moderators){
	    $query = array(
	        'UPDATE'	=> 'forums',
	        'SET'		=> 'moderators='.$cur_moderators,
	        'WHERE'		=> 'id='.$cur_forum['id']
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_forum_moderators_qr_update_forum_moderators')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setAvatarInfo($id, $avatar_type, $avatar_height, $avatar_width) {
	    // Save to DB
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> 'avatar=\''.$avatar_type.'\', avatar_height=\''.$avatar_height.'\', avatar_width=\''.$avatar_width.'\'',
	        'WHERE'		=> 'id='.$id
	    );
	    ($hook = ForumFunction::get_hook('pf_change_details_avatar_qr_update_avatar')) ? eval($hook) : null;
	    $tnis->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setUserValues($id, $new_values) {
	    // Run the update
	    $query = array(
	        'UPDATE'	=> 'users',
	        'SET'		=> implode(',', $new_values),
	        'WHERE'		=> 'id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_user')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function setUsername($id, $form, $old_username) {
	    $query = array(
	        'UPDATE'	=> 'posts',
	        'SET'		=> 'poster=\''.$this->forum_db->escape($form['username']).'\'',
	        'WHERE'		=> 'poster_id='.$id
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_posts_poster')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $query = array(
	        'UPDATE'	=> 'topics',
	        'SET'		=> 'poster=\''.$this->forum_db->escape($form['username']).'\'',
	        'WHERE'		=> 'poster=\''.$this->forum_db->escape($old_username).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_topics_poster')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $query = array(
	        'UPDATE'	=> 'topics',
	        'SET'		=> 'last_poster=\''.$this->forum_db->escape($form['username']).'\'',
	        'WHERE'		=> 'last_poster=\''.$this->forum_db->escape($old_username).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_topics_last_poster')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $query = array(
	        'UPDATE'	=> 'forums',
	        'SET'		=> 'last_poster=\''.$this->forum_db->escape($form['username']).'\'',
	        'WHERE'		=> 'last_poster=\''.$this->forum_db->escape($old_username).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_forums_last_poster')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $query = array(
	        'UPDATE'	=> 'online',
	        'SET'		=> 'ident=\''.$this->forum_db->escape($form['username']).'\'',
	        'WHERE'		=> 'ident=\''.$this->forum_db->escape($old_username).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_online_ident')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $query = array(
	        'UPDATE'	=> 'posts',
	        'SET'		=> 'edited_by=\''.$this->forum_db->escape($form['username']).'\'',
	        'WHERE'		=> 'edited_by=\''.$this->forum_db->escape($old_username).'\''
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_qr_update_posts_edited_by')) ? eval($hook) : null;
	    $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	}
	
	public function getGroups() {
	    $query = array(
	        'SELECT'	=> 'g.g_id, g.g_title',
	        'FROM'		=> 'groups AS g',
	        'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
	        'ORDER BY'	=> 'g.g_title'
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_admin_qr_get_groups')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    $groups = [];
	    while ($cur_group = $this->forum_db->fetch_assoc($result)) {
	        $groups[] = $cur_group;
	    }
	    
	    return $groups;
	}
	
	public function getCatsAndForums() {
	    $query = array(
	        'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.moderators',
	        'FROM'		=> 'categories AS c',
	        'JOINS'		=> array(
	            array(
	                'INNER JOIN'	=> 'forums AS f',
	                'ON'			=> 'c.id=f.cat_id'
	            )
	        ),
	        'WHERE'		=> 'f.redirect_url IS NULL',
	        'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
	    );
	    
	    ($hook = ForumFunction::get_hook('pf_change_details_admin_qr_get_cats_and_forums')) ? eval($hook) : null;
	    $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	    
	    $cats = [];
	    while ($cur_forum = $this->forum_db->fetch_assoc($result)) {
	        $cats[] = $cur_forum;
	    }
	    
	    return $cats;
	}
}

