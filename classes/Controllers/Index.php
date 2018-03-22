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
    
    
    public function __construct(Container $c) {
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
        
        return $c['templates']->render('index', [
            'new_topics'   => $new_topics,
            'forums'        => $this->gateway->getForums($c['user']),
            'lang_index'    => $c['lang_index'],
            'forum_stats'   => Cache::load_stats(),
            'forum_page' => $forum_page
        ]);
    }
}