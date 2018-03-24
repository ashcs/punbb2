<?php
namespace Punbb\Controllers;

use Punbb\ForumFunction as F;
use \Pimple\Container;
use Punbb\Cache;


define('FORUM_PAGE', 'topic');

class Topic extends Base {
    
    /**
     *
     * @var \Punbb\Data\TopicGateway
     */
    protected $gateway;
    
    public function __construct(Container $c) {
        parent::__construct(__CLASS__, $c);
    }

    function Index($id, $p = 1, $pid = 0)
    {
        $c = $this->c;
      
        if (! ($cur_topic = $this->gateway->getTopicInfo($id, $c['user'], $c['config']))) {
            F::message($c['lang_common']['Bad request']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
        $forum_page['is_admmod'] = ($c['user']['g_id'] == FORUM_ADMIN || ($c['user']['g_moderator'] == '1' && array_key_exists($c['user']['username'], $mods_array))) ? true : false;
        
        // Can we or can we not post replies?
        if ($cur_topic['closed'] == '0' || $forum_page['is_admmod'])
            $forum_page['may_post'] = (($cur_topic['post_replies'] == '' && $c['user']['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1' || $forum_page['is_admmod']) ? true : false;
        else
            $forum_page['may_post'] = false;
        
        // Add/update this topic in our list of tracked topics
        if (! $c['user']['is_guest']) {
            $tracked_topics = F::get_tracked_topics();
            $tracked_topics['topics'][$id] = time();
            F::set_tracked_topics($tracked_topics);
        } else {
            $tracked_topics = [];
        }
        
        // Determine the post offset (based on $_GET['p'])
        $forum_page['num_pages'] = ceil(($cur_topic['num_replies'] + 1) / $c['user']['disp_posts']);
        $forum_page['page'] = ($p > $forum_page['num_pages']) ? 1 : $p;
        $forum_page['start_from'] = $c['user']['disp_posts'] * ($forum_page['page'] - 1);
        $forum_page['finish_at'] = min(($forum_page['start_from'] + $c['user']['disp_posts']), ($cur_topic['num_replies'] + 1));
        $forum_page['items_info'] = F::generate_items_info($c['lang_topic']['Posts'], ($forum_page['start_from'] + 1), ($cur_topic['num_replies'] + 1), $forum_page);
        
        ($hook = F::get_hook('vt_modify_page_details')) ? eval($hook) : null;
        
        $c['breadcrumbs']->addCrumb($c['config']['o_board_title'], F::forum_link($c['url']['index']));
        $c['breadcrumbs']->addCrumb($cur_topic['forum_name'],F::forum_link($c['url']['forum'], array(
            $cur_topic['forum_id'],
           F::sef_friendly($cur_topic['forum_name'])
        )));
        $c['breadcrumbs']->addCrumb($cur_topic['subject']);

        return ['template' => 'viewtopic', 'data' => [
            'posts' => $this->gateway->getPosts($id, $forum_page, $c['user']),
            'lang_topic' => $c['lang_topic'],
            'id' => $id,
            'pid' => $pid,
            'cur_topic' => $cur_topic,
            'tracked_topics' => $tracked_topics,
            'forum_user' => $c['user'],
            'forum_page' => $forum_page
        ]];
    }
    
    public function ViewByPostId($pid) {
        if (false === ($num_posts = $this->gateway->getNumPost($pid))) {
            F::message($this->c['lang_common']['Bad request']);
        }
        $p = ceil($num_posts / $this->c['user']['disp_posts']);
        $id = $this->gateway->getTopicId();
        
        return $this->Index($id, $p, $pid);
    }
}