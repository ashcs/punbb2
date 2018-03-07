<?php $this->layout('main') ?>

<?php ($hook = \Punbb\ForumFunction::get_hook('se_results_output_start')) ? eval($hook) : null;

	// Load the forum.php language file
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/index.php';

	$forum_page['cur_category'] = $forum_page['cat_count'] = $forum_page['item_count'] = 0;


	$forum_page['item_count'] = 0;

	// Finally, lets loop through the results and output them
	foreach ($search_set as $cur_set)
	{
		($hook = \Punbb\ForumFunction::get_hook('se_results_loop_start')) ? eval($hook) : null;

		++$forum_page['item_count'];

		if ($forum_config['o_censoring'] == '1')
			$cur_set['subject'] = \Punbb\ForumFunction::censor_words($cur_set['subject']);


			if ($cur_set['cid'] != $forum_page['cur_category'])	// A new category since last iteration?
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

				($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_pre_cat_head')) ? eval($hook) : null;

				$forum_page['cur_category'] = $cur_set['cid'];

?>
				<div class="main-head">
					<h2 class="hn"><span><?php echo \Punbb\ForumFunction::forum_htmlencode($cur_set['cat_name']) ?></span></h2>
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
			if ($cur_set['redirect_url'] != '')
			{
				$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><a class="external" href="'.\Punbb\ForumFunction::forum_htmlencode($cur_forum['redirect_url']).'" title="'.sprintf($lang_index['Link to'], \Punbb\ForumFunction::forum_htmlencode($cur_forum['redirect_url'])).'"><span>'.\Punbb\ForumFunction::forum_htmlencode($cur_set['forum_name']).'</span></a></h3>';
				$forum_page['item_status']['redirect'] = 'redirect';

				if ($cur_set['forum_desc'] != '')
					$forum_page['item_subject']['desc'] = $cur_set['forum_desc'];

				$forum_page['item_subject']['redirect'] = '<span>'.$lang_index['External forum'].'</span>';

				($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_redirect_pre_item_subject_merge')) ? eval($hook) : null;

				if (!empty($forum_page['item_subject']))
					$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';

				// Forum topic and post count
				$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><span class="label">'.$lang_index['No topic info'].'</span></li>';
				$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><span class="label">'.$lang_index['No post info'].'</span></li>';
				$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['No lastpost info'].'</span></li>';

				($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_redirect_pre_display')) ? eval($hook) : null;
			}
			else
			{
				// Setup the title and link to the forum
				$forum_page['item_title']['title'] = '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['forum'], array($cur_set['fid'], \Punbb\ForumFunction::sef_friendly($cur_set['forum_name']))).'"><span>'.\Punbb\ForumFunction::forum_htmlencode($cur_set['forum_name']).'</span></a>';

				($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_redirect_pre_item_title_merge')) ? eval($hook) : null;

				$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.implode(' ', $forum_page['item_title']).'</h3>';

				// Setup the forum description and mod list
				if ($cur_set['forum_desc'] != '')
					$forum_page['item_subject']['desc'] = $cur_set['forum_desc'];

				($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_normal_pre_item_subject_merge')) ? eval($hook) : null;

				if (!empty($forum_page['item_subject']))
					$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';

				// Setup forum topics, post count and last post
				$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_set['num_topics']).'</strong> <span class="label">'.(($cur_set['num_topics'] == 1) ? $lang_index['topic'] : $lang_index['topics']).'</span></li>';
				$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_set['num_posts']).'</strong> <span class="label">'.(($cur_set['num_posts'] == 1) ? $lang_index['post'] : $lang_index['posts']).'</span></li>';

				if ($cur_set['last_post'] != '')
					$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['Last post'].'</span> <strong><a href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_set['last_post_id']).'">'.\Punbb\ForumFunction::format_time($cur_set['last_post']).'</a></strong> <cite>'.sprintf($lang_index['Last poster'], \Punbb\ForumFunction::forum_htmlencode($cur_set['last_poster'])).'</cite></li>';
				else
					$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><strong>'.$lang_common['Never'].'</strong></li>';

				($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_normal_pre_display')) ? eval($hook) : null;
			}

			// Generate classes for this forum depending on its status
			$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

			($hook = \Punbb\ForumFunction::get_hook('se_results_forums_row_pre_display')) ? eval($hook) : null;

?>
			<div id="forum<?php echo $cur_set['fid'] ?>" class="main-item<?php echo $forum_page['item_style'] ?>">
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
?>
	</div>

	<div class="main-foot">
<?php

	if (!empty($forum_page['main_foot_options']))
		echo "\n\t\t\t".'<p class="options">'.implode(' ', $forum_page['main_foot_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
<?php

	($hook = \Punbb\ForumFunction::get_hook('se_results_end')) ? eval($hook) : null;
