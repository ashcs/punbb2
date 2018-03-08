<?php
/**
 * Loads common data and performs various functions necessary for the site to work properly.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;
use Punbb\Cache;

if (!defined('FORUM_ROOT'))
	exit('The constant FORUM_ROOT must be defined and point to a valid PunBB installation root directory.');

if (!defined('FORUM_ESSENTIALS_LOADED'))
	require FORUM_ROOT.'include/essentials.php';

// Strip out "bad" UTF-8 characters
ForumFunction::forum_remove_bad_characters();

// If a cookie name is not specified in config.php, we use the default (forum_cookie)
if (empty($cookie_name))
	$cookie_name = 'forum_cookie';

// Enable output buffering
if (!defined('FORUM_DISABLE_BUFFERING'))
{
	// For some very odd reason, "Norton Internet Security" unsets this
	$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

	// Should we use gzip output compression?
	if ($forum_config['o_gzip'] && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');
	else
		ob_start();
}

// Define standard date/time formats
$forum_time_formats = array($forum_config['o_time_format'], 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
$forum_date_formats = array($forum_config['o_date_format'], 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

// Create forum_page array
$forum_page = array();

// Login and fetch user info
$forum_user = array();
ForumFunction::cookie_login($forum_user);

// Attempt to load the common language file
if (file_exists(FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php'))
	include FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
else
	ForumFunction::error('There is no valid language pack \''.ForumFunction::forum_htmlencode($forum_user['language']).'\' installed.<br />Please reinstall a language of that name.');

// Setup the URL rewriting scheme
if ($forum_config['o_sef'] != 'Default' && file_exists(FORUM_ROOT.'include/url/'.$forum_config['o_sef'].'/forum_urls.php'))
	require FORUM_ROOT.'include/url/'.$forum_config['o_sef'].'/forum_urls.php';
else
	require FORUM_ROOT.'include/url/Default/forum_urls.php';

// A good place to modify the URL scheme
($hook = ForumFunction::get_hook('co_modify_url_scheme')) ? eval($hook) : null;

// Check if we are to display a maintenance message
if ($forum_config['o_maintenance'] && $forum_user['g_id'] > FORUM_ADMIN && !defined('FORUM_TURN_OFF_MAINT'))
	ForumFunction::maintenance_message();

// Load cached updates info
if ($forum_user['g_id'] == FORUM_ADMIN)
{
	if (file_exists(FORUM_CACHE_DIR.'cache_updates.php'))
		include FORUM_CACHE_DIR.'cache_updates.php';

	// Regenerate cache only if automatic updates are enabled and if the cache is more than 12 hours old
	if ($forum_config['o_check_for_updates'] == '1' && (!defined('FORUM_UPDATES_LOADED') || $forum_updates['cached'] < (time() - 43200)))
	{
		Cache::generate_updates_cache();
		require FORUM_CACHE_DIR.'cache_updates.php';
	}
}

// Check if current user is banned
ForumFunction::check_bans();

// Update online list
ForumFunction::update_users_online();

// Check to see if we logged in without a cookie being set
if ($forum_user['is_guest'] && isset($_GET['login']))
	ForumFunction::message($lang_common['No cookie']);

// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid (token in post.php is dealt with in post.php)
if (!empty($_POST) && (isset($_POST['confirm_cancel']) || (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ForumFunction::generate_form_token(ForumFunction::get_current_url()))) && !defined('FORUM_SKIP_CSRF_CONFIRM'))
	ForumFunction::csrf_confirm_form();

($hook = ForumFunction::get_hook('co_common')) ? eval($hook) : null;

$forum_page = [];

$c['forum_flash'] = $forum_flash;
$c['templates'] = new League\Plates\Engine(FORUM_ROOT.'../templates/Oxygen/View');
$c['templates']->addData([
    'container'     => get_container(),
    'lang_common'   => $lang_common,
    'forum_config'  => $forum_config,
    'forum_user'    => $forum_user,
    'forum_start'   => $forum_start,
    'forum_db'      => $forum_db,
    'forum_url'    => $forum_url,
    'forum_page'    => & $forum_page 
]);

$c['forum_db'] = $forum_db;

$gateways = [
    'ViewforumGateway',
    'IndexGateway',
    'ViewtopicGateway',
    'UserlistGateway',
    'LoginGateway',
    'PostGateway',
    'ProfileGateway',
    'SearchGateway',
    'ModerateGateway',
    'DeleteGateway',
    'EditGateway'
];

foreach ($gateways as $cur_gateway) {
    $GLOBALS['c'][$cur_gateway] = function ($c) use ($cur_gateway) {
        $gateway = 'Punbb\\Data\\' . $cur_gateway;
        return new $gateway($c['forum_db']);
    };
}

$c['breadcrumbs'] = function ($c) {
    return new \Creitive\Breadcrumbs\Breadcrumbs;
};

$c['SearchFunction'] = function ($c) {
    return new \Punbb\SearchFunctions(new Pimple\Psr11\Container($c));
};
