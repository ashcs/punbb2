<?php
/**
 * Handles logins, logouts, and password reset requests.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;
use Punbb\Cache;

if (isset($_GET['action']))
	define('FORUM_QUIET_VISIT', 1);

defined('FORUM_ROOT') or define('FORUM_ROOT', './');

require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('li_start')) ? eval($hook) : null;

// Load the login.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/login.php';


$action = isset($_GET['action']) ? $_GET['action'] : null;
$errors = array();

// Login
if (isset($_POST['form_sent']) && empty($action))
{
	$form_username = ForumFunction::forum_trim($_POST['req_username']);
	$form_password = ForumFunction::forum_trim($_POST['req_password']);
	$save_pass = isset($_POST['save_pass']);

	($hook = ForumFunction::get_hook('li_login_form_submitted')) ? eval($hook) : null;

	list($user_id, $group_id, $db_password_hash, $salt) = $c['LoginGateway']->getLoginAttempt($form_username);

	$authorized = false;
	if (!empty($db_password_hash))
	{
		$form_password_hash = ForumFunction::forum_hash($form_password, $salt);
		if ($db_password_hash == $form_password_hash)
			$authorized = true;
	}

	($hook = ForumFunction::get_hook('li_login_pre_auth_message')) ? eval($hook) : null;

	if (!$authorized)
		$errors[] = sprintf($lang_login['Wrong user/pass']);

	// Did everything go according to plan?
	if (empty($errors))
	{
		// Update the status if this is the first time the user logged in
		if ($group_id == FORUM_UNVERIFIED)
		{
		    $c['LoginGateway']->updateUnverified($user_id, $forum_config);
    		// Remove cache file with forum stats
			Cache::clean_stats_cache();
		}

		$c['LoginGateway']->removeOnlineByIdent(\Punbb\ForumFunction::get_remote_address());

		$expire = ($save_pass) ? time() + 1209600 : time() + $forum_config['o_timeout_visit'];
		ForumFunction::forum_setcookie($cookie_name, base64_encode($user_id.'|'.$form_password_hash.'|'.$expire.'|'.sha1($salt.$form_password_hash.ForumFunction::forum_hash($expire, $salt))), $expire);

		($hook = ForumFunction::get_hook('li_login_pre_redirect')) ? eval($hook) : null;

		ForumFunction::redirect(ForumFunction::forum_htmlencode($_POST['redirect_url']).((substr_count($_POST['redirect_url'], '?') == 1) ? '&amp;' : '?').'login=1', $lang_login['Login redirect']);
	}
}


// Logout
else if ($action == 'out')
{
	if ($forum_user['is_guest'] || !isset($_GET['id']) || $_GET['id'] != $forum_user['id'])
	{
		header('Location: '.ForumFunction::forum_link($forum_url['index']));
		exit;
	}

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ForumFunction::generate_form_token('logout'.$forum_user['id'])))
		ForumFunction::csrf_confirm_form();

	($hook = ForumFunction::get_hook('li_logout_selected')) ? eval($hook) : null;
	
	$c['LoginGateway']->removeOnlineById($forum_user['id']);

	// Update last_visit (make sure there's something to update it with)
	$c['LoginGateway']->updateUserLastVisit($forum_user);

	$expire = time() + 1209600;
	ForumFunction::forum_setcookie($cookie_name, base64_encode('1|'.ForumFunction::random_key(8, false, true).'|'.$expire.'|'.ForumFunction::random_key(8, false, true)), $expire);

	// Reset tracked topics
	ForumFunction::set_tracked_topics(null);

	($hook = ForumFunction::get_hook('li_logout_pre_redirect')) ? eval($hook) : null;

	ForumFunction::redirect(ForumFunction::forum_link($forum_url['index']), $lang_login['Logout redirect']);
}


// New password
else if ($action == 'forget' || $action == 'forget_2')
{
	if (!$forum_user['is_guest'])
		header('Location: '.ForumFunction::forum_link($forum_url['index']));

	($hook = ForumFunction::get_hook('li_forgot_pass_selected')) ? eval($hook) : null;

	if (isset($_POST['form_sent']))
	{
		// User pressed the cancel button
		if (isset($_POST['cancel']))
			ForumFunction::redirect(ForumFunction::forum_link($forum_url['index']), $lang_login['New password cancel redirect']);

		if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/email.php';

		// Validate the email-address
		$email = strtolower(ForumFunction::forum_trim($_POST['req_email']));
		if (!is_valid_email($email))
			$errors[] = $lang_login['Invalid e-mail'];

		($hook = ForumFunction::get_hook('li_forgot_pass_end_validation')) ? eval($hook) : null;

		// Did everything go according to plan?
		if (empty($errors))
		{
			//$users_with_email = $c['LoginGateway']->getUserByEmail($email);

			if (!empty($users_with_email = $c['LoginGateway']->getUserByEmail($email)))
			{
				($hook = ForumFunction::get_hook('li_forgot_pass_pre_email')) ? eval($hook) : null;

				// Load the "activate password" template
				$mail_tpl = ForumFunction::forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/activate_password.tpl'));

				// The first row contains the subject
				$first_crlf = strpos($mail_tpl, "\n");
				$mail_subject = ForumFunction::forum_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = ForumFunction::forum_trim(substr($mail_tpl, $first_crlf));

				// Do the generic replacements first (they apply to all e-mails sent out here)
				$mail_message = str_replace('<base_url>', $base_url.'/', $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

				($hook = ForumFunction::get_hook('li_forgot_pass_new_general_replace_data')) ? eval($hook) : null;

				// Loop through users we found
				foreach ($users_with_email as $cur_hit)
				{
					$forgot_pass_timeout = 3600;

					($hook = ForumFunction::get_hook('li_forgot_pass_pre_flood_check')) ? eval($hook) : null;

					if ($cur_hit['group_id'] == FORUM_ADMIN)
						ForumFunction::message(sprintf($lang_login['Email important'], '<a href="mailto:'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'">'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'</a>'));

					if ($cur_hit['last_email_sent'] != '' && (time() - $cur_hit['last_email_sent']) < $forgot_pass_timeout && (time() - $cur_hit['last_email_sent']) >= 0)
						ForumFunction::message(sprintf($lang_login['Email flood'], $forgot_pass_timeout));

					// Generate a new password activation key
					$new_password_key = ForumFunction::random_key(8, true);

					$c['LoginGateway']->setActivationKey($new_password_key);
					
					// Do the user specific replacements to the template
					$cur_mail_message = str_replace('<username>', $cur_hit['username'], $mail_message);
					$cur_mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', ForumFunction::forum_link($forum_url['change_password_key'], array($cur_hit['id'], $new_password_key))), $cur_mail_message);

					($hook = ForumFunction::get_hook('li_forgot_pass_new_user_replace_data')) ? eval($hook) : null;

					forum_mail($email, $mail_subject, $cur_mail_message);
				}

				ForumFunction::message(sprintf($lang_login['Forget mail'], '<a href="mailto:'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'">'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'</a>'));
			}
			else
				$errors[] = sprintf($lang_login['No e-mail match'], ForumFunction::forum_htmlencode($email));
		}
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = ForumFunction::forum_link($forum_url['request_password']);

	$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
	$c['breadcrumbs']->addCrumb($lang_login['New password request']);

	($hook = ForumFunction::get_hook('li_forgot_pass_pre_header_load')) ? eval($hook) : null;

	define ('FORUM_PAGE', 'reqpass');
	
	echo $c['templates']->render('forgot', [
	    'lang_login'    => $lang_login,
	    
	    'errors'        => $errors
	]);
    exit;
}

if (!$forum_user['is_guest'])
	header('Location: '.ForumFunction::forum_link($forum_url['index']));

// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = ForumFunction::forum_link($forum_url['login']);

$forum_page['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'redirect_url'	=> '<input type="hidden" name="redirect_url" value="'.ForumFunction::forum_htmlencode($forum_user['prev_url']).'" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.ForumFunction::generate_form_token($forum_page['form_action']).'" />'
);

$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb(sprintf($lang_login['Login info'], $forum_config['o_board_title']));

define('FORUM_PAGE', 'login');

echo $c['templates']->render('login', [
    'lang_login'    => $lang_login,
    
    'errors'        => $errors
]);

exit;
