<?php
/**
 * Displays a list of the categories/forums that the current user can see, along with some statistics.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */

use Punbb\ForumFunction;
use Punbb\Cache;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');

require FORUM_ROOT.'include/common.php'; 

($hook = ForumFunction::get_hook('in_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);

// Load the index.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/index.php';

// Get list of forums and topics with new posts since last visit
if (!$forum_user['is_guest'])
{
    $new_topics = $c['IndexGateway']->getNewTopic($forum_user);
	$tracked_topics = ForumFunction::get_tracked_topics();
}
else {
    $new_topics = [];
}

$forum_page['main_title'] = ForumFunction::forum_htmlencode($forum_config['o_board_title']);
$forum_page['cur_category'] = $forum_page['cat_count'] = $forum_page['item_count'] = 0;

$c['breadcrumbs']->addCrumb($forum_config['o_board_title']);

define('FORUM_PAGE', 'index');

echo $c['templates']->render('index', [
    'new_topics'   => $new_topics,
    'forums'        => $c['IndexGateway']->getForums($forum_user),
    'lang_index'    => $lang_index,
    'forum_stats'   => Cache::load_stats()
]);

exit;
