<?php
/**
 * Post deletion page.
 *
 * Deletes the specified post (and, if necessary, the topic it is in).
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('dl_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);

// Load the delete.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/delete.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	ForumFunction::message($lang_common['Bad request']);


// Fetch some info about the post, the topic and the forum
if (!($cur_post = $c['DeleteGateway']->getPostInfo($id, $forum_user)))
	ForumFunction::message($lang_common['Bad request']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

$cur_post['is_topic'] = ($id == $cur_post['first_post_id']) ? true : false;

($hook = ForumFunction::get_hook('dl_pre_permission_check')) ? eval($hook) : null;

// Do we have permission to delete this post?
if ((($forum_user['g_delete_posts'] == '0' && !$cur_post['is_topic']) ||
	($forum_user['g_delete_topics'] == '0' && $cur_post['is_topic']) ||
	$cur_post['poster_id'] != $forum_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$forum_page['is_admmod'])
	ForumFunction::message($lang_common['No permission']);


($hook = ForumFunction::get_hook('dl_post_selected')) ? eval($hook) : null;

// User pressed the cancel button
if (isset($_POST['cancel']))
	ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $id), $lang_common['Cancel redirect']);

// User pressed the delete button
else if (isset($_POST['delete']))
{
	($hook = ForumFunction::get_hook('dl_form_submitted')) ? eval($hook) : null;

	if (!isset($_POST['req_confirm']))
		ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $id), $lang_common['No confirm redirect']);

	if ($cur_post['is_topic'])
	{
		// Delete the topic and all of it's posts
		ForumFunction::delete_topic($cur_post['tid'], $cur_post['fid']);

		$forum_flash->add_info($lang_delete['Topic del redirect']);

		($hook = ForumFunction::get_hook('dl_topic_deleted_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($cur_post['fid'], ForumFunction::sef_friendly($cur_post['forum_name']))), $lang_delete['Topic del redirect']);
	}
	else
	{
		// Delete just this one post
		ForumFunction::delete_post($id, $cur_post['tid'], $cur_post['fid']);

		// Fetch previus post #id in some topic for redirect after delete
		$prev_post = $c['DeleteGateway']->getPostForRedirect($id, $cur_post);

		$forum_flash->add_info($lang_delete['Post del redirect']);

		($hook = ForumFunction::get_hook('dl_post_deleted_pre_redirect')) ? eval($hook) : null;

		if (isset($prev_post['id']))
		{
			ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $prev_post['id']), $lang_delete['Post del redirect']);
		}
		else
		{
			ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($cur_post['tid'], ForumFunction::sef_friendly($cur_post['subject']))), $lang_delete['Post del redirect']);
		}
	}
}

// Run the post through the parser
if (!defined('FORUM_PARSER_LOADED'))
	require FORUM_ROOT.'include/parser.php';

$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = ForumFunction::forum_link($forum_url['delete'], $id);

// Generate the post heading
$forum_page['post_ident'] = [
    'byline' => '<span class="post-byline">'.sprintf((($cur_post['is_topic']) ? $lang_delete['Topic byline'] : $lang_delete['Reply byline']), '<strong>'.ForumFunction::forum_htmlencode($cur_post['poster']).'</strong>').'</span>',
    'link' => '<span class="post-link"><a class="permalink" href="'.ForumFunction::forum_link($forum_url['post'], $cur_post['tid']).'">'.ForumFunction::format_time($cur_post['posted']).'</a></span>'
];

($hook = ForumFunction::get_hook('dl_pre_item_ident_merge')) ? eval($hook) : null;

// Generate the post title
if ($cur_post['is_topic'])
	$forum_page['item_subject'] = sprintf($lang_delete['Topic title'], $cur_post['subject']);
else
	$forum_page['item_subject'] = sprintf($lang_delete['Reply title'], $cur_post['subject']);

$forum_page['item_subject'] = ForumFunction::forum_htmlencode($forum_page['item_subject']);

// Setup breadcrumbs

$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb($cur_post['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($cur_post['fid'], ForumFunction::sef_friendly($cur_post['forum_name']))));
$c['breadcrumbs']->addCrumb($cur_post['subject'], ForumFunction::forum_link($forum_url['topic'], array($cur_post['tid'], ForumFunction::sef_friendly($cur_post['subject']))));
$c['breadcrumbs']->addCrumb(($cur_post['is_topic']) ? $lang_delete['Delete topic'] : $lang_delete['Delete post']);

($hook = ForumFunction::get_hook('dl_pre_header_load')) ? eval($hook) : null;

define ('FORUM_PAGE', 'postdelete');

echo $c['templates']->render('postdelete', [
    'lang_delete'    => $lang_delete,
    'cur_post'      => $cur_post,
]);

exit;

