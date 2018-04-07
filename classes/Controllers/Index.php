<?php
namespace Punbb\Controllers;

use Punbb\ForumFunction as F;
use \Pimple\Container;
use Punbb\Cache;


define('FORUM_PAGE', 'index');

class Index extends Base {
    
    /**
     *
     * @var \Punbb\Data\IndexGateway
     */
    protected $gateway;
    
    
    public function __construct(Container $c)
    {
        parent::__construct(__CLASS__, $c);
    }

    function Index()
    {
        $c = $this->c;
        
        // Get list of forums and topics with new posts since last visit
        if (!$c['user']['is_guest'])
        {
            $new_topics = $this->gateway->getNewTopic($c['user']);
            $tracked_topics = F::get_tracked_topics();
        }
        else {
            $new_topics = [];
        }
        
        $forum_page['main_title'] = F::forum_htmlencode($c['config']['o_board_title']);
        $forum_page['cur_category'] = $forum_page['cat_count'] = $forum_page['item_count'] = 0;
        
        $c['breadcrumbs']->addCrumb($c['config']['o_board_title']);
        
        //var_dump($this->gateway->getForums($c['user']));exit;
        
        return ['template' => 'index', 'data' => [
            'new_topics'   => $new_topics,
            'forums'        => $this->gateway->getForums($c['user']),
            'lang_index'    => $c['lang_index'],
            'forum_stats'   => Cache::load_stats(),
            'forum_page' => $forum_page,
            'online_info'   => $this->getOnline(),
            'page'  => 'index',
        ]];
    }
    
    private function getOnline() {
        $c = $this->c;
        if ($c['config']['o_users_online'] == '1')
        {
            $Online = $c['OnlineGateway'];// new \Punbb\Data\OnlineGateway($forum_db);
            $user_online = $Online->getUserOnline();
            
            $forum_page['num_guests'] = $forum_page['num_users'] = 0;
            $users = array();
            
            foreach ($user_online as $forum_user_online)
            {
       
                if ($forum_user_online['user_id'] > 1)
                {
                    $users[] = ($c['user']['g_view_users'] == '1') ? '<a href="'.F::forum_link($c['url']['user'], $forum_user_online['user_id']).'">'.F::forum_htmlencode($forum_user_online['ident']).'</a>' : F::forum_htmlencode($forum_user_online['ident']);
                    ++$forum_page['num_users'];
                }
                else
                    ++$forum_page['num_guests'];
            }
            
            $forum_page['online_info'] = array();
            $forum_page['online_info']['guests'] = ($forum_page['num_guests'] == 0) ? $c['lang_index']['Guests none'] : sprintf((($forum_page['num_guests'] == 1) ? $c['lang_index']['Guests single'] : $c['lang_index']['Guests plural']), \Punbb\ForumFunction::forum_number_format($forum_page['num_guests']));
            $forum_page['online_info']['users'] = ($forum_page['num_users'] == 0) ? $c['lang_index']['Users none'] : sprintf((($forum_page['num_users'] == 1) ? $c['lang_index']['Users single'] : $c['lang_index']['Users plural']), \Punbb\ForumFunction::forum_number_format($forum_page['num_users']));
            
            return [
                'summary' => implode($c['lang_index']['Online stats separator'], $forum_page['online_info']),
                'users' => (!empty($users)) ? implode($c['lang_index']['Online list separator'], $users) : false
            ];
        }
            
    }
    
}