<?php
/**
 * Loads the minimum amount of data (eg: functions, database connection, config data, etc) necessary to integrate the site.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */

use Punbb\Loader;
use Punbb\ForumFunction;
use Punbb\Cache;
use Punbb\FlashMessenger;
use Pimple\Container;

ini_set('display_errors', 1);

// Record the start time (will be used to calculate the generation time for the page)
list($usec, $sec) = explode(' ', microtime());
$forum_start = ((float)$usec + (float)$sec);

if (!defined('FORUM_ROOT'))
	exit('The constant FORUM_ROOT must be defined and point to a valid PunBB installation root directory.');

// Detect UTF-8 support in PCRE
if (version_compare(PHP_VERSION, '5.3.0', '<'))
	exit('To start engine PHP 5.3.0 version minimum requred. Current version is ' . PHP_VERSION);

require FORUM_ROOT.'include/constants.php';

$autoloader = require dirname(__DIR__) . '/../vendor/autoload.php';

$c = new Container();

function get_container() {
    return $GLOBALS['c'];
}

$forum_loader = Loader::get_instance();
// Load UTF-8 functions
require FORUM_ROOT.'include/utf8/utf8.php';
require FORUM_ROOT.'include/utf8/ucwords.php';
require FORUM_ROOT.'include/utf8/trim.php';

// Ignore any user abort requests
ignore_user_abort(true);

// Attempt to load the configuration file config.php
if (file_exists(FORUM_ROOT.'config.php'))
	include FORUM_ROOT.'config.php';

if (!defined('FORUM'))
	ForumFunction::error('The file \'config.php\' doesn\'t exist or is corrupt.<br />Please run <a href="'.FORUM_ROOT.'admin/install.php">install.php</a> to install PunBB first.');

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
{
	header('HTTP/1.1 403 Prefetching Forbidden');

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');		// For HTTP/1.0 compability

	exit;
}

// Make sure PHP reports all errors except E_NOTICE. PunBB supports E_ALL, but a lot of scripts it may interact with, do not.
if (defined('FORUM_DEBUG'))
	error_reporting(E_ALL);
else
	error_reporting(E_ALL ^ E_NOTICE);


// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Load DB abstraction layer and connect
$dbClassName = 'Punbb\\'.ucfirst($db_type);
$forum_db = new $dbClassName($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);

// Start a transaction
$forum_db->start_transaction();

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
	include FORUM_CACHE_DIR.'cache_config.php';

if (!defined('FORUM_CONFIG_LOADED'))
{
	Cache::generate_config_cache();
	require FORUM_CACHE_DIR.'cache_config.php';
}

if (!isset($base_url))
{
	// Make an educated guess regarding base_url
	$base_url_guess = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);

	$base_url = $base_url_guess;
}

// Verify that we are running the proper database schema revision
if (defined('PUN') || !isset($forum_config['o_database_revision']) || $forum_config['o_database_revision'] < FORUM_DB_REVISION || version_compare($forum_config['o_cur_version'], FORUM_VERSION, '<'))
	ForumFunction::error('Your PunBB database is out-of-date and must be upgraded in order to continue.<br />Please run <a href="'.$base_url.'/admin/db_update.php">db_update.php</a> in order to complete the upgrade process.');


// Load hooks
if (file_exists(FORUM_CACHE_DIR.'cache_hooks.php'))
	include FORUM_CACHE_DIR.'cache_hooks.php';

if (!defined('FORUM_HOOKS_LOADED'))
{
	Cache::generate_hooks_cache();
	require FORUM_CACHE_DIR.'cache_hooks.php';
}

$forum_flash = new FlashMessenger();

// A good place to add common functions for your extension
($hook = ForumFunction::get_hook('es_essentials')) ? eval($hook) : null;

define('FORUM_ESSENTIALS_LOADED', 1);
