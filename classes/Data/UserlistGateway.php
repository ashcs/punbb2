<?php

namespace Punbb\Data;

class UserlistGateway {
	
	private $forum_db;
	
	private $where_sql;
	
	public function __construct($forum_db) {
		$this->forum_db = $forum_db;
	}

	public function getUserCount($forum_page, $forum_user)
    {
        // Create any SQL for the WHERE clause
        $where_sql = array();
        $like_command = (substr(strrchr(get_class($this->forum_db), '\\'), 1) == 'Pgsql') ? 'ILIKE' : 'LIKE';
        
        if ($forum_user['g_search_users'] == '1' && $forum_page['username'] != '')
            $where_sql[] = 'u.username ' . $like_command . ' \'' . $forum_db->escape(str_replace('*', '%', $forum_page['username'])) . '\'';
        if ($forum_page['show_group'] > - 1)
            $where_sql[] = 'u.group_id=' . $forum_page['show_group'];
        
        // Fetch user count
        $query = array(
            'SELECT' => 'COUNT(u.id)',
            'FROM' => 'users AS u',
            'WHERE' => 'u.id > 1 AND u.group_id != ' . FORUM_UNVERIFIED
        );
        
        if (! empty($where_sql))
            $query['WHERE'] .= ' AND ' . implode(' AND ', $where_sql);
        
        ($hook = \Punbb\ForumFunction::get_hook('ul_qr_get_user_count')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
        
        $this->where_sql = $where_sql;
        
        return $this->forum_db->result($result);
    }
    
    public function getUserGroups() {
        // Get the list of user groups (excluding the guest group)
        $query = array(
            'SELECT'	=> 'g.g_id, g.g_title',
            'FROM'		=> 'groups AS g',
            'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
            'ORDER BY'	=> 'g.g_id'
        );
        
        ($hook = \Punbb\ForumFunction::get_hook('ul_qr_get_groups')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or\Punbb\ ForumFunction::error(__FILE__, __LINE__);
        $groups = [];
        while (($cur_group = $this->forum_db->fetch_assoc($result)))
        {
            $groups[] = $cur_group;
        }
        
        return $groups;
    }
    
    public function getUsers($forum_page){
        // Grab the users
        $query = array(
            'SELECT'	=> 'u.id, u.username, u.title, u.num_posts, u.registered, g.g_id, g.g_user_title',
            'FROM'		=> 'users AS u',
            'JOINS'		=> array(
                array(
                    'LEFT JOIN'		=> 'groups AS g',
                    'ON'			=> 'g.g_id=u.group_id'
                )
            ),
            'WHERE'		=> 'u.id > 1 AND u.group_id != '.FORUM_UNVERIFIED,
            'ORDER BY'	=> $forum_page['sort_by'].' '.$forum_page['sort_dir'].', u.id ASC',
            'LIMIT'		=> $forum_page['start_from'].', 50'
        );
        
        if (!empty($this->where_sql))
            $query['WHERE'] .= ' AND '.implode(' AND ', $this->where_sql);
        
        ($hook = \Punbb\ForumFunction::get_hook('ul_qr_get_users')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
        
        $founded_user_datas = array();
        while ($user_data = $this->forum_db->fetch_assoc($result)) {
            $founded_user_datas[] = $user_data;
        }
         return $founded_user_datas;  
    }
}

