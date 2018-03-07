<?php
namespace Punbb\Data;

class LoginGateway
{

    private $forum_db;

    public function __construct($forum_db)
    {
        $this->forum_db = $forum_db;
    }

    public function getLoginAttempt($form_username)
    {
        // Get user info matching login attempt
        $query = array(
            'SELECT' => 'u.id, u.group_id, u.password, u.salt',
            'FROM' => 'users AS u'
        );
        
        if (in_array(strtolower(substr(strrchr(get_class($this->forum_db), '\\'), 1)), array(
            'mysql',
            'mysqli',
            'mysql_innodb',
            'mysqli_innodb'
        )))
            $query['WHERE'] = 'username=\'' . $this->forum_db->escape($form_username) . '\'';
        else
            $query['WHERE'] = 'LOWER(username)=LOWER(\'' . $this->forum_db->escape($form_username) . '\')';
        
        ($hook = \Punbb\ForumFunction::get_hook('li_login_qr_get_login_data')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
        
        return $this->forum_db->fetch_row($result);
    }
    
    public function updateUnverified($user_id, $forum_config) {
        $query = array(
            'UPDATE'	=> 'users',
            'SET'		=> 'group_id='.$forum_config['o_default_user_group'],
            'WHERE'		=> 'id='.$user_id
        );
        
        ($hook = \Punbb\ForumFunction::get_hook('li_login_qr_update_user_group')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
    }
    
    public function removeOnlineByIdent($ident) {
        // Remove this user's guest entry from the online list
        $query = array(
            'DELETE'	=> 'online',
            'WHERE'		=> 'ident=\''.$this->forum_db->escape($ident).'\''
        );
        
        ($hook = \Punbb\ForumFunction::get_hook('li_login_qr_delete_online_user')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
    }
    
    public function removeOnlineById($id) {
        
        // Remove user from "users online" list.
        $query = array(
            'DELETE'	=> 'online',
            'WHERE'		=> 'user_id='.$id
        );
        
        ($hook = \Punbb\ForumFunction::get_hook('li_logout_qr_delete_online_user')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
    }
    
    public function updateUserLastVisit($forum_user) {
        if (isset($forum_user['logged']))
        {
            $query = array(
                'UPDATE'	=> 'users',
                'SET'		=> 'last_visit='.$forum_user['logged'],
                'WHERE'		=> 'id='.$forum_user['id']
            );
            
            ($hook = \Punbb\ForumFunction::get_hook('li_logout_qr_update_last_visit')) ? eval($hook) : null;
            $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
        }
    }
    
    public function getUserByEmail($email) {
        $users_with_email = array();
        
        // Fetch user matching $email
        $query = array(
            'SELECT'	=> 'u.id, u.group_id, u.username, u.salt, u.last_email_sent',
            'FROM'		=> 'users AS u',
            'WHERE'		=> 'u.email=\''.$this->forum_db->escape($email).'\''
        );
        
        ($hook = \Punbb\ForumFunction::get_hook('li_forgot_pass_qr_get_user_data')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
        
        while ($cur_user = $this->forum_db->fetch_assoc($result))
        {
            $users_with_email[] = $cur_user;
        }
        
        return $users_with_email;
    }
    
    public function setActivationKey($new_password_key) {
        
        $query = array(
            'UPDATE'	=> 'users',
            'SET'		=> 'activate_key=\''.$new_password_key.'\', last_email_sent = '.time(),
            'WHERE'		=> 'id='.$cur_hit['id']
        );
        
        ($hook = \Punbb\ForumFunction::get_hook('li_forgot_pass_qr_set_activate_key')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or \Punbb\ForumFunction::error(__FILE__, __LINE__);
    }
    
}

