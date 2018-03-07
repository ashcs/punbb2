<?php
/**
 * Adds a new post to the specified topic or a new topic to the specified forum.
 *
 * @copyright (C) 2008-2012 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;

define('FORUM_SKIP_CSRF_CONFIRM', 1);

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('po_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);

// Load the post.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';


$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;

if ($tid < 1 && $fid < 1 || $tid > 0 && $fid > 0)
	ForumFunction::message($lang_common['Bad request']);

if (!($cur_posting = $c['PostGateway']->getPostingInfo($tid, $fid, $forum_user)))
	ForumFunction::message($lang_common['Bad request']);

$is_subscribed = $tid && $cur_posting['is_subscribed'];

// Is someone trying to post into a redirect forum?
if ($cur_posting['redirect_url'] != '')
	ForumFunction::message($lang_common['Bad request']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

($hook = ForumFunction::get_hook('po_pre_permission_check')) ? eval($hook) : null;

// Do we have permission to post?
if ((($tid && (($cur_posting['post_replies'] == '' && $forum_user['g_post_replies'] == '0') || $cur_posting['post_replies'] == '0')) ||
	($fid && (($cur_posting['post_topics'] == '' && $forum_user['g_post_topics'] == '0') || $cur_posting['post_topics'] == '0')) ||
	(isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
	!$forum_page['is_admmod'])
	ForumFunction::message($lang_common['No permission']);


($hook = ForumFunction::get_hook('po_posting_location_selected')) ? eval($hook) : null;

// Start with a clean slate
$errors = array();

// Did someone just hit "Submit" or "Preview"?
if (isset($_POST['form_sent']))
{
	($hook = ForumFunction::get_hook('po_form_submitted')) ? eval($hook) : null;

	// Make sure form_user is correct
	if (($forum_user['is_guest'] && $_POST['form_user'] != 'Guest') || (!$forum_user['is_guest'] && $_POST['form_user'] != $forum_user['username']))
		ForumFunction::message($lang_common['Bad request']);

	// Flood protection
	if (!isset($_POST['preview']) && $forum_user['last_post'] != '' && (time() - $forum_user['last_post']) < $forum_user['g_post_flood'] && (time() - $forum_user['last_post']) >= 0)
		$errors[] = sprintf($lang_post['Flood'], $forum_user['g_post_flood']);

	// If it's a new topic
	if ($fid)
	{
		$subject = ForumFunction::forum_trim($_POST['req_subject']);

		if ($subject == '')
			$errors[] = $lang_post['No subject'];
		else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
			$errors[] = sprintf($lang_post['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);
		else if ($forum_config['p_subject_all_caps'] == '0' && ForumFunction::check_is_all_caps($subject) && !$forum_page['is_admmod'])
			$errors[] = $lang_post['All caps subject'];
	}

	// If the user is logged in we get the username and e-mail from $forum_user
	if (!$forum_user['is_guest'])
	{
		$username = $forum_user['username'];
		$email = $forum_user['email'];
	}
	// Otherwise it should be in $_POST
	else
	{
		$username = ForumFunction::forum_trim($_POST['req_username']);
		$email = strtolower(ForumFunction::forum_trim(($forum_config['p_force_guest_email'] == '1') ? $_POST['req_email'] : $_POST['email']));

		// Load the profile.php language file
		require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

		// It's a guest, so we have to validate the username
		$errors = array_merge($errors, ForumFunction::validate_username($username));

		if ($forum_config['p_force_guest_email'] == '1' || $email != '')
		{
			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/email.php';

			if (!is_valid_email($email))
				$errors[] = $lang_post['Invalid e-mail'];

			if (is_banned_email($email))
				$errors[] = $lang_profile['Banned e-mail'];
		}
	}

	// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid
	if ($forum_user['is_admmod'] && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ForumFunction::generate_form_token(ForumFunction::get_current_url())))
		$errors[] = $lang_post['CSRF token mismatch'];

	// Clean up message from POST
	$message = ForumFunction::forum_linebreaks(ForumFunction::forum_trim($_POST['req_message']));

	if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
		$errors[] = sprintf($lang_post['Too long message'], ForumFunction::forum_number_format(strlen($message)), ForumFunction::forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
	else if ($forum_config['p_message_all_caps'] == '0' && ForumFunction::check_is_all_caps($message) && !$forum_page['is_admmod'])
		$errors[] = $lang_post['All caps message'];

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
	$subscribe = isset($_POST['subscribe']) ? 1 : 0;

	$now = time();

	($hook = ForumFunction::get_hook('po_end_validation')) ? eval($hook) : null;

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		// If it's a reply
		if ($tid)
		{
			$post_info = array(
				'is_guest'		=> $forum_user['is_guest'],
				'poster'		=> $username,
				'poster_id'		=> $forum_user['id'],	// Always 1 for guest posts
				'poster_email'	=> ($forum_user['is_guest'] && $email != '') ? $email : null,	// Always null for non-guest posts
				'subject'		=> $cur_posting['subject'],
				'message'		=> $message,
				'hide_smilies'	=> $hide_smilies,
				'posted'		=> $now,
				'subscr_action'	=> ($forum_config['o_subscriptions'] == '1' && $subscribe && !$is_subscribed) ? 1 : (($forum_config['o_subscriptions'] == '1' && !$subscribe && $is_subscribed) ? 2 : 0),
				'topic_id'		=> $tid,
				'forum_id'		=> $cur_posting['id'],
				'update_user'	=> true,
				'update_unread'	=> true
			);

			($hook = ForumFunction::get_hook('po_pre_add_post')) ? eval($hook) : null;
			ForumFunction::add_post($post_info, $new_pid);
		}
		// If it's a new topic
		else if ($fid)
		{
			$post_info = array(
				'is_guest'		=> $forum_user['is_guest'],
				'poster'		=> $username,
				'poster_id'		=> $forum_user['id'],	// Always 1 for guest posts
				'poster_email'	=> ($forum_user['is_guest'] && $email != '') ? $email : null,	// Always null for non-guest posts
				'subject'		=> $subject,
				'message'		=> $message,
				'hide_smilies'	=> $hide_smilies,
				'posted'		=> $now,
				'subscribe'		=> ($forum_config['o_subscriptions'] == '1' && (isset($_POST['subscribe']) && $_POST['subscribe'] == '1')),
				'forum_id'		=> $fid,
				'forum_name'	=> $cur_posting['forum_name'],
				'update_user'	=> true,
				'update_unread'	=> true
			);

			($hook = ForumFunction::get_hook('po_pre_ForumFunction::add_topic')) ? eval($hook) : null;
			ForumFunction::add_topic($post_info, $new_tid, $new_pid);
		}

		($hook = ForumFunction::get_hook('po_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $new_pid), $lang_post['Post redirect']);
	}
}


// Are we quoting someone?
if ($tid && isset($_GET['qid']))
{
	$qid = intval($_GET['qid']);
	if ($qid < 1)
		ForumFunction::message($lang_common['Bad request']);

	if (!($quote_info = $c['PostGateway']->getQuoteAndPoster($qid, $tid)))
	{
		ForumFunction::message($lang_common['Bad request']);
	}

	($hook = ForumFunction::get_hook('po_modify_quote_info')) ? eval($hook) : null;

	if ($forum_config['p_message_bbcode'] == '1')
	{
		// If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
		if (strpos($quote_info['poster'], '[') !== false || strpos($quote_info['poster'], ']') !== false)
		{
			if (strpos($quote_info['poster'], '\'') !== false)
				$quote_info['poster'] = '"'.$quote_info['poster'].'"';
			else
				$quote_info['poster'] = '\''.$quote_info['poster'].'\'';
		}
		else
		{
			// Get the characters at the start and end of $q_poster
			$ends = utf8_substr($quote_info['poster'], 0, 1).utf8_substr($quote_info['poster'], -1, 1);

			// Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
			if ($ends == '\'\'')
				$quote_info['poster'] = '"'.$quote_info['poster'].'"';
			else if ($ends == '""')
				$quote_info['poster'] = '\''.$quote_info['poster'].'\'';
		}

		$forum_page['quote'] = '[quote='.$quote_info['poster'].']'.$quote_info['message'].'[/quote]'."\n";
	}
	else
		$forum_page['quote'] = '> '.$quote_info['poster'].' '.$lang_common['wrote'].':'."\n\n".'> '.$quote_info['message']."\n";
}


// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = ($tid ? ForumFunction::forum_link($forum_url['new_reply'], $tid) : ForumFunction::forum_link($forum_url['new_topic'], $fid));
$forum_page['form_attributes'] = array();

$forum_page['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'form_user'		=> '<input type="hidden" name="form_user" value="'.((!$forum_user['is_guest']) ? ForumFunction::forum_htmlencode($forum_user['username']) : 'Guest').'" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />'
);

// Setup help
$forum_page['text_options'] = array();
if ($forum_config['p_message_bbcode'] == '1')
	$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.ForumFunction::forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
if ($forum_config['p_message_img_tag'] == '1')
	$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.ForumFunction::forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
if ($forum_config['o_smilies'] == '1')
	$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.ForumFunction::forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

// Setup breadcrumbs
$forum_page['crumbs'][] = array($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$forum_page['crumbs'][] = array($cur_posting['forum_name'], ForumFunction::forum_link($forum_url['forum'], array($cur_posting['id'], ForumFunction::sef_friendly($cur_posting['forum_name']))));
if ($tid)
	$forum_page['crumbs'][] = array($cur_posting['subject'], ForumFunction::forum_link($forum_url['topic'], array($tid, ForumFunction::sef_friendly($cur_posting['subject']))));
$forum_page['crumbs'][] = $tid ? $lang_post['Post reply'] : $lang_post['Post new topic'];

($hook = ForumFunction::get_hook('po_pre_header_load')) ? eval($hook) : null;

$forum_page['total_post_count'] = $c['PostGateway']->getAmountPost($tid);
$posts = $c['PostGateway']->getTopicPreview($tid, $forum_config);

define('FORUM_PAGE', 'post');

echo $c['templates']->render('post', [
    'lang_post'    => $lang_post,
    
    'posts'         => $posts,
    'tid'           => $tid,
    'fid'           => $fid,
    'cur_posting'   => $cur_posting,
    'is_subscribed' => $is_subscribed,
    'errors'        => $errors
]);

exit;


