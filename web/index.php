<?php

use Punbb\Loader;
use Punbb\ForumFunction;
use Punbb\Cache;
use Punbb\FlashMessenger;
use Pimple\Container;

if (version_compare(PHP_VERSION, '5.6.0', '<')) exit('To start engine PHP 5.6.0 version minimum requred. Current version is ' . PHP_VERSION);
    
define('FORUM_ROOT', './');

ini_set('display_errors', true);

list($usec, $sec) = explode(' ', microtime());
$forum_start = ((float)$usec + (float)$sec);

$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

// Attempt to load the configuration file config.php
if (file_exists(FORUM_ROOT . 'config.php')) {
    include FORUM_ROOT . 'config.php';
}
    
defined('FORUM') or ForumFunction::error('The file \'config.php\' doesn\'t exist or is corrupt.<br />Please run <a href="' . FORUM_ROOT . 'admin/install.php">install.php</a> to install PunBB first.');
        
require FORUM_ROOT.'include/constants.php';
// Load UTF-8 functions
require FORUM_ROOT.'include/utf8/utf8.php';
require FORUM_ROOT.'include/utf8/ucwords.php';
require FORUM_ROOT.'include/utf8/trim.php';

$c = new Container();

function get_container() {
    return $GLOBALS['c'];
}

/** @var \Zend\Diactoros\Response $response */
$response = new \Zend\Diactoros\Response;

/** @var \Zend\Diactoros\ServerRequest $request */
$request =  \Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$emitter = new  \Zend\Diactoros\Response\SapiEmitter;

$forum_loader = Loader::get_instance();

// Ignore any user abort requests
ignore_user_abort(true);
    

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch') {
    // Send no-cache headers
    $response = $response->withStatus('403', 'Prefetching Forbidden')
        ->withHeader('Expires', 'Thu, 21 Jul 1977 07:30:00 GMT')
        ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
        ->withAddedHeader('Cache-Control', 'post-check=0, pre-check=0')
        ->withHeader('Pragma', 'no-cache');
    
    $emitter->emit($response);
    exit();
}

// Make sure PHP reports all errors except E_NOTICE. PunBB supports E_ALL, but a lot of scripts it may interact with, do not.
if (defined('FORUM_DEBUG'))
    error_reporting(E_ALL);
else
    error_reporting(E_ALL ^ E_NOTICE);

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// If the cache directory is not specified, we use the default setting
defined('FORUM_CACHE_DIR') or define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Load DB abstraction layer and connect
$dbClassName = 'Punbb\\'.ucfirst($db_type);
$forum_db = new $dbClassName($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);

// Start a transaction
$forum_db->start_transaction();

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
    include FORUM_CACHE_DIR.'cache_config.php';

if (! defined('FORUM_CONFIG_LOADED')) {
    Cache::generate_config_cache();
    require FORUM_CACHE_DIR . 'cache_config.php';
}

// Verify that we are running the proper database schema revision
if ($forum_config['o_database_revision'] < FORUM_DB_REVISION || version_compare($forum_config['o_cur_version'], FORUM_VERSION, '<'))
    ForumFunction::error('Your PunBB database is out-of-date and must be upgraded in order to continue.<br />Please run <a href="'.$base_url.'/admin/db_update.php">db_update.php</a> in order to complete the upgrade process.');
    
$forum_flash = new FlashMessenger();

// Strip out "bad" UTF-8 characters
ForumFunction::forum_remove_bad_characters();
    
    // If a cookie name is not specified in config.php, we use the default (forum_cookie)
if (empty($cookie_name))
    $cookie_name = 'forum_cookie';

// Enable output buffering
if (! defined('FORUM_DISABLE_BUFFERING')) {
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
if (file_exists(FORUM_ROOT . 'lang/' . $forum_user['language'] . '/common.php'))
    include FORUM_ROOT . 'lang/' . $forum_user['language'] . '/common.php';
else
    ForumFunction::error('There is no valid language pack \'' . ForumFunction::forum_htmlencode($forum_user['language']) . '\' installed.<br />Please reinstall a language of that name.');

// Setup the URL rewriting scheme
if ($forum_config['o_sef'] != 'Default' && file_exists(FORUM_ROOT . 'include/url/' . $forum_config['o_sef'] . '/forum_urls.php'))
    require FORUM_ROOT . 'include/url/' . $forum_config['o_sef'] . '/forum_urls.php';
else
    require FORUM_ROOT . 'include/url/Default/forum_urls.php';

// Check if we are to display a maintenance message
if ($forum_config['o_maintenance'] && $forum_user['g_id'] > FORUM_ADMIN && ! defined('FORUM_TURN_OFF_MAINT'))
    ForumFunction::maintenance_message();

// Check if current user is banned
ForumFunction::check_bans();

// Update online list
ForumFunction::update_users_online();

// Check to see if we logged in without a cookie being set
if ($forum_user['is_guest'] && isset($_GET['login']))
    ForumFunction::message($lang_common['No cookie']);

// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid (token in post.php is dealt with in post.php)
if (! empty($_POST) && (isset($_POST['confirm_cancel']) || (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ForumFunction::generate_form_token(ForumFunction::get_current_url()))) && ! defined('FORUM_SKIP_CSRF_CONFIRM'))
    ForumFunction::csrf_confirm_form();

$forum_page = [];

$c['forum_flash'] = $forum_flash;
$c['templates'] = new League\Plates\Engine(FORUM_ROOT . '../templates/Oxygen/View');
$c['templates']->addData([
    'container' => $c,
    'lang_common' => $lang_common,
    'forum_config' => $forum_config,
    'forum_user' => $forum_user,
    'forum_start' => $forum_start,
    'forum_db' => $forum_db,
    'forum_url' => $forum_url,
    'forum_page' => & $forum_page
]);

$c['forum_db'] = $forum_db;

$gateways = [
    'ForumGateway',
    'IndexGateway',
    'TopicGateway',
    'UserlistGateway',
    'LoginGateway',
    'PostGateway',
    'ProfileGateway',
    'SearchGateway',
    'ModerateGateway',
    'DeleteGateway',
    'EditGateway',
    'MiscGateway'
];

$c['user'] = $forum_user;
$c['config'] = $forum_config;
$c['url'] = $forum_url;
$c['lang_common'] = $lang_common;
$c['request'] = $request;
$c['response'] = $response;

foreach ($gateways as $cur_gateway) {
    $GLOBALS['c'][$cur_gateway] = function ($c) use ($cur_gateway) {
        $gateway = 'Punbb\\Data\\' . $cur_gateway;
        return new $gateway($c['forum_db']);
    };
}

$c['breadcrumbs'] = function ($c) {
    return new \Creitive\Breadcrumbs\Breadcrumbs();
};

$c['SearchFunction'] = function ($c) {
    return new \Punbb\SearchFunctions(new Pimple\Psr11\Container($c));
};

// Bring in all the rewrite rules
if (file_exists(FORUM_ROOT.'include/url/'.$forum_config['o_sef'].'/rewrite_rules.php'))
    require FORUM_ROOT.'include/url/'.$forum_config['o_sef'].'/rewrite_rules.php';
else
    require FORUM_ROOT.'include/url/Default/rewrite_rules.php';

$matches = [];
$params = [];

if ('' == ($request_uri = substr($request->getUri()->getPath(), 1))) {
    $request_uri = '/';
}

foreach ($forum_rewrite_rules as $rule => $rewrite_to) {
    if (preg_match($rule, $request_uri, $matches))  {
        break;
    }
}

if (empty($matches)) {
    $response = $response->withStatus('404', 'Not Found');
    $response->getBody()->write('Page Not found (Error 404):<br />The requested page <em>'.ForumFunction::forum_htmlencode($request->getUri()).'</em> could not be found.');
    $emitter->emit($response);
    exit();
}

if (isset($rewrite_to[1])) {
    foreach ($rewrite_to[1] as $pos => $key) {
        if (isset($matches[$pos])) {
            $params[$key] = $matches[$pos];
            $request = $request->withAttribute($key, $matches[$pos]);
        }
    }
}

$http_method = $request->getMethod();

if (!isset($rewrite_to[0][$http_method])) {
    $response = $response->withStatus('405', 'Method Not Allowed');
    $response->getBody()->write('Method Not Allowed (Error 405):<br />The requested method for this page is not allowed.');
    $emitter->emit($response);
    exit();
}


$callable = '\\Punbb\\Controllers\\' . $rewrite_to[0][$http_method][0];

$controller = new $callable($c);

defined('FORUM_PAGE_TYPE') or define('FORUM_PAGE_TYPE', 'page-default');

$result = call_user_func_array(
    [$controller, $rewrite_to[0][$http_method][1]], $params
);

$response->getBody()->write($result);

$emitter->emit($response);

