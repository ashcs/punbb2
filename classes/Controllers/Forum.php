<?php
namespace Punbb\Controllers;

use Pimple\Container;
use Punbb\ForumFunction as F;


define('FORUM_PAGE', 'forum');

class Forum extends Base {

    /**
     *
     * @var \Punbb\Data\ForumGateway
     */
    protected $gateway;
    
    public function __construct(Container $c) {
        parent::__construct(__CLASS__, $c);
    }

    function test($id, $p = 1)
    {
        $c = $this->c;
        
        if (! ($cur_forum = $this->gateway->getForum($id, $c['user'], $c['config']))) {
            F::message($c['lang_common']['Bad request']);
        }
        
        $forum_page['num_pages'] = ceil($cur_forum['num_topics'] / $c['user']['disp_topics']);
        $forum_page['page'] = ($p > $forum_page['num_pages']) ? 1 : $p;
        $forum_page['start_from'] = $c['user']['disp_topics'] * ($forum_page['page'] - 1);
        $forum_page['finish_at'] = min(($forum_page['start_from'] + $c['user']['disp_topics']), ($cur_forum['num_topics']));
        
        $forum_page['items_info'] = F::generate_items_info($c['lang_forum']['Topics'], ($forum_page['start_from'] + 1), $cur_forum['num_topics'], $forum_page);
        
        // Navigation links for header and page numbering for title/meta description
        if ($forum_page['page'] < $forum_page['num_pages'])
        {
            $forum_page['nav']['last'] = '<link rel="last" href="'.F::forum_sublink($c['url']['forum'], $c['url']['page'], $forum_page['num_pages'], array($id, F::sef_friendly($cur_forum['forum_name']))).'" title="'.$c['lang_common']['Page'].' '.$forum_page['num_pages'].'" />';
            $forum_page['nav']['next'] = '<link rel="next" href="'.F::forum_sublink($c['url']['forum'], $c['url']['page'], ($forum_page['page'] + 1), array($id, F::sef_friendly($cur_forum['forum_name']))).'" title="'.$c['lang_common']['Page'].' '.($forum_page['page'] + 1).'" />';
        }
        if ($forum_page['page'] > 1)
        {
            $forum_page['nav']['prev'] = '<link rel="prev" href="'.F::forum_sublink($c['url']['forum'], $c['url']['page'], ($forum_page['page'] - 1), array($id, F::sef_friendly($cur_forum['forum_name']))).'" title="'.$c['lang_common']['Page'].' '.($forum_page['page'] - 1).'" />';
            $forum_page['nav']['first'] = '<link rel="first" href="'.F::forum_link($c['url']['forum'], array($id, F::sef_friendly($cur_forum['forum_name']))).'" title="'.$c['lang_common']['Page'].' 1" />';
        }
        
        $topics_id = $this->gateway->getTopicIDs(
            $cur_forum, ['start_from' => $forum_page['start_from'], 'disp_topics' => $c['user']['disp_topics']]
        );
        
        if (!empty($topics_id))
        {
            $topics = $this->gateway->getTopics(
                $topics_id, $c['user'], $cur_forum['sort_by'], $c['config']
            );
        }
        if (! $c['user']['is_guest'])
            $tracked_topics = F::get_tracked_topics();
        else
            $tracked_topics = [];
        
        $c['breadcrumbs']->addCrumb($c['config']['o_board_title'], F::forum_link($c['url']['index']))
            ->addCrumb($cur_forum['forum_name']);
        
        // Setup main header
        $forum_page['main_title'] = '<a class="permalink" href="' . F::forum_link($c['url']['forum'], array(
            $id,
            F::sef_friendly($cur_forum['forum_name'])
        )) . '" rel="bookmark" title="' .$c['lang_forum']['Permalink forum'] . '">' . F::forum_htmlencode($cur_forum['forum_name']) . '</a>';
                
        return $c['templates']->render('viewforum', [
            'cur_forum' => $cur_forum,
            'lang_forum' =>$c['lang_forum'],
            'id' => $id,
            'tracked_topics' => $tracked_topics,
            'topics' => $topics,
            'forum_page' => $forum_page
        ]);
    }
}