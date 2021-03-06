<?php
/**
 * Allows the creation of new user accounts.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;
use Punbb\Cache;


defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('rg_start')) ? eval($hook) : null;

// If we are logged in, we shouldn't be here
if (!$forum_user['is_guest'])
{
	header('Location: '.ForumFunction::forum_link($forum_url['index']));
	exit;
}

// Load the profile.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

if ($forum_config['o_regs_allow'] == '0')
	ForumFunction::message($lang_profile['No new regs']);

$errors = array();

// User pressed the cancel button
if (isset($_GET['cancel']))
	ForumFunction::redirect(ForumFunction::forum_link($forum_url['index']), $lang_profile['Reg cancel redirect']);

// User pressed agree but failed to tick checkbox
else if (isset($_GET['agree']) && !isset($_GET['req_agreement']))
	ForumFunction::redirect(ForumFunction::forum_link($forum_url['index']), $lang_profile['Reg cancel redirect']);

// Show the rules
else if ($forum_config['o_rules'] == '1' && !isset($_GET['agree']) && !isset($_POST['form_sent']))
{
	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
	$c['breadcrumbs']->addCrumb($lang_common['Register'], ForumFunction::forum_link($forum_url['register']));
	$c['breadcrumbs']->addCrumb($lang_common['Rules']);

	($hook = ForumFunction::get_hook('rg_rules_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'rules-register');
	
	echo $c['templates']->render('rules-register', [
	    'lang_profile'    => $lang_profile,
	    'errors'        => $errors
	]);
	
	exit;
}

else if (isset($_POST['form_sent']))
{
	($hook = ForumFunction::get_hook('rg_register_form_submitted')) ? eval($hook) : null;

	// Check that someone from this IP didn't register a user within the last hour (DoS prevention)
	$query = array(
		'SELECT'	=> 'COUNT(u.id)',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.registration_ip=\''.$forum_db->escape(ForumFunction::get_remote_address()).'\' AND u.registered>'.(time() - 3600)
	);

	($hook = ForumFunction::get_hook('rg_register_qr_check_register_flood')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
	if ($forum_db->result($result) > 0)
	{
		$errors[] = $lang_profile['Registration flood'];
	}

	// Did everything go according to plan so far?
	if (empty($errors))
	{
		$username = ForumFunction::forum_trim($_POST['req_username']);
		$email1 = strtolower(ForumFunction::forum_trim($_POST['req_email1']));

		if ($forum_config['o_regs_verify'] == '1')
		{
			$password1 = ForumFunction::random_key(8, true);
			$password2 = $password1;
		}
		else
		{
			$password1 = ForumFunction::forum_trim($_POST['req_password1']);
			$password2 = ($forum_config['o_mask_passwords'] == '1') ? ForumFunction::forum_trim($_POST['req_password2']) : $password1;
		}

		// Validate the username
		$errors = array_merge($errors, ForumFunction::validate_username($username));

		// ... and the password
		if (utf8_strlen($password1) < 4)
			$errors[] = $lang_profile['Pass too short'];
		else if ($password1 != $password2)
			$errors[] = $lang_profile['Pass not match'];

		// ... and the e-mail address
		if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/email.php';

		if (!is_valid_email($email1))
			$errors[] = $lang_profile['Invalid e-mail'];

		// Check if it's a banned e-mail address
		$banned_email = is_banned_email($email1);
		if ($banned_email && $forum_config['p_allow_banned_email'] == '0')
			$errors[] = $lang_profile['Banned e-mail'];

		// Clean old unverified registrators - delete older than 72 hours
		$query = array(
			'DELETE'	=> 'users',
			'WHERE'		=> 'group_id='.FORUM_UNVERIFIED.' AND activate_key IS NOT NULL AND registered < '.(time() - 259200)
		);
		($hook = ForumFunction::get_hook('rg_register_qr_delete_unverified')) ? eval($hook) : null;
		$forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);

		// Check if someone else already has registered with that e-mail address
		$dupe_list = array();

		$query = array(
			'SELECT'	=> 'u.username',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.email=\''.$forum_db->escape($email1).'\''
		);

		($hook = ForumFunction::get_hook('rg_register_qr_check_email_dupe')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);

		while ($cur_dupe = $forum_db->fetch_assoc($result))
		{
			$dupe_list[] = $cur_dupe['username'];
		}

		if (!empty($dupe_list) && empty($errors))
		{
			if ($forum_config['p_allow_dupe_email'] == '0')
				$errors[] = $lang_profile['Dupe e-mail'];
		}

		($hook = ForumFunction::get_hook('rg_register_end_validation')) ? eval($hook) : null;

		// Did everything go according to plan so far?
		if (empty($errors))
		{
			// Make sure we got a valid language string
			if (isset($_POST['language']))
			{
				$language = preg_replace('#[\.\\\/]#', '', $_POST['language']);
				if (!file_exists(FORUM_ROOT.'lang/'.$language.'/common.php'))
					ForumFunction::message($lang_common['Bad request']);
			}
			else
				$language = $forum_config['o_default_lang'];

			$initial_group_id = ($forum_config['o_regs_verify'] == '0') ? $forum_config['o_default_user_group'] : FORUM_UNVERIFIED;
			$salt = ForumFunction::random_key(12);
			$password_hash = ForumFunction::forum_hash($password1, $salt);

			// Validate timezone and DST
			$timezone = (isset($_POST['timezone'])) ? floatval($_POST['timezone']) : $forum_config['o_default_timezone'];

			// Validate timezone — on error use default value
			if (($timezone > 14.0) || ($timezone < -12.0)) {
				$timezone = $forum_config['o_default_timezone'];
			}

			// DST
			$dst = (isset($_POST['dst']) && intval($_POST['dst']) === 1) ? 1 : $forum_config['o_default_dst'];


			// Insert the new user into the database. We do this now to get the last inserted id for later use.
			$user_info = array(
				'username'				=>	$username,
				'group_id'				=>	$initial_group_id,
				'salt'					=>	$salt,
				'password'				=>	$password1,
				'password_hash'			=>	$password_hash,
				'email'					=>	$email1,
				'email_setting'			=>	$forum_config['o_default_email_setting'],
				'timezone'				=>	$timezone,
				'dst'					=>	$dst,
				'language'				=>	$language,
				'style'					=>	$forum_config['o_default_style'],
				'registered'			=>	time(),
				'registration_ip'		=>	ForumFunction::get_remote_address(),
				'activate_key'			=>	($forum_config['o_regs_verify'] == '1') ? '\''.ForumFunction::random_key(8, true).'\'' : 'NULL',
				'require_verification'	=>	($forum_config['o_regs_verify'] == '1'),
				'notify_admins'			=>	($forum_config['o_regs_report'] == '1')
			);

			($hook = ForumFunction::get_hook('rg_register_pre_ForumFunction::add_user')) ? eval($hook) : null;
			ForumFunction::add_user($user_info, $new_uid);

			// If we previously found out that the e-mail was banned
			if ($banned_email && $forum_config['o_mailing_list'] != '')
			{
				$mail_subject = 'Alert - Banned e-mail detected';
				$mail_message = 'User \''.$username.'\' registered with banned e-mail address: '.$email1."\n\n".'User profile: '.ForumFunction::forum_link($forum_url['user'], $new_uid)."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

				($hook = ForumFunction::get_hook('rg_register_banned_email')) ? eval($hook) : null;

				forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
			}

			// If we previously found out that the e-mail was a dupe
			if (!empty($dupe_list) && $forum_config['o_mailing_list'] != '')
			{
				$mail_subject = 'Alert - Duplicate e-mail detected';
				$mail_message = 'User \''.$username.'\' registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.ForumFunction::forum_link($forum_url['user'], $new_uid)."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

				($hook = ForumFunction::get_hook('rg_register_dupe_email')) ? eval($hook) : null;

				forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
			}

			($hook = ForumFunction::get_hook('rg_register_pre_login_redirect')) ? eval($hook) : null;

			// Must the user verify the registration or do we log him/her in right now?
			if ($forum_config['o_regs_verify'] == '1')
			{
				ForumFunction::message(sprintf($lang_profile['Reg e-mail'], '<a href="mailto:'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'">'.ForumFunction::forum_htmlencode($forum_config['o_admin_email']).'</a>'));
			}
			else
			{
				// Remove cache file with forum stats
				
				{
					
				}

				Cache::clean_stats_cache();
			}

			$expire = time() + $forum_config['o_timeout_visit'];

			ForumFunction::forum_setcookie($cookie_name, base64_encode($new_uid.'|'.$password_hash.'|'.$expire.'|'.sha1($salt.$password_hash.ForumFunction::forum_hash($expire, $salt))), $expire);

			ForumFunction::redirect(ForumFunction::forum_link($forum_url['index']), $lang_profile['Reg complete']);
		}
	}
}

// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = ForumFunction::forum_link($forum_url['register']).'?action=register';

// Setup form information
$forum_page['frm_info'] = array();
if ($forum_config['o_regs_verify'] != '0')
	$forum_page['frm_info']['email'] = '<p class="warn">'.$lang_profile['Reg e-mail info'].'</p>';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index'])),
	sprintf($lang_profile['Register at'], $forum_config['o_board_title'])
);

// Load JS for timezone detection
$forum_loader->add_js($base_url.'/include/js/min/punbb.timezone.min.js');
$forum_loader->add_js('PUNBB.timezone.detect_on_register_form();', array('type' => 'inline'));

($hook = ForumFunction::get_hook('rg_register_pre_header_load')) ? eval($hook) : null;


$languages = array();
$d = dir(FORUM_ROOT.'lang');
while (($entry = $d->read()) !== false)
{
    if ($entry != '.' && $entry != '..' && is_dir(FORUM_ROOT.'lang/'.$entry) && file_exists(FORUM_ROOT.'lang/'.$entry.'/common.php'))
        $languages[] = $entry;
}
$d->close();

($hook = \Punbb\ForumFunction::get_hook('rg_register_pre_language')) ? eval($hook) : null;

define('FORUM_PAGE', 'register');

echo $c['templates']->render('register', [
    'lang_profile'    => $lang_profile,
    'errors'        => $errors,
    'languages'    => $languages
    
]);

exit;

