<?php

namespace Punbb\Data;

class ReportGateway {
	
	private $forum_db;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}
	
	public function isNewReport() {
		$query = array(
			'SELECT'	=> 'COUNT(r.id)',
			'FROM'		=> 'reports AS r',
			'WHERE'		=> 'r.zapped IS NULL',
		);

		($hook = \Punbb\ForumFunction::get_hook('hd_qr_get_unread_reports_count')) ? eval($hook) : null;
		$result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);

		if ($this->forum_db->result($result))
			return true;
		
		return false;
	}
}

