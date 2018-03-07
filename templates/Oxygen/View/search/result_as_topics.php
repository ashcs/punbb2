<?php $this->layout('main') ?>

<?php ($hook = \Punbb\ForumFunction::get_hook('se_results_output_start')) ? eval($hook) : null;


    // Load the forum.php language file
    require FORUM_ROOT.'lang/'.$forum_user['language'].'/forum.php';
    
    $forum_page['item_header'] = array();
    $forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.$lang_forum['Topics'].'</strong>';
    $forum_page['item_header']['info']['forum'] = '<strong class="info-forum">'.$lang_forum['Forum'].'</strong>';
    $forum_page['item_header']['info']['replies'] = '<strong class="info-replies">'.$lang_forum['replies'].'</strong>';
    $forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.$lang_forum['last post'].'</strong>';
    
    ($hook = \Punbb\ForumFunction::get_hook('se_results_topics_pre_item_header_output')) ? eval($hook) : null;
    
    ?>

	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary forum-noview"><span><?php printf($lang_forum['Search subtitle'], implode(' ', $forum_page['item_header']['subject']), implode(', ', $forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div class="main-content main-forum forum-forums">
<?php

	$forum_page['item_count'] = 0;

	// Finally, lets loop through the results and output them
	foreach ($search_set as $cur_set)
	{
		($hook = \Punbb\ForumFunction::get_hook('se_results_loop_start')) ? eval($hook) : null;

		++$forum_page['item_count'];

		if ($forum_config['o_censoring'] == '1')
			$cur_set['subject'] = \Punbb\ForumFunction::censor_words($cur_set['subject']);

			// Start from scratch
			$forum_page['item_subject'] = $forum_page['item_body'] = $forum_page['item_status'] = $forum_page['item_nav'] = $forum_page['item_title'] = $forum_page['item_title_status'] = array();

			// Assemble the Topic heading

			// Should we display the dot or not? :)
			if (!$forum_user['is_guest'] && $forum_config['o_show_dot'] == '1' && $cur_set['has_posted'] == $forum_user['id'])
			{
				$forum_page['item_title']['posted'] = '<span class="posted-mark">'.$lang_forum['You posted indicator'].'</span>';
				$forum_page['item_status']['posted'] = 'posted';
			}

			if ($cur_set['sticky'] == '1')
			{
				$forum_page['item_title_status']['sticky'] = '<em class="sticky">'.$lang_forum['Sticky'].'</em>';
				$forum_page['item_status']['sticky'] = 'sticky';
			}

			if ($cur_set['closed'] != '0')
			{
				$forum_page['item_title_status']['closed'] = '<em class="closed">'.$lang_forum['Closed'].'</em>';
				$forum_page['item_status']['closed'] = 'closed';
			}

			($hook = \Punbb\ForumFunction::get_hook('se_results_topics_row_pre_item_subject_status_merge')) ? eval($hook) : null;

			if (!empty($forum_page['item_title_status']))
				$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf($lang_forum['Item status'], implode(', ', $forum_page['item_title_status'])).'</span>';

			$forum_page['item_title']['link'] = '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($cur_set['tid'], \Punbb\ForumFunction::sef_friendly($cur_set['subject']))).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_set['subject']).'</a>';

			($hook = \Punbb\ForumFunction::get_hook('se_results_topics_row_pre_item_title_merge')) ? eval($hook) : null;

			$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span> '.implode(' ', $forum_page['item_title']).'</h3>';

			$forum_page['item_pages'] = ceil(($cur_set['num_replies'] + 1) / $forum_user['disp_posts']);

			if ($forum_page['item_pages'] > 1)
				$forum_page['item_nav']['pages'] = '<span>'.$lang_forum['Pages'].'&#160;</span>'.\Punbb\ForumFunction::paginate($forum_page['item_pages'], -1, $forum_url['topic'], $lang_common['Page separator'], array($cur_set['tid'], \Punbb\ForumFunction::sef_friendly($cur_set['subject'])));

			// Does this topic contain posts we haven't read? If so, tag it accordingly.
			if (!$forum_user['is_guest'] && $cur_set['last_post'] > $forum_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_set['tid']]) || $tracked_topics['topics'][$cur_set['tid']] < $cur_set['last_post']) && (!isset($tracked_topics['forums'][$cur_set['forum_id']]) || $tracked_topics['forums'][$cur_set['forum_id']] < $cur_set['last_post']))
			{
				$forum_page['item_nav']['new'] = '<em class="item-newposts"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['topic_new_posts'], array($cur_set['tid'], \Punbb\ForumFunction::sef_friendly($cur_set['subject']))).'" title="'.$lang_forum['New posts info'].'">'.$lang_forum['New posts'].'</a></em>';
				$forum_page['item_status']['new'] = 'new';
			}

			($hook = \Punbb\ForumFunction::get_hook('se_results_topics_row_pre_item_nav_merge')) ? eval($hook) : null;

			$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf($lang_forum['Topic starter'], \Punbb\ForumFunction::forum_htmlencode($cur_set['poster'])).'</span>';

			if (!empty($forum_page['item_nav']))
				$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf($lang_forum['Topic navigation'], implode('&#160;&#160;', $forum_page['item_nav'])).'</span>';

			($hook = \Punbb\ForumFunction::get_hook('se_results_topics_row_pre_item_subject_merge')) ? eval($hook) : null;

			$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';

			if (empty($forum_page['item_status']))
				$forum_page['item_status']['normal'] = 'normal';

			($hook = \Punbb\ForumFunction::get_hook('se_results_topics_pre_item_status_merge')) ? eval($hook) : null;

			$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

			$forum_page['item_body']['info']['forum'] = '<li class="info-forum"><span class="label">'.$lang_search['Posted in'].'</span><a href="'.\Punbb\ForumFunction::forum_link($forum_url['forum'], array($cur_set['forum_id'], \Punbb\ForumFunction::sef_friendly($cur_set['forum_name']))).'">'.$cur_set['forum_name'].'</a></li>';
			$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_set['num_replies']).'</strong> <span class="label">'.(($cur_set['num_replies'] == 1) ? $lang_forum['Reply'] : $lang_forum['Replies']).'</span></li>';
			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_forum['Last post'].'</span> <strong><a href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_set['last_post_id']).'">'.\Punbb\ForumFunction::format_time($cur_set['last_post']).'</a></strong> <cite>'.sprintf($lang_forum['by poster'], \Punbb\ForumFunction::forum_htmlencode($cur_set['last_poster'])).'</cite></li>';

			($hook = \Punbb\ForumFunction::get_hook('se_results_topics_row_pre_display')) ? eval($hook) : null;

?>
		<div class="main-item<?php echo $forum_page['item_style'] ?>">
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
