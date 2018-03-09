<?php
/**
 * Edit post page.
 *
 * Modifies the contents of the specified post.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */

use Punbb\ForumFunction;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('ed_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);

// Load the post.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	ForumFunction::message($lang_common['Bad request']);

// Fetch some info about the post, the topic and the forum
if (!($cur_post = $c['EditGateway']->getPostInfo($id, $forum_user)))
	ForumFunction::message($lang_common['Bad request']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

($hook = ForumFunction::get_hook('ed_pre_permission_check')) ? eval($hook) : null;

// Do we have permission to edit this post?
if (($forum_user['g_edit_posts'] == '0' ||
	$cur_post['poster_id'] != $forum_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$forum_page['is_admmod'])
	ForumFunction::message($lang_common['No permission']);


$can_edit_subject = $id == $cur_post['first_post_id'];

($hook = ForumFunction::get_hook('ed_post_selected')) ? eval($hook) : null;


// Start with a clean slate
$errors = array();

if (isset($_POST['form_sent']))
{
	($hook = ForumFunction::get_hook('ed_form_submitted')) ? eval($hook) : null;

	// If it is a topic it must contain a subject
	if ($can_edit_subject)
	{
		$subject = ForumFunction::forum_trim($_POST['req_subject']);

		if ($subject == '')
			$errors[] = $lang_post['No subject'];
		else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
			$errors[] = sprintf($lang_post['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);
		else if ($forum_config['p_subject_all_caps'] == '0' && ForumFunction::check_is_all_caps($subject) && !$forum_page['is_admmod'])
			$subject = utf8_ucwords(utf8_strtolower($subject));
	}

	// Clean up message from POST
	$message = ForumFunction::forum_linebreaks(ForumFunction::forum_trim($_POST['req_message']));

	if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
		$errors[] = sprintf($lang_post['Too long message'], ForumFunction::forum_number_format(strlen($message)), ForumFunction::forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
	else if ($forum_config['p_message_all_caps'] == '0' && ForumFunction::check_is_all_caps($message) && !$forum_page['is_admmod'])
		$message = utf8_ucwords(utf8_strtolower($message));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1')
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$message = preparse_bbcode($message, $errors);
	}

	if ($message == '')
		$errors[] = $lang_post['No message'];

	$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;

	($hook = ForumFunction::get_hook('ed_end_validation')) ? eval($hook) : null;

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		($hook = ForumFunction::get_hook('ed_pre_post_edited')) ? eval($hook) : null;

		if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/search_idx.php';

		if ($can_edit_subject)
		{
			// Update the topic and any redirect topics
			$c['EditGateway']->setTopicSubject($subject, $cur_post);
			// We changed the subject, so we need to take that into account when we update the search words
			update_search_index('edit', $id, $message, $subject);
		}
		else
			update_search_index('edit', $id, $message);

		// Update the post
		$c['EditGateway']->setPostMessage($id, $message, $hide_smilies, $forum_user, !(!isset($_POST['silent']) || !$forum_page['is_admmod']) );

		($hook = ForumFunction::get_hook('ed_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $id), $lang_post['Edit redirect']);
	}
}

// Setup error messages
if (!empty($errors))
{
	$forum_page['errors'] = array();

	foreach ($errors as $cur_error)
		$forum_page['errors'][] = '<li><span>'.$cur_error.'</span></li>';
}

// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = ForumFunction::forum_link($forum_url['edit'], $id);
$forum_page['form_attributes'] = array();

$forum_page['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />'
);

// Setup help
$forum_page['main_head_options'] = array();
if ($forum_config['p_message_bbcode'] == '1')
	$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.ForumFunction::forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
if ($forum_config['p_message_img_tag'] == '1')
	$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.ForumFunction::forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
if ($forum_config['o_smilies'] == '1')
	$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.ForumFunction::forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb($cur_post['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($cur_post['fid'], ForumFunction::sef_friendly($cur_post['forum_name']))));
$c['breadcrumbs']->addCrumb($cur_post['subject'], ForumFunction::forum_link($forum_url['topic'], array($cur_post['tid'], ForumFunction::sef_friendly($cur_post['subject']))));
$c['breadcrumbs']->addCrumb((($id == $cur_post['first_post_id']) ? $lang_post['Edit topic'] : $lang_post['Edit reply']));

($hook = ForumFunction::get_hook('ed_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE', 'postedit');

echo $c['templates']->render('postedit', [
    'lang_post'    => $lang_post,
    'cur_post'      => $cur_post,
    'id'            => $id,
    'can_edit_subject'  => $can_edit_subject 
]);

exit;
