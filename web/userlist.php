<?php
/**
 * Provides a list of forum users that can be sorted based on various criteria.
 *
 * @copyright (C) 2008-2018 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
use Punbb\ForumFunction;

defined('FORUM_ROOT') or define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = ForumFunction::get_hook('ul_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	ForumFunction::message($lang_common['No view']);
else if ($forum_user['g_view_users'] == '0')
	ForumFunction::message($lang_common['No permission']);

// Load the userlist.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/userlist.php';

// Miscellaneous setup
$forum_page['show_post_count'] = ($forum_config['o_show_post_count'] == '1' || $forum_user['is_admmod']) ? true : false;

$forum_page['username'] = '';
if (isset($_GET['username']) && is_string($_GET['username'])) {
	if ($_GET['username'] != '-' && $forum_user['g_search_users'] == '1') {
		$forum_page['username'] = $_GET['username'];
	}
}

$forum_page['show_group'] = (!isset($_GET['show_group']) || intval($_GET['show_group']) < -1 && intval($_GET['show_group']) > 2) ? -1 : intval($_GET['show_group']);
$forum_page['sort_by'] = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'username' && $_GET['sort_by'] != 'registered' && ($_GET['sort_by'] != 'num_posts' || !$forum_page['show_post_count'])) ? 'username' : $_GET['sort_by'];
$forum_page['sort_dir'] = (!isset($_GET['sort_dir']) || strtoupper($_GET['sort_dir']) != 'ASC' && strtoupper($_GET['sort_dir']) != 'DESC') ? 'ASC' : strtoupper($_GET['sort_dir']);

$forum_page['num_users'] = $c['UserlistGateway']->getUserCount($forum_page, $forum_user);

// Determine the user offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil($forum_page['num_users'] / 50);
$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : intval($_GET['p']);
$forum_page['start_from'] = 50 * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(50, ($forum_page['num_users']));

$c['breadcrumbs']->addCrumb($forum_config['o_board_title'], ForumFunction::forum_link($forum_url['index']));
$c['breadcrumbs']->addCrumb($lang_common['User list']);

define('FORUM_PAGE', 'userlist');

echo $c['templates']->render('userlist', [
    'groups'   =>  $c['UserlistGateway']->getUserGroups(),
    'lang_ul'    => $lang_ul,
    
    'forum_user'    => $forum_user,
    'base_url'      => $base_url,
    'founded_user_datas'    => $c['UserlistGateway']->getUsers($forum_page)
]);

exit;
