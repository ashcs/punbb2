<?php
/**
 * Provides various features for forum users (ie: display rules, send emails through the forum, mark a forum as read, etc).
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;

if (isset($_GET['action']))
	define('FORUM_QUIET_VISIT', 1);

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('mi_start')) ? eval($hook) : null;

// Load the misc.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/misc.php';


$action = isset($_GET['action']) ? $_GET['action'] : null;
$errors = array();

// Show the forum rules?
if ($action == 'rules')
{
	if ($forum_config['o_rules'] == '0' || ($forum_user['is_guest'] && $forum_user['g_read_board'] == '0' && $forum_config['o_regs_allow'] == '0'))
		ForumFunction::message($lang_common['Bad request']);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index'])),
		$lang_common['Rules']
	);

	($hook = ForumFunction::get_hook('mi_rules_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'rules');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = ForumFunction::get_hook('mi_rules_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo $lang_common['Rules'] ?></span></h2>
	</div>

	<div class="main-content main-frm">
		<div id="rules-content" class="ct-box user-box">
			<?php echo $forum_config['o_rules_message']."\n" ?>
		</div>
	</div>
<?php

	($hook = ForumFunction::get_hook('mi_rules_end')) ? eval($hook) : null;

	$tpl_temp = ForumFunction::forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


// Mark all topics/posts as read?
else if ($action == 'markread')
{
	if ($forum_user['is_guest'])
		ForumFunction::message($lang_common['No permission']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('markread'.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mi_markread_selected')) ? eval($hook) : null;

	$c['MiscGateway']->setMarkread($forum_user);
	
	// Reset tracked topics
	ForumFunction::set_tracked_topics(null);

	$forum_flash->add_info($lang_misc['Mark read redirect']);

	($hook = ForumFunction::get_hook('mi_markread_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['index']), $lang_misc['Mark read redirect']);
}


// Mark the topics/posts in a forum as read?
else if ($action == 'markforumread')
{
	if ($forum_user['is_guest'])
		ForumFunction::message($lang_common['No permission']);

	$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($fid < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('markforumread'.$fid.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mi_markforumread_selected')) ? eval($hook) : null;

	// Fetch some info about the forum
	if (!($forum_name = $c['MiscGateway']->getForumName($fid, $forum_user)))
	{
		ForumFunction::message($lang_common['Bad request']);
	}

	$tracked_topics = ForumFunction::get_tracked_topics();
	$tracked_topics['forums'][$fid] = time();
	ForumFunction::set_tracked_topics($tracked_topics);

	$forum_flash->add_info($lang_misc['Mark forum read redirect']);

	($hook = ForumFunction::get_hook('mi_markforumread_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($fid, ForumFunction::sef_friendly($forum_name))), $lang_misc['Mark forum read redirect']);
}

// OpenSearch plugin?
else if ($action == 'opensearch')
{
	// Send XML/no cache headers
	header('Content-Type: text/xml; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
	echo '<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">'."\n";
	echo "\t".'<ShortName>'.ForumFunction::forum_htmlencode($forum_config['o_board_title']).'</ShortName>'."\n";
	echo "\t".'<Description>'.ForumFunction::forum_htmlencode($forum_config['o_board_desc']).'</Description>'."\n";
	echo "\t".'<InputEncoding>utf-8</InputEncoding>'."\n";
	echo "\t".'<OutputEncoding>utf-8</OutputEncoding>'."\n";
	echo "\t".'<Image width="16" height="16" type="image/x-icon">'.$base_url.'/favicon.ico</Image>'."\n";
	echo "\t".'<Url type="text/html" method="get" template="'.$base_url.'/search.php?action=search&amp;source=opensearch&amp;keywords={searchTerms}"/>'."\n";
	echo "\t".'<Url type="application/opensearchdescription+xml" rel="self" template="'.ForumFunction::forum_link($forum_url['opensearch']).'"/>'."\n";
	echo "\t".'<Contact>'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'</Contact>'."\n";

	if ($forum_config['o_show_version'] == '1')
		echo "\t".'<Attribution>PunBB '.$forum_config['o_cur_version'].'</Attribution>'."\n";
	else
		echo "\t".'<Attribution>PunBB</Attribution>'."\n";

	echo "\t".'<moz:SearchForm>'.ForumFunction::forum_link($forum_url['search']).'</moz:SearchForm>'."\n";
	echo '</OpenSearchDescription>'."\n";

	exit;
}


// Send form e-mail?
else if (isset($_GET['email']))
{
	if ($forum_user['is_guest'] || $forum_user['g_send_email'] == '0')
		ForumFunction::message($lang_common['No permission']);

	$recipient_id = intval($_GET['email']);

	if ($recipient_id < 2)
		ForumFunction::message($lang_common['Bad request']);

	($hook = ForumFunction::get_hook('mi_email_selected')) ? eval($hook) : null;

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		ForumFunction::redirect(ForumFunction::forum_htmlencode($_POST['redirect_url']), $lang_common['Cancel redirect']);

	if (!($recipient_info = $c['MiscGateway']->getRecipientInfo($recipient_id)))
	{
		ForumFunction::message($lang_common['Bad request']);
	}

	if ($recipient_info['email_setting'] == 2 && !$forum_user['is_admmod'])
		ForumFunction::message($lang_misc['Form e-mail disabled']);

	if ($recipient_info['email'] == '')
		ForumFunction::message($lang_common['Bad request']);

	if (isset($_POST['form_sent']))
	{
		($hook = ForumFunction::get_hook('mi_email_form_submitted')) ? eval($hook) : null;

		// Clean up message and subject from POST
		$subject = ForumFunction::forum_trim($_POST['req_subject']);
		$message = ForumFunction::forum_trim($_POST['req_message']);

		if ($subject == '')
			$errors[] = $lang_misc['No e-mail subject'];
		else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
	     	$errors[] = sprintf($lang_misc['Too long e-mail subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);

		if ($message == '')
			$errors[] = $lang_misc['No e-mail message'];
		else if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
			$errors[] = sprintf($lang_misc['Too long e-mail message'],
				ForumFunction::forum_number_format(strlen($message)), ForumFunction::forum_number_format(FORUM_MAX_POSTSIZE_BYTES));

		if ($forum_user['last_email_sent'] != '' && (time() - $forum_user['last_email_sent']) < $forum_user['g_email_flood'] && (time() - $forum_user['last_email_sent']) >= 0)
			$errors[] = sprintf($lang_misc['Email flood'], $forum_user['g_email_flood']);

		($hook = ForumFunction::get_hook('mi_email_end_validation')) ? eval($hook) : null;

		// Did everything go according to plan?
		if (empty($errors))
		{
			// Load the "form e-mail" template
			$mail_tpl = ForumFunction::forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/form_email.tpl'));

			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = ForumFunction::forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = ForumFunction::forum_trim(substr($mail_tpl, $first_crlf));

			$mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
			$mail_message = str_replace('<sender>', $forum_user['username'], $mail_message);
			$mail_message = str_replace('<board_title>', $forum_config['o_board_title'], $mail_message);
			$mail_message = str_replace('<mail_message>', $message, $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

			($hook = ForumFunction::get_hook('mi_email_new_replace_data')) ? eval($hook) : null;

			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/email.php';

			forum_mail($recipient_info['email'], $mail_subject, $mail_message, $forum_user['email'], $forum_user['username']);

			// Set the user's last_email_sent time
			$c['MiscGateway']->setLastEmailSent($forum_user);
			
			$forum_flash->add_info($lang_misc['E-mail sent redirect']);

			($hook = ForumFunction::get_hook('mi_email_pre_redirect')) ? eval($hook) : null;

			ForumFunction::redirect(ForumFunction::forum_htmlencode($_POST['redirect_url']), $lang_misc['E-mail sent redirect']);
		}
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['email'], $recipient_id);

	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'redirect_url'	=> '<input type="hidden" name="redirect_url" value="'.ForumFunction::forum_htmlencode($forum_user['prev_url']).'" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />'
	);

	// Setup main heading
	$forum_page['main_head'] = sprintf($lang_misc['Send forum e-mail'], ForumFunction::forum_htmlencode($recipient_info['username']));

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index'])),
		sprintf($lang_misc['Send forum e-mail'], ForumFunction::forum_htmlencode($recipient_info['username']))
	);

	($hook = ForumFunction::get_hook('mi_email_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'formemail');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = ForumFunction::get_hook('mi_email_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo $forum_page['main_head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p class="important"><?php echo $lang_misc['E-mail disclosure note'] ?></p>
		</div>
<?php

	// If there were any errors, show them
	if (!empty($errors))
	{
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = ForumFunction::get_hook('mi_pre_email_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_misc['Form e-mail errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo $lang_common['Required warn'] ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = ForumFunction::get_hook('mi_email_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_misc['Write e-mail'] ?></strong></legend>
<?php ($hook = ForumFunction::get_hook('mi_email_pre_subject')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_misc['E-mail subject'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_subject" value="<?php echo(isset($_POST['req_subject']) ? ForumFunction::forum_htmlencode($_POST['req_subject']) : '') ?>" size="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" maxlength="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" required /></span>
					</div>
				</div>
<?php ($hook = ForumFunction::get_hook('mi_email_pre_message_contents')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_misc['E-mail message'] ?></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="req_message" rows="10" cols="95" required><?php echo(isset($_POST['req_message']) ? ForumFunction::forum_htmlencode($_POST['req_message']) : '') ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = ForumFunction::get_hook('mi_email_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = ForumFunction::get_hook('mi_email_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php

	($hook = ForumFunction::get_hook('mi_email_end')) ? eval($hook) : null;

	$tpl_temp = ForumFunction::forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


// Report a post?
else if (isset($_GET['report']))
{
	if ($forum_user['is_guest'])
		ForumFunction::message($lang_common['No permission']);

	$post_id = intval($_GET['report']);
	if ($post_id < 1)
		ForumFunction::message($lang_common['Bad request']);


	($hook = ForumFunction::get_hook('mi_report_selected')) ? eval($hook) : null;

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $post_id), $lang_common['Cancel redirect']);


	if (isset($_POST['form_sent']))
	{
		($hook = ForumFunction::get_hook('mi_report_form_submitted')) ? eval($hook) : null;

		// Start with a clean slate
		$errors = array();

		// Flood protection
		if ($forum_user['last_email_sent'] != '' && (time() - $forum_user['last_email_sent']) < $forum_user['g_email_flood'] && (time() - $forum_user['last_email_sent']) >= 0)
			ForumFunction::message(sprintf($lang_misc['Report flood'], $forum_user['g_email_flood']));

		// Clean up reason from POST
		$reason = ForumFunction::forum_linebreaks(ForumFunction::forum_trim($_POST['req_reason']));
		if ($reason == '')
			ForumFunction::message($lang_misc['No reason']);

		if (strlen($reason) > FORUM_MAX_POSTSIZE_BYTES)
		{
			$errors[] = sprintf($lang_misc['Too long reason'], ForumFunction::forum_number_format(strlen($reason)), ForumFunction::forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
		}

		if (empty($errors)) {
			// Get some info about the topic we're reporting
			if (!($topic_info = $c['MiscGateway']->getReportedTopicInfo($post_id)))
			{
				ForumFunction::message($lang_common['Bad request']);
			}

			($hook = ForumFunction::get_hook('mi_report_pre_reports_sent')) ? eval($hook) : null;

			// Should we use the internal report handling?
			if ($forum_config['o_report_method'] == 0 || $forum_config['o_report_method'] == 2)
			{
				$c['MiscGateway']->insertReport($post_id, $topic_info, $forum_user, $reason);
			}

			// Should we e-mail the report?
			if ($forum_config['o_report_method'] == 1 || $forum_config['o_report_method'] == 2)
			{
				// We send it to the complete mailing-list in one swoop
				if ($forum_config['o_mailing_list'] != '')
				{
					$mail_subject = 'Report('.$topic_info['forum_id'].') - \''.$topic_info['subject'].'\'';
					$mail_message = 'User \''.$forum_user['username'].'\' has reported the following message:'."\n".ForumFunction::forum_link($forum_url['post'], $post_id)."\n\n".'Reason:'."\n".$reason;

					if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
						require FORUM_ROOT.'include/email.php';

					($hook = ForumFunction::get_hook('mi_report_modify_message')) ? eval($hook) : null;

					forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
				}
			}

			// Set last_email_sent time to prevent flooding
			$c['MiscGateway']->setLastEmailSent($forum_user);
			
			$forum_flash->add_info($lang_misc['Report redirect']);

			($hook = ForumFunction::get_hook('mi_report_pre_redirect')) ? eval($hook) : null;

			ForumFunction::redirect(ForumFunction::forum_link($forum_url['post'], $post_id), $lang_misc['Report redirect']);
		}
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['report'], $post_id);

	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />'
	);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index'])),
		$lang_misc['Report post']
	);

	// Setup main heading
	$forum_page['main_head'] = end($forum_page['crumbs']);

	($hook = ForumFunction::get_hook('mi_report_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'report');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = ForumFunction::get_hook('mi_report_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo $forum_page['main_head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo $lang_common['Required warn'] ?></p>
		</div>
<?php
		// If there were any errors, show them
		if (!empty($errors)) {
			$forum_page['errors'] = array();
			foreach ($errors as $cur_error) {
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
			}

			($hook = ForumFunction::get_hook('mi_pre_report_errors')) ? eval($hook) : null;
?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_misc['Report errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php
		}
?>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = ForumFunction::get_hook('mi_report_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = ForumFunction::get_hook('mi_report_pre_reason')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_misc['Reason'] ?></span> <small><?php echo $lang_misc['Reason help'] ?></small></label><br />
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="req_reason" rows="5" cols="60" required></textarea></span></div>
					</div>
				</div>
<?php ($hook = ForumFunction::get_hook('mi_report_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = ForumFunction::get_hook('mi_report_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php

	($hook = ForumFunction::get_hook('mi_report_end')) ? eval($hook) : null;

	$tpl_temp = ForumFunction::forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


// Subscribe to a topic?
else if (isset($_GET['subscribe']))
{
	if ($forum_user['is_guest'] || $forum_config['o_subscriptions'] != '1')
		ForumFunction::message($lang_common['No permission']);

	$topic_id = intval($_GET['subscribe']);
	if ($topic_id < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('subscribe'.$topic_id.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mi_subscribe_selected')) ? eval($hook) : null;

	// Make sure the user can view the topic
	if (!($subject = $c['MiscGateway']->getTopicSubject($topic_id, $forum_user)))
		ForumFunction::message($lang_common['Bad request']);

	if ($c['MiscGateway']->getTopicSubscrCount($topic_id, $forum_user) > 0)
		ForumFunction::message($lang_misc['Already subscribed']);

	$c['MiscGateway']->setTopicSubscription($topic_id, $forum_user);

	$forum_flash->add_info($lang_misc['Subscribe redirect']);

	($hook = ForumFunction::get_hook('mi_subscribe_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($topic_id, ForumFunction::sef_friendly($subject))), $lang_misc['Subscribe redirect']);
}


// Unsubscribe from a topic?
else if (isset($_GET['unsubscribe']))
{
	if ($forum_user['is_guest'] || $forum_config['o_subscriptions'] != '1')
		ForumFunction::message($lang_common['No permission']);

	$topic_id = intval($_GET['unsubscribe']);
	if ($topic_id < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('unsubscribe'.$topic_id.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mi_unsubscribe_selected')) ? eval($hook) : null;

	if (!($subject = $c['MiscGateway']->getSubscribedSubject($topic_id, $forum_user)))
		ForumFunction::message($lang_misc['Not subscribed']);

	$c['MiscGateway']->deleteTopicSubscribtion($topic_id, $forum_user);

	$forum_flash->add_info($lang_misc['Unsubscribe redirect']);

	($hook = ForumFunction::get_hook('mi_unsubscribe_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['topic'], array($topic_id, ForumFunction::sef_friendly($subject))), $lang_misc['Unsubscribe redirect']);
}


// Subscribe to a forum?
else if (isset($_GET['forum_subscribe']))
{
	if ($forum_user['is_guest'] || $forum_config['o_subscriptions'] != '1')
		ForumFunction::message($lang_common['No permission']);

	$forum_id = intval($_GET['forum_subscribe']);
	if ($forum_id < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('forum_subscribe'.$forum_id.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mi_forum_subscribe_selected')) ? eval($hook) : null;

	// Make sure the user can view the forum
	if (!($forum_name = $c['MiscGateway']->getForumName($forum_id, $forum_user)))
		ForumFunction::message($lang_common['Bad request']);

	if ($c['MiscGateway']->getForumSubscrCount($forum_id, $forum_user) > 0)
		ForumFunction::message($lang_misc['Already subscribed']);

	$c['MiscGateway']->setForumSubscription($forum_id, $forum_user);
	
	$forum_flash->add_info($lang_misc['Subscribe redirect']);

	($hook = ForumFunction::get_hook('mi_forum_subscribe_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($forum_id, ForumFunction::sef_friendly($forum_name))), $lang_misc['Subscribe redirect']);
}


// Unsubscribe from a topic?
else if (isset($_GET['forum_unsubscribe']))
{
	if ($forum_user['is_guest'] || $forum_config['o_subscriptions'] != '1')
		ForumFunction::message($lang_common['No permission']);

	$forum_id = intval($_GET['forum_unsubscribe']);
	if ($forum_id < 1)
		ForumFunction::message($lang_common['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('forum_unsubscribe'.$forum_id.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('mi_forum_unsubscribe_selected')) ? eval($hook) : null;

	// Make sure the user can view the forum
	if (!($forum_name = $c['MiscGateway']->getForumName($forum_id, $forum_user)))
		ForumFunction::message($lang_misc['Not subscribed']);

	$c['MiscGateway']->deleteForumSubscription($forum_id, $forum_user);

	$forum_flash->add_info($lang_misc['Unsubscribe redirect']);

	($hook = ForumFunction::get_hook('mi_forum_unsubscribe_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['forum'], array($forum_id, ForumFunction::sef_friendly($forum_name))), $lang_misc['Unsubscribe redirect']);
}


($hook = ForumFunction::get_hook('mi_new_action')) ? eval($hook) : null;

ForumFunction::message($lang_common['Bad request']);
