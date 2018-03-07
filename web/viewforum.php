<?php
/**
 * Lists the topics in the specified forum.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('vf_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);

require FORUM_ROOT.'lang/'.$forum_user['language'].'/forum.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	ForumFunction::message($lang_common['Bad request']);
	
	if (!($cur_forum = $c['ViewforumGateway']->getForum($id, $forum_user, $forum_config)))
	ForumFunction::message($lang_common['Bad request']);

($hook = ForumFunction::get_hook('vf_modify_forum_info')) ? eval($hook) : null;

// Is this a redirect forum? In that case, redirect!
if ($cur_forum['redirect_url'] != '')
{
	($hook = ForumFunction::get_hook('vf_redirect_forum_pre_redirect')) ? eval($hook) : null;

	header('Location: '.$cur_forum['redirect_url']);
	exit;
}

// Determine the topic offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil($cur_forum['num_topics'] / $forum_user['disp_topics']);
$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = $forum_user['disp_topics'] * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_topics']), ($cur_forum['num_topics']));

$forum_page['items_info'] = ForumFunction::generate_items_info($lang_forum['Topics'], ($forum_page['start_from'] + 1), $cur_forum['num_topics']);
($hook = ForumFunction::get_hook('vf_modify_page_details')) ? eval($hook) : null;


$topics_id = $c['ViewforumGateway']->getTopicIDs(
    $cur_forum, ['start_from' => $forum_page['start_from'], 'disp_topics' => $forum_user['disp_topics']]
);

// If there are topics id in this forum
if (!empty($topics_id))
{
    $topics = $c['ViewforumGateway']->getTopics($topics_id, $forum_user, $cur_forum['sort_by'], $forum_config);
}

// Get topic/forum tracking data
if (!$forum_user['is_guest'])
    $tracked_topics = ForumFunction::get_tracked_topics();
else 
    $tracked_topics = [];

$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb($cur_forum['forum_name']);

// Setup main header
$forum_page['main_title'] = '<a class="permalink" href="'.ForumFunction::forum_link($forum_url['forum'], array($id, ForumFunction::sef_friendly($cur_forum['forum_name']))).'" rel="bookmark" title="'.$lang_forum['Permalink forum'].'">'.ForumFunction::forum_htmlencode($cur_forum['forum_name']).'</a>';

define('FORUM_PAGE', 'viewforum');

echo $c['templates']->render('viewforum', [
    'cur_forum'   => $cur_forum,
    'lang_forum'    => $lang_forum,
    'id'            => $id,
    'tracked_topics'    =>$tracked_topics,
    'topics'        => $topics,
    //'forum_page'    => $forum_page
]);

exit;

