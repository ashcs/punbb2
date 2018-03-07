<?php
/**
 * Provides various mass-moderation tools to moderators.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');

require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('mr_start')) ? eval($hook) : null;

// Load the misc.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/misc.php';


// This particular function doesn't require forum-based moderator access. It can be used
// by all moderators and admins.
if (isset($_GET['get_host']))
{
	if (!$forum_user['is_admmod'])
		ForumFunction::message($lang_common['No permission']);

	$_get_host = $_GET['get_host'];
	if (!is_string($_get_host))
		ForumFunction::message($lang_common['Bad request']);

	($hook = ForumFunction::get_hook('mr_view_ip_selected')) ? eval($hook) : null;

	// Is get_host an IP address or a post ID?
	if (preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $_get_host) || preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/', $_get_host))
		$ip = $_get_host;
	else
	{
		$get_host = intval($_get_host);
		if ($get_host < 1)
			ForumFunction::message($lang_common['Bad request']);

		if (!($ip = $c['ModerateGateway']->getPosterIp($get_host)))
			ForumFunction::message($lang_common['Bad request']);
	}

	($hook = ForumFunction::get_hook('mr_view_ip_pre_output')) ? eval($hook) : null;

	ForumFunction::message(sprintf($lang_misc['Hostname lookup'], $ip, @gethostbyaddr($ip), '<a href="'.ForumFunction::forum_link($forum_url['admin_users']).'?show_users='.$ip.'">'.$lang_misc['Show more users'].'</a>'));
}


// All other functions require moderator/admin access
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($fid < 1)
	ForumFunction::message($lang_common['Bad request']);

if (!($cur_forum = $c['ModerateGateway']->getModeratingForum($fid, $forum_user)))
	ForumFunction::message($lang_common['Bad request']);

// Make sure we're not trying to moderate a redirect forum
if ($cur_forum['redirect_url'] != '')
	ForumFunction::message($lang_common['Bad request']);

// Setup the array of moderators
$mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

($hook = ForumFunction::get_hook('mr_pre_permission_check')) ? eval($hook) : null;

if ($forum_user['g_id'] != FORUM_ADMIN && ($forum_user['g_moderator'] != '1' || !array_key_exists($forum_user['username'], $mods_array)))
	ForumFunction::message($lang_common['No permission']);

// Get topic/forum tracking data
if (!$forum_user['is_guest'])
	$tracked_topics = ForumFunction::get_tracked_topics();


// Did someone click a cancel button?
if (isset($_POST['cancel']))
	ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))), $lang_common['Cancel redirect']);

// All topic moderation features require a topic id in GET
if (isset($_GET['tid']))
{
	($hook = ForumFunction::get_hook('mr_post_actions_selected')) ? eval($hook) : null;

	$tid = intval($_GET['tid']);
	if ($tid < 1)
		ForumFunction::message($lang_common['Bad request']);

	if (!($cur_topic = $c['ModerateGateway']->getTopicInfo($tid)))
		ForumFunction::message($lang_common['Bad request']);

	// User pressed the cancel button
	if (isset($_POST['delete_posts_cancel']))
		ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject']))), $lang_common['Cancel redirect']);

	// Delete one or more posts
	if (isset($_POST['delete_posts']) || isset($_POST['delete_posts_comply']))
	{
		($hook = ForumFunction::get_hook('mr_delete_posts_form_submitted')) ? eval($hook) : null;

		$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
		$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

		if (empty($posts))
			ForumFunction::message($lang_misc['No posts selected']);

		if (isset($_POST['delete_posts_comply']))
		{
			if (!isset($_POST['req_confirm']))
				ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject']))), $lang_common['No confirm redirect']);

			($hook = ForumFunction::get_hook('mr_confirm_delete_posts_form_submitted')) ? eval($hook) : null;

			// Verify that the post IDs are valid
			if ($c['ModerateGateway']->getPostCount($posts, $cur_topic, $tid) != count($posts))
				ForumFunction::message($lang_common['Bad request']);

			$c['ModerateGateway']->deletePosts($posts);

			if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/search_idx.php';

			strip_search_index($posts);

			ForumFunction::sync_topic($tid);
			ForumFunction::sync_forum($fid);

			$forum_flash->add_info($lang_misc['Delete posts redirect']);

			($hook = ForumFunction::get_hook('mr_confirm_delete_posts_pre_redirect')) ? eval($hook) : null;

			ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject']))), $lang_misc['Delete posts redirect']);
		}

		// Setup form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_topic'], array($fid, $tid));

		$forum_page['hidden_fields'] = array(
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />',
			'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />'
		);

		// Setup breadcrumbs
		$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
		$c['breadcrumbs']->addCrumb($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))));
		$c['breadcrumbs']->addCrumb($cur_topic['subject'], ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject']))));
		$c['breadcrumbs']->addCrumb($lang_misc['Delete posts']);

		($hook = ForumFunction::get_hook('mr_confirm_delete_posts_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'dialogue');
		
		echo $c['templates']->render('moderate/delete-dialogue', [
		    'lang_misc'    => $lang_misc,
		]);
		
		exit;
	}
	else if (isset($_POST['split_posts']) || isset($_POST['split_posts_comply']))
	{
		($hook = ForumFunction::get_hook('mr_split_posts_form_submitted')) ? eval($hook) : null;

		$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
		$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

		if (empty($posts))
			ForumFunction::message($lang_misc['No posts selected']);

		if (isset($_POST['split_posts_comply']))
		{
			if (!isset($_POST['req_confirm']))
				ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject']))), $lang_common['No confirm redirect']);

			// Load the post.php language file
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';

			($hook = ForumFunction::get_hook('mr_confirm_split_posts_form_submitted')) ? eval($hook) : null;

			if ($c['ModerateGateway']->getPostCount($posts, $cur_topic, $tid) != count($posts))
				ForumFunction::message($lang_common['Bad request']);

			$new_subject = isset($_POST['new_subject']) ? ForumFunction::forum_trim($_POST['new_subject']) : '';

			if ($new_subject == '')
				ForumFunction::message($lang_post['No subject']);
			else if (utf8_strlen($new_subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
				ForumFunction::message(sprintf($lang_post['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH));

			$c['ModerateGateway']->splitPosts($posts, $new_subject, $fid);

			// Sync last post data for the old topic, the new topic, and the forum itself
			ForumFunction::sync_topic($new_tid);
			ForumFunction::sync_topic($tid);
			ForumFunction::sync_forum($fid);

			$forum_flash->add_info($lang_misc['Split posts redirect']);

			($hook = ForumFunction::get_hook('mr_confirm_split_posts_pre_redirect')) ? eval($hook) : null;

			ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($new_tid, ForumFunction::sef_friendly($new_subject))), $lang_misc['Split posts redirect']);
		}

		// Setup form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_topic'], array($fid, $tid));

		$forum_page['hidden_fields'] = array(
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />',
			'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />'
		);

		$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
		$c['breadcrumbs']->addCrumb($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))));
		$c['breadcrumbs']->addCrumb($cur_topic['subject'], ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject']))));
		$c['breadcrumbs']->addCrumb($lang_misc['Split posts']);

		($hook = ForumFunction::get_hook('mr_confirm_split_posts_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'dialogue');
		
		echo $c['templates']->render('moderate/split-dialogue', [
		    'lang_misc'    => $lang_misc,
		]);
		
		exit;
	}


	// Show the moderate topic view

	// Load the viewtopic.php language file
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';

	// Used to disable the Split and Delete buttons if there are no replies to this topic
	$forum_page['button_status'] = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';


	// Determine the post offset (based on $_GET['p'])
	$forum_page['num_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);
	$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : intval($_GET['p']);
	$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);
	$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), ($cur_topic['num_replies'] + 1));
	$forum_page['items_info'] = ForumFunction::generate_items_info($lang_misc['Posts'], ($forum_page['start_from'] + 1), ($cur_topic['num_replies'] + 1));

	// Generate paging links
	$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.ForumFunction::paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['moderate_topic'], $lang_common['Paging separator'], array($fid, $tid)).'</p>';

	// Navigation links for header and page numbering for title/meta description
	if ($forum_page['page'] < $forum_page['num_pages'])
	{
		$forum_page['nav']['last'] = '<link rel="last" href="'.ForumFunction::forum_sublink($forum_url['moderate_topic'], $forum_url['page'], $forum_page['num_pages'], array($fid, $tid)).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
		$forum_page['nav']['next'] = '<link rel="next" href="'.ForumFunction::forum_sublink($forum_url['moderate_topic'], $forum_url['page'], ($forum_page['page'] + 1), array($fid, $tid)).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
	}
	if ($forum_page['page'] > 1)
	{
		$forum_page['nav']['prev'] = '<link rel="prev" href="'.ForumFunction::forum_sublink($forum_url['moderate_topic'], $forum_url['page'], ($forum_page['page'] - 1), array($fid, $tid)).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
		$forum_page['nav']['first'] = '<link rel="first" href="'.ForumFunction::forum_link($forum_url['moderate_topic'], array($fid, $tid)).'" title="'.$lang_common['Page'].' 1" />';
	}

	if ($forum_config['o_censoring'] == '1')
		$cur_topic['subject'] = ForumFunction::censor_words($cur_topic['subject']);

	// Setup form
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_topic'], array($fid, $tid));

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index'])),
		array($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name'])))),
		array($cur_topic['subject'], ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_topic['subject'])))),
		$lang_topic['Moderate topic']
	);

	// Setup main heading
	$forum_page['main_title'] = sprintf($lang_misc['Moderate topic head'], ForumFunction::forum_htmlencode($cur_topic['subject']));

	$forum_page['main_head_options']['select_all'] = '<span '.(empty($forum_page['main_head_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-post-actions-form">'.$lang_misc['Select all'].'</span></span>';
	$forum_page['main_foot_options']['select_all'] = '<span '.(empty($forum_page['main_foot_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-post-actions-form">'.$lang_misc['Select all'].'</span></span>';

	if ($forum_page['num_pages'] > 1)
		$forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);

	($hook = ForumFunction::get_hook('mr_post_actions_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'modtopic');
	// Init JS helper for select-all
	$forum_loader->add_js('PUNBB.common.addDOMReadyEvent(PUNBB.common.initToggleCheckboxes);', array('type' => 'inline'));
	
	echo $c['templates']->render('moderate', [
	    'lang_misc'    => $lang_misc,
	    'posts'        => $c['ModerateGateway']->getPostFromTopic($tid, $forum_page, $forum_user),
	    'cur_topic'    => $cur_topic,
	    'lang_topic'   => $lang_topic,
	]);
	
	exit;
}


// Move one or more topics
if (isset($_REQUEST['move_topics']) || isset($_POST['move_topics_to']))
{
	if (isset($_POST['move_topics_to']))
	{
		($hook = ForumFunction::get_hook('mr_confirm_move_topics_form_submitted')) ? eval($hook) : null;

		$topics = isset($_POST['topics']) && !empty($_POST['topics']) ? explode(',', $_POST['topics']) : array();
		$topics = array_map('intval', $topics);

		$move_to_forum = isset($_POST['move_to_forum']) ? intval($_POST['move_to_forum']) : 0;
		if (empty($topics) || $move_to_forum < 1)
			ForumFunction::message($lang_common['Bad request']);

		if (!($move_to_forum_name = $c['ModerateGateway']->moveTopics($move_to_forum, $topics, $fid))) {
		    ForumFunction::message($lang_common['Bad request']);
		}

		// Should we create redirect topics?
		if (isset($_POST['with_redirect']))
		{
			foreach ($topics as $cur_topic)
			{
			    $c['ModerateGateway']->setRedirect($cur_topic, $fid);
			}
		}

		ForumFunction::sync_forum($fid);			// Synchronize the forum FROM which the topic was moved
		ForumFunction::sync_forum($move_to_forum);	// Synchronize the forum TO which the topic was moved

		$forum_page['redirect_msg'] = (count($topics) > 1) ? $lang_misc['Move topics redirect'] : $lang_misc['Move topic redirect'];

		$forum_flash->add_info($forum_page['redirect_msg']);

		($hook = ForumFunction::get_hook('mr_confirm_move_topics_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($move_to_forum, ForumFunction::sef_friendly($move_to_forum_name))), $forum_page['redirect_msg']);
	}

	if (isset($_POST['move_topics']))
	{
		$topics = isset($_POST['topics']) && is_array($_POST['topics']) ? $_POST['topics'] : array();
		$topics = array_map('intval', $topics);

		if (empty($topics))
			ForumFunction::message($lang_misc['No topics selected']);

		if (count($topics) == 1)
		{
			$topics = $topics[0];
			$action = 'single';
		}
		else
			$action = 'multiple';
	}
	else
	{
		$topics = intval($_GET['move_topics']);
		if ($topics < 1)
			ForumFunction::message($lang_common['Bad request']);

		$action = 'single';
	}
	if ($action == 'single')
	{
		if (!($subject = $c['ModerateGateway']->getTopicSubject($topics)))
			ForumFunction::message($lang_common['Bad request']);
	}

	if (empty($forum_list = $c['ModerateGateway']->getForumsToMove($fid, $forum_user)))
	{
		ForumFunction::message($lang_misc['Nowhere to move']);
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_forum'], $fid);

	$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />',
		'topics'		=> '<input type="hidden" name="topics" value="'.($action == 'single' ? $topics : implode(',', $topics)).'" />'
	);

	// Setup breadcrumbs
	$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
	$c['breadcrumbs']->addCrumb($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))));
	if ($action == 'single')
        $c['breadcrumbs']->addCrumb($subject, ForumFunction::forum_link($forum_url['topic'], array($topics, ForumFunction::sef_friendly($subject))));
    else
        $c['breadcrumbs']->addCrumb($lang_misc['Moderate forum'], ForumFunction::forum_link($forum_url['moderate_forum'], $fid));
    $c['breadcrumbs']->addCrumb(($action == 'single') ? $lang_misc['Move topic'] : $lang_misc['Move topics']);
	
	//Setup main heading
    $forum_page['main_title'] = (($action == 'single') ? $lang_misc['Move topic'] : $lang_misc['Move topics']) .' '.$lang_misc['To new forum'];

	($hook = ForumFunction::get_hook('mr_move_topics_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	
	echo $c['templates']->render('moderate/move-dialogue', [
	    'lang_misc'    => $lang_misc,
	    'forum_list'   => $forum_list,
	    'action'       => $action,
	]);
	
	exit;
}


// Merge topics
else if (isset($_POST['merge_topics']) || isset($_POST['merge_topics_comply']))
{
	$topics = isset($_POST['topics']) && !empty($_POST['topics']) ? $_POST['topics'] : array();
	$topics = array_map('intval', (is_array($topics) ? $topics : explode(',', $topics)));

	if (empty($topics))
		ForumFunction::message($lang_misc['No topics selected']);

	if (count($topics) == 1)
		ForumFunction::message($lang_misc['Merge error']);

	if (isset($_POST['merge_topics_comply']))
	{
		($hook = ForumFunction::get_hook('mr_confirm_merge_topics_form_submitted')) ? eval($hook) : null;

		if (!($merge_to_tid = $c['ModerateGateway']->mergeTopics($topics, $fid, isset($_POST['with_redirect'])))) 
		    ForumFunction::message($lang_common['Bad request']);
		
		// Synchronize the topic we merged to and the forum where the topics were merged
		ForumFunction::sync_topic($merge_to_tid);
		ForumFunction::sync_forum($fid);

		$forum_flash->add_info($lang_misc['Merge topics redirect']);

		($hook = ForumFunction::get_hook('mr_confirm_merge_topics_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))), $lang_misc['Merge topics redirect']);
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_forum'], $fid);

	$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />',
		'topics'		=> '<input type="hidden" name="topics" value="'.implode(',', $topics).'" />'
	);

	// Setup breadcrumbs
	$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
	$c['breadcrumbs']->addCrumb($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))));
	$c['breadcrumbs']->addCrumb($lang_misc['Moderate forum'], ForumFunction::forum_link($forum_url['moderate_forum'], $fid));
	$c['breadcrumbs']->addCrumb($lang_misc['Merge topics']);

	($hook = ForumFunction::get_hook('mr_merge_topics_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	
	echo $c['templates']->render('moderate/merge-dialogue', [
	    'lang_misc'    => $lang_misc,
	]);
	
	exit;
}


// Delete one or more topics
else if (isset($_REQUEST['delete_topics']) || isset($_POST['delete_topics_comply']))
{
	$topics = isset($_POST['topics']) && !empty($_POST['topics']) ? $_POST['topics'] : array();
	$topics = array_map('intval', (is_array($topics) ? $topics : explode(',', $topics)));

	if (empty($topics))
		ForumFunction::message($lang_misc['No topics selected']);

	$multi = count($topics) > 1;
	if (isset($_POST['delete_topics_comply']))
	{
		if (!isset($_POST['req_confirm']))
			ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))), $lang_common['Cancel redirect']);

		($hook = ForumFunction::get_hook('mr_confirm_delete_topics_form_submitted')) ? eval($hook) : null;

		if (false === ($forum_ids = $c['ModerateGateway']->getForumIds($topics, $fid)))
		    ForumFunction::message($lang_common['Bad request']);

		// Strip the search index provided we're not just deleting redirect topics
		if (!empty($post_ids = $c['ModerateGateway']->getPostIdsByTopics($topics)))
		{
			if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/search_idx.php';

			strip_search_index($post_ids);
		}

		$c['ModerateGateway']->deleteTopicsData($topics);
		
		foreach ($forum_ids as $cur_forum_id)
			ForumFunction::sync_forum($cur_forum_id);

		$forum_flash->add_info($multi ? $lang_misc['Delete topics redirect'] : $lang_misc['Delete topic redirect']);

		($hook = ForumFunction::get_hook('mr_confirm_delete_topics_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))), $multi ? $lang_misc['Delete topics redirect'] : $lang_misc['Delete topic redirect']);
	}


	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] =0;
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_forum'], $fid);

	$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />',
		'topics'		=> '<input type="hidden" name="topics" value="'.implode(',', $topics).'" />'
	);

	// Setup breadcrumbs
	$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
	$c['breadcrumbs']->addCrumb($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))));
	$c['breadcrumbs']->addCrumb($lang_misc['Moderate forum'], ForumFunction::forum_link($forum_url['moderate_forum'], $fid));
	$c['breadcrumbs']->addCrumb($multi ? $lang_misc['Delete topics'] : $lang_misc['Delete topic']);

	($hook = ForumFunction::get_hook('mr_delete_topics_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	
	echo $c['templates']->render('moderate/delete-topic-dialogue', [
	    'lang_misc'    => $lang_misc,
	    'multi'        => $multi,
	]);
	
	exit;
}


// Open or close one or more topics
else if (isset($_REQUEST['open']) || isset($_REQUEST['close']))
{
	$action = (isset($_REQUEST['open'])) ? 0 : 1;

	($hook = ForumFunction::get_hook('mr_open_close_topic_selected')) ? eval($hook) : null;

	// There could be an array of topic ID's in $_POST
	if (isset($_POST['open']) || isset($_POST['close']))
	{
		$topics = isset($_POST['topics']) && is_array($_POST['topics']) ? $_POST['topics'] : array();
		$topics = array_map('intval', $topics);

		if (empty($topics))
			ForumFunction::message($lang_misc['No topics selected']);

		$c['ModerateGateway']->setTopicClosed($action, $topics, $fid);

		if (count($topics) == 1)
			$forum_page['redirect_msg'] = ($action) ? $lang_misc['Close topic redirect'] : $lang_misc['Open topic redirect'];
		else
			$forum_page['redirect_msg'] = ($action) ? $lang_misc['Close topics redirect'] : $lang_misc['Open topics redirect'];

		$forum_flash->add_info($forum_page['redirect_msg']);

		($hook = ForumFunction::get_hook('mr_open_close_multi_topics_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['moderate_forum'], $fid), $forum_page['redirect_msg']);
	}
	// Or just one in $_GET
	else
	{
		$topic_id = ($action) ? intval($_GET['close']) : intval($_GET['open']);
		if ($topic_id < 1)
			ForumFunction::message($lang_common['Bad request']);

		// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
		// If it's in GET, we need to make sure it's valid.
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token(($action ? 'close' : 'open').$topic_id)))
			ForumFunction::csrf_confirm_form();

		// Get the topic subject
		if (!($subject = $c['ModerateGateway']->getTopicSubject($topic_id, $fid)))
		{
			ForumFunction::message($lang_common['Bad request']);
		}

		$c['ModerateGateway']->setTopicClosed($action, $topic_id, $fid);

		$forum_page['redirect_msg'] = ($action) ? $lang_misc['Close topic redirect'] : $lang_misc['Open topic redirect'];

		$forum_flash->add_info($forum_page['redirect_msg']);

		($hook = ForumFunction::get_hook('mr_open_close_single_topic_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($topic_id, ForumFunction::sef_friendly($subject))), $forum_page['redirect_msg']);
	}
}


// Stick a topic
else if (isset($_GET['stick']))
{
	$stick = intval($_GET['stick']);
	if ($stick < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('stick'.$stick)))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mr_stick_topic_selected')) ? eval($hook) : null;

	// Get the topic subject
	if (!($subject =  $c['ModerateGateway']->getTopicSubject($stick, $fid)))
	{
		ForumFunction::message($lang_common['Bad request']);
	}
	
	$c['ModerateGateway']->setTopicSticky($stick, 1, $fid);

	$forum_flash->add_info($lang_misc['Stick topic redirect']);

	($hook = ForumFunction::get_hook('mr_stick_topic_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($stick, ForumFunction::sef_friendly($subject))), $lang_misc['Stick topic redirect']);
}


// Unstick a topic
else if (isset($_GET['unstick']))
{
	$unstick = intval($_GET['unstick']);
	if ($unstick < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('unstick'.$unstick)))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mr_unstick_topic_selected')) ? eval($hook) : null;

	// Get the topic subject
	if (!($subject =  $c['ModerateGateway']->getTopicSubject($unstick, $fid)))
	{
		ForumFunction::message($lang_common['Bad request']);
	}

	$c['ModerateGateway']->setTopicSticky($unstick, 0, $fid);
	
	$forum_flash->add_info($lang_misc['Unstick topic redirect']);

	($hook = ForumFunction::get_hook('mr_unstick_topic_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($unstick, ForumFunction::sef_friendly($subject))), $lang_misc['Unstick topic redirect']);
}


($hook = ForumFunction::get_hook('mr_new_action')) ? eval($hook) : null;


// No specific forum moderation action was specified in the query string, so we'll display the moderate forum view

// If forum is empty
if ($cur_forum['num_topics'] == 0)
	ForumFunction::message($lang_common['Bad request']);

// Load the viewforum.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/forum.php';

// Determine the topic offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil($cur_forum['num_topics'] / $forum_user['disp_topics']);

$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = $forum_user['disp_topics'] * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_topics']), ($cur_forum['num_topics']));
$forum_page['items_info'] = ForumFunction::generate_items_info($lang_misc['Topics'], ($forum_page['start_from'] + 1), $cur_forum['num_topics']);

// Select topics
$topics = $c['ModerateGateway']->getTopics($fid, $cur_forum, $forum_page, $forum_user, $forum_config);

// Generate paging links
$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.ForumFunction::paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['moderate_forum'], $lang_common['Paging separator'], $fid).'</p>';

// Navigation links for header and page numbering for title/meta description
if ($forum_page['page'] < $forum_page['num_pages'])
{
	$forum_page['nav']['last'] = '<link rel="last" href="'.ForumFunction::forum_sublink($forum_url['moderate_forum'], $forum_url['page'], $forum_page['num_pages'], $fid).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
	$forum_page['nav']['next'] = '<link rel="next" href="'.ForumFunction::forum_sublink($forum_url['moderate_forum'], $forum_url['page'], ($forum_page['page'] + 1), $fid).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
}
if ($forum_page['page'] > 1)
{
	$forum_page['nav']['prev'] = '<link rel="prev" href="'.ForumFunction::forum_sublink($forum_url['moderate_forum'], $forum_url['page'], ($forum_page['page'] - 1), $fid).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
	$forum_page['nav']['first'] = '<link rel="first" href="'.ForumFunction::forum_link($forum_url['moderate_forum'], $fid).'" title="'.$lang_common['Page'].' 1" />';
}

// Setup form
$forum_page['fld_count'] = 0;
$forum_page['form_action'] = ForumFunction::forum_link($forum_url['moderate_forum'], $fid);

// Setup breadcrumbs
$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb($cur_forum['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($cur_forum['forum_name']))));
$c['breadcrumbs']->addCrumb(sprintf($lang_misc['Moderate forum head'], ForumFunction::forum_htmlencode($cur_forum['forum_name'])));

// Setup main heading
if ($forum_page['num_pages'] > 1)
	$forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);

$forum_page['main_head_options']['select_all'] = '<span '.(empty($forum_page['main_head_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-topic-actions-form">'.$lang_misc['Select all'].'</span></span>';
$forum_page['main_foot_options']['select_all'] = '<span '.(empty($forum_page['main_foot_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-topic-actions-form">'.$lang_misc['Select all'].'</span></span>';

($hook = ForumFunction::get_hook('mr_topic_actions_pre_header_load')) ? eval($hook) : null;

$forum_loader->add_js('PUNBB.common.addDOMReadyEvent(PUNBB.common.initToggleCheckboxes);', array('type' => 'inline'));

define('FORUM_PAGE', 'modforum');

echo $c['templates']->render('moderate/modforum', [
    'lang_misc'    => $lang_misc,
    'lang_forum'    => $lang_forum,
    'topics'        => $topics,
]);

exit;

