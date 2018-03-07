<?php $this->layout('main') ?>

<?php 	($hook = \Punbb\ForumFunction::get_hook('se_results_output_start')) ? eval($hook) : null;

		// Load the topic.php language file
		require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';

		// Load parser
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';
?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-content main-topic">
<?php



	$forum_page['item_count'] = 0;

	// Finally, lets loop through the results and output them
	foreach ($search_set as $cur_set)
	{
		($hook = \Punbb\ForumFunction::get_hook('se_results_loop_start')) ? eval($hook) : null;

		++$forum_page['item_count'];

		if ($forum_config['o_censoring'] == '1')
			$cur_set['subject'] = \Punbb\ForumFunction::censor_words($cur_set['subject']);

			// Generate the result heading
			$forum_page['post_ident'] = array();
			$forum_page['post_ident']['num'] = '<span class="post-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span>';
			$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_set['pid'] == $cur_set['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_set['pposter']).'</strong>').'</span>';
			$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_set['pid']).'">'.\Punbb\ForumFunction::format_time($cur_set['pposted']).'</a></span>';

			($hook = \Punbb\ForumFunction::get_hook('se_results_posts_row_pre_item_ident_merge')) ? eval($hook) : null;

			// Generate the topic title
			$forum_page['item_subject'] = '<a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink topic'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($cur_set['tid'], \Punbb\ForumFunction::sef_friendly($cur_set['subject']))).'">'.sprintf((($cur_set['pid'] == $cur_set['first_post_id']) ? $lang_topic['Topic title'] : $lang_topic['Reply title']), \Punbb\ForumFunction::forum_htmlencode($cur_set['subject'])).'</a> <small>'.sprintf($lang_topic['Search replies'], \Punbb\ForumFunction::forum_number_format($cur_set['num_replies']), '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['forum'], array($cur_set['forum_id'], \Punbb\ForumFunction::sef_friendly($cur_set['forum_name']))).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_set['forum_name']).'</a>').'</small>';

			// Generate author identification
			$forum_page['user_ident'] = ($cur_set['poster_id'] > 1 && $forum_user['g_view_users'] == '1') ? '<strong class="username"><a title="'.sprintf($lang_search['Go to profile'], \Punbb\ForumFunction::forum_htmlencode($cur_set['pposter'])).'" href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $cur_set['poster_id']).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_set['pposter']).'</a></strong>' : '<strong class="username">'.\Punbb\ForumFunction::forum_htmlencode($cur_set['pposter']).'</strong>';

			// Generate the post actions links
			$forum_page['post_actions'] = array();
			$forum_page['post_actions']['forum'] = '<span><a href="'.\Punbb\ForumFunction::forum_link($forum_url['forum'], array($cur_set['forum_id'], \Punbb\ForumFunction::sef_friendly($cur_set['forum_name']))).'">'.$lang_search['Go to forum'].'<span>: '.\Punbb\ForumFunction::forum_htmlencode($cur_set['forum_name']).'</span></a></span>';

			if ($cur_set['pid'] != $cur_set['first_post_id'])
				$forum_page['post_actions']['topic'] = '<span><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink topic'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($cur_set['tid'], \Punbb\ForumFunction::sef_friendly($cur_set['subject']))).'">'.$lang_search['Go to topic'].'<span>: '.\Punbb\ForumFunction::forum_htmlencode($cur_set['subject']).'</span></a></span>';

			$forum_page['post_actions']['post'] = '<span><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_set['pid']).'">'.$lang_search['Go to post'].'<span> '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';

			$forum_page['message'] = parse_message($cur_set['message'], $cur_set['hide_smilies']);

			// Give the post some class
			$forum_page['item_status'] = array(
				'post',
				(($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' )
			);

			if ($forum_page['item_count'] == 1)
				$forum_page['item_status']['firstpost'] = 'firstpost';

			if (($forum_page['start_from'] + $forum_page['item_count']) == $forum_page['finish_at'])
				$forum_page['item_status']['lastpost'] = 'lastpost';

			if ($cur_set['pid'] == $cur_set['first_post_id'])
				$forum_page['item_status']['topicpost'] = 'topicpost';


			($hook = \Punbb\ForumFunction::get_hook('se_results_posts_row_pre_display')) ? eval($hook) : null;

?>
	<div class="<?php echo implode(' ', $forum_page['item_status']) ?> resultpost">
		<div class="posthead">
			<h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
			<h4 class="hn post-title"><span><?php echo $forum_page['item_subject'] ?></span></h4>
		</div>
		<div class="postbody">
			<div class="post-entry">
				<div class="entry-content">
					<?php echo $forum_page['message'] ?>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('se_results_posts_row_new_post_entry_data')) ? eval($hook) : null; ?>
			</div>
		</div>
		<div class="postfoot">
			<div class="post-options">
				<p class="post-actions"><?php echo implode(' ', $forum_page['post_actions']) ?></p>
			</div>
		</div>
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