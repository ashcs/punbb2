<?php
/**
 * Lists the posts in the specified topic.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */

use Punbb\ForumFunction;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('vt_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);

// Load the viewtopic.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
	ForumFunction::message($lang_common['Bad request']);


// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
    if (false === ($num_posts = $c['ViewtopicGateway']->getNumPost($pid))) {
        ForumFunction::message($lang_common['Bad request']);
    }
	$_GET['p'] = ceil($num_posts / $forum_user['disp_posts']);
	$id = $c['ViewtopicGateway']->getTopicId();
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new')
{
	if (!$forum_user['is_guest'])
	{
		// We need to check if this topic has been viewed recently by the user
		$tracked_topics = ForumFunction::get_tracked_topics();
		$last_viewed = isset($tracked_topics['topics'][$id]) ? $tracked_topics['topics'][$id] : $forum_user['last_visit'];

		($hook = ForumFunction::get_hook('vt_find_new_post')) ? eval($hook) : null;

		if (($first_new_post_id = $c['ViewtopicGateway']->getFirstNewPost($id, $last_viewed)))
		{
			header('Location: '.str_replace('&amp;', '&', ForumFunction::forum_link($forum_url['post'], $first_new_post_id)));
			exit;
		}
	}

	header('Location: '.str_replace('&amp;', '&', ForumFunction::forum_link($forum_url['topic_last_post'], $id)));
	exit;
}

// If action=last, we redirect to the last post
else if ($action == 'last')
{
	if (($last_post_id = $c['ViewtopicGateway']->getLastPost($id)))
	{
		header('Location: '.str_replace('&amp;', '&', ForumFunction::forum_link($forum_url['post'], $last_post_id)));
		exit;
	}
}

if (!($cur_topic = $c['ViewtopicGateway']->getTopicInfo($id, $forum_user, $forum_config)))
{
	ForumFunction::message($lang_common['Bad request']);
}

($hook = ForumFunction::get_hook('vt_modify_topic_info')) ? eval($hook) : null;

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

// Can we or can we not post replies?
if ($cur_topic['closed'] == '0' || $forum_page['is_admmod'])
	$forum_user['may_post'] = (($cur_topic['post_replies'] == '' && $forum_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1' || $forum_page['is_admmod']) ? true : false;
else
	$forum_user['may_post'] = false;

// Add/update this topic in our list of tracked topics
if (!$forum_user['is_guest'])
{
	$tracked_topics = ForumFunction::get_tracked_topics();
	$tracked_topics['topics'][$id] = time();
	ForumFunction::set_tracked_topics($tracked_topics);
}
else {
    $tracked_topics = [];
}

// Determine the post offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);
$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), ($cur_topic['num_replies'] + 1));
$forum_page['items_info'] = ForumFunction::generate_items_info($lang_topic['Posts'], ($forum_page['start_from'] + 1), ($cur_topic['num_replies'] + 1));

($hook = ForumFunction::get_hook('vt_modify_page_details')) ? eval($hook) : null;

$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb($cur_topic['forum_name'], \Punbb\ForumFunction::forum_link($forum_url['forum'], array($cur_topic['forum_id'], \Punbb\ForumFunction::sef_friendly($cur_topic['forum_name']))));
$c['breadcrumbs']->addCrumb($cur_topic['subject']);

define('FORUM_PAGE', 'viewtopic');

echo $c['templates']->render('viewtopic', [
    'posts'   =>  $c['ViewtopicGateway']->getPosts($id, $forum_page, $forum_user),
    'lang_topic'    => $lang_topic,
    'id'            => $id,
    'pid'           => $pid,
    'cur_topic'     => $cur_topic,
    'tracked_topics'    =>$tracked_topics,
    'forum_user'    => $forum_user
]);

exit;

