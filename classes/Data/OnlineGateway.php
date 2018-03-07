<?php

namespace Punbb\Data;

class OnlineGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}
	
	public function getUserOnline() {
		$query = array(
			'SELECT'	=> 'o.user_id, o.ident',
			'FROM'		=> 'online AS o',
			'WHERE'		=> 'o.idle=0',
			'ORDER BY'	=> 'o.ident'
		);
		
		($hook = \Punbb\ForumFunction::get_hook('in_users_online_qr_get_online_info')) ? eval($hook) : null;
		$result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
		$users = array();
		
		while ($forum_user_online = $this->forum_db->fetch_assoc($result))
			$users[] = $forum_user_online;
		
		return $users;
	}
}

