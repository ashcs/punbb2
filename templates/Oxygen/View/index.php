<?php $this->layout('main') ?>

<?php 

$forum_page['cur_category'] = $forum_page['cat_count'] = $forum_page['item_count'] = 0;

foreach ($forums as $cur_forum)
{
	($hook = \Punbb\ForumFunction::get_hook('in_forum_loop_start')) ? eval($hook) : null;

	++$forum_page['item_count'];

	if ($cur_forum['cid'] != $forum_page['cur_category'])	// A new category since last iteration?
	{
		if ($forum_page['cur_category'] != 0)
			echo "\t".'</div>'."\n";

		++$forum_page['cat_count'];
		$forum_page['item_count'] = 1;

		$forum_page['item_header'] = array();
		$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.$lang_index['Forums'].'</strong>';
		$forum_page['item_header']['info']['topics'] = '<strong class="info-topics">'.$lang_index['topics'].'</strong>';
		$forum_page['item_header']['info']['post'] = '<strong class="info-posts">'.$lang_index['posts'].'</strong>';
		$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.$lang_index['last post'].'</strong>';

		($hook = \Punbb\ForumFunction::get_hook('in_forum_pre_cat_head')) ? eval($hook) : null;

		$forum_page['cur_category'] = $cur_forum['cid'];

?>	<div class="main-head">
		<h2 class="hn"><span><?php echo \Punbb\ForumFunction::forum_htmlencode($cur_forum['cat_name']) ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary"><span><?php printf($lang_index['Category subtitle'], implode(' ', $forum_page['item_header']['subject']), implode(', ', $forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div id="category<?php echo $forum_page['cat_count'] ?>" class="main-content main-category">
<?php

	}

	// Reset arrays and globals for each forum
	$forum_page['item_status'] = $forum_page['item_subject'] = $forum_page['item_body'] = $forum_page['item_title'] = array();

	// Is this a redirect forum?
	if ($cur_forum['redirect_url'] != '')
	{
		$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><a class="external" href="'.\Punbb\ForumFunction::forum_htmlencode($cur_forum['redirect_url']).'" title="'.sprintf($lang_index['Link to'], \Punbb\ForumFunction::forum_htmlencode($cur_forum['redirect_url'])).'"><span>'.\Punbb\ForumFunction::forum_htmlencode($cur_forum['forum_name']).'</span></a></h3>';
		$forum_page['item_status']['redirect'] = 'redirect';

		if ($cur_forum['forum_desc'] != '')
			$forum_page['item_subject']['desc'] = $cur_forum['forum_desc'];

		$forum_page['item_subject']['redirect'] = '<span>'.$lang_index['External forum'].'</span>';

		($hook = \Punbb\ForumFunction::get_hook('in_redirect_row_pre_item_subject_merge')) ? eval($hook) : null;

		if (!empty($forum_page['item_subject']))
			$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';

		// Forum topic and post count
		$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><span class="label">'.$lang_index['No topic info'].'</span></li>';
		$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><span class="label">'.$lang_index['No post info'].'</span></li>';
		$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['No lastpost info'].'</span></li>';

		($hook = \Punbb\ForumFunction::get_hook('in_redirect_row_pre_display')) ? eval($hook) : null;
	}
	else
	{
		// Setup the title and link to the forum
		$forum_page['item_title']['title'] = '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['forum'], array($cur_forum['fid'], \Punbb\ForumFunction::sef_friendly($cur_forum['forum_name']))).'"><span>'.\Punbb\ForumFunction::forum_htmlencode($cur_forum['forum_name']).'</span></a>';

		// Are there new posts since our last visit?
		if (!$forum_user['is_guest'] && $cur_forum['last_post'] > $forum_user['last_visit'] && (empty($tracked_topics['forums'][$cur_forum['fid']]) || $cur_forum['last_post'] > $tracked_topics['forums'][$cur_forum['fid']]))
		{
			// There are new posts in this forum, but have we read all of them already?
		    if (! empty($new_topics))
			foreach ($new_topics[$cur_forum['fid']] as $check_topic_id => $check_last_post)
			{
				if ((empty($tracked_topics['topics'][$check_topic_id]) || $tracked_topics['topics'][$check_topic_id] < $check_last_post) && (empty($tracked_topics['forums'][$cur_forum['fid']]) || $tracked_topics['forums'][$cur_forum['fid']] < $check_last_post))
				{
					$forum_page['item_status']['new'] = 'new';
					$forum_page['item_title']['status'] = '<small>'.sprintf($lang_index['Forum has new'], '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['search_new_results'], $cur_forum['fid']).'" title="'.$lang_index['New posts title'].'">'.$lang_index['Forum new posts'].'</a>').'</small>';

					break;
				}
			}
		}

		($hook = \Punbb\ForumFunction::get_hook('in_normal_row_pre_item_title_merge')) ? eval($hook) : null;

		$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.implode(' ', $forum_page['item_title']).'</h3>';


		// Setup the forum description and mod list
		if ($cur_forum['forum_desc'] != '')
			$forum_page['item_subject']['desc'] = $cur_forum['forum_desc'];

			if ($forum_config['o_show_moderators'] == '1' && $cur_forum['moderators'] != '')
		{
			$forum_page['mods_array'] = unserialize($cur_forum['moderators']);
			$forum_page['item_mods'] = array();

			foreach ($forum_page['mods_array'] as $mod_username => $mod_id)
				$forum_page['item_mods'][] = ($forum_user['g_view_users'] == '1') ? '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $mod_id).'">'.\Punbb\ForumFunction::forum_htmlencode($mod_username).'</a>' : \Punbb\ForumFunction::forum_htmlencode($mod_username);

			($hook = \Punbb\ForumFunction::get_hook('in_row_modify_modlist')) ? eval($hook) : null;

			$forum_page['item_subject']['modlist'] = '<span class="modlist">'.sprintf($lang_index['Moderated by'], implode(', ', $forum_page['item_mods'])).'</span>';
		}

		($hook = \Punbb\ForumFunction::get_hook('in_normal_row_pre_item_subject_merge')) ? eval($hook) : null;

		if (!empty($forum_page['item_subject']))
			$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';


		// Setup forum topics, post count and last post
		$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_forum['num_topics']).'</strong> <span class="label">'.(($cur_forum['num_topics'] == 1) ? $lang_index['topic'] : $lang_index['topics']).'</span></li>';
		$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_forum['num_posts']).'</strong> <span class="label">'.(($cur_forum['num_posts'] == 1) ? $lang_index['post'] : $lang_index['posts']).'</span></li>';

		if ($cur_forum['last_post'] != '')
			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['Last post'].'</span> <strong><a href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_forum['last_post_id']).'">'.\Punbb\ForumFunction::format_time($cur_forum['last_post']).'</a></strong> <cite>'.sprintf($lang_index['Last poster'], \Punbb\ForumFunction::forum_htmlencode($cur_forum['last_poster'])).'</cite></li>';
		else
			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><strong>'.$lang_common['Never'].'</strong></li>';

		($hook = \Punbb\ForumFunction::get_hook('in_normal_row_pre_display')) ? eval($hook) : null;
	}

	// Generate classes for this forum depending on its status
	$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

	($hook = \Punbb\ForumFunction::get_hook('in_row_pre_display')) ? eval($hook) : null;

?>		<div id="forum<?php echo $cur_forum['fid'] ?>" class="main-item<?php echo $forum_page['item_style'] ?>">
			<span class="icon <?php echo implode(' ', $forum_page['item_status']) ?>"><!-- --></span>
			<div class="item-subject">
				<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['subject'])."\n" ?>
			</div>
			<ul class="item-info">
				<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['info'])."\n" ?>
			</ul>
		</div>
<?php

}
// Did we output any categories and forums?
if ($forum_page['cur_category'] > 0)
{

?>	</div>
<?php

}
else
{

?>	<div class="main-head">
		<h2 class="hn"><span><?php echo $lang_common['Forum message']?></span></h2>
	</div>
	<div class="main-content main-message">
		<p><?php echo $lang_index['Empty board'] ?></p>
	</div>
<?php

}

?>
<div id="brd-stats" class="gen-content">
	<h2 class="hn"><span><?php echo $lang_index['Statistics'] ?></span></h2>
	<ul>
	<li class="st-users"><span><?= sprintf($lang_index['No of users'], '<strong>'.\Punbb\ForumFunction::forum_number_format($forum_stats['total_ users']).'</strong>') ?></span></li>
	<li class="st-users"><span><?= sprintf($lang_index['Newest user'], '<strong>'.($forum_user['g_view_users'] == '1' ? '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $forum_stats['last_user']['id']).'">'.\Punbb\ForumFunction::forum_htmlencode($forum_stats['last_user']['username']).'</a>' : \Punbb\ForumFunction::forum_htmlencode($forum_stats['last_user']['username'])).'</strong>') ?></span></li>
	<li class="st-activity"><span><?= sprintf($lang_index['No of topics'], '<strong>'.\Punbb\ForumFunction::forum_number_format($forum_stats['total_topics']).'</strong>') ?></span></li>
	<li class="st-activity"><span><?= sprintf($lang_index['No of posts'], '<strong>'.\Punbb\ForumFunction::forum_number_format($forum_stats['total_posts']).'</strong>') ?></span></li>
	</ul>
</div>
<?php


if ($forum_config['o_users_online'] == '1')
{
	$Online = new \Punbb\Data\OnlineGateway($forum_db);
	$user_online = $Online->getUserOnline();
	
	$forum_page['num_guests'] = $forum_page['num_users'] = 0;
	$users = array();

	foreach ($user_online as $forum_user_online)
	{
		($hook = \Punbb\ForumFunction::get_hook('in_users_online_add_online_user_loop')) ? eval($hook) : null;

		if ($forum_user_online['user_id'] > 1)
		{
			$users[] = ($forum_user['g_view_users'] == '1') ? '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $forum_user_online['user_id']).'">'.\Punbb\ForumFunction::forum_htmlencode($forum_user_online['ident']).'</a>' : \Punbb\ForumFunction::forum_htmlencode($forum_user_online['ident']);
			++$forum_page['num_users'];
		}
		else
			++$forum_page['num_guests'];
	}

	$forum_page['online_info'] = array();
	$forum_page['online_info']['guests'] = ($forum_page['num_guests'] == 0) ? $lang_index['Guests none'] : sprintf((($forum_page['num_guests'] == 1) ? $lang_index['Guests single'] : $lang_index['Guests plural']), \Punbb\ForumFunction::forum_number_format($forum_page['num_guests']));
	$forum_page['online_info']['users'] = ($forum_page['num_users'] == 0) ? $lang_index['Users none'] : sprintf((($forum_page['num_users'] == 1) ? $lang_index['Users single'] : $lang_index['Users plural']), \Punbb\ForumFunction::forum_number_format($forum_page['num_users']));

	($hook = \Punbb\ForumFunction::get_hook('in_users_online_pre_online_info_output')) ? eval($hook) : null;
?>
<div id="brd-online" class="gen-content">
	<h3 class="hn"><span><?php printf($lang_index['Currently online'], implode($lang_index['Online stats separator'], $forum_page['online_info'])) ?></span></h3>
<?php if (!empty($users)): ?>
	<p><?php echo implode($lang_index['Online list separator'], $users) ?></p>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('in_new_online_data')) ? eval($hook) : null; ?>
</div>
<?php

	($hook = \Punbb\ForumFunction::get_hook('in_users_online_end')) ? eval($hook) : null;
}

