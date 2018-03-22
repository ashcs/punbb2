<?php $this->layout('main') ?>

<?php 


// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

// Sort out whether or not this user can post
$forum_user['may_post'] = (($cur_forum['post_topics'] == '' && $forum_user['g_post_topics'] == '1') || $cur_forum['post_topics'] == '1' || $forum_page['is_admmod']) ? true : false;


// Navigation links for header and page numbering for title/meta description
if ($forum_page['page'] < $forum_page['num_pages'])
{
    $forum_page['nav']['last'] = '<link rel="last" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['forum'], $forum_url['page'], $forum_page['num_pages'], array($id, \Punbb\ForumFunction::sef_friendly($cur_forum['forum_name']))).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
    $forum_page['nav']['next'] = '<link rel="next" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['forum'], $forum_url['page'], ($forum_page['page'] + 1), array($id, \Punbb\ForumFunction::sef_friendly($cur_forum['forum_name']))).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
}
if ($forum_page['page'] > 1)
{
    $forum_page['nav']['prev'] = '<link rel="prev" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['forum'], $forum_url['page'], ($forum_page['page'] - 1), array($id, \Punbb\ForumFunction::sef_friendly($cur_forum['forum_name']))).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
    $forum_page['nav']['first'] = '<link rel="first" href="'.\Punbb\ForumFunction::forum_link($forum_url['forum'], array($id, \Punbb\ForumFunction::sef_friendly($cur_forum['forum_name']))).'" title="'.$lang_common['Page'].' 1" />';
}


// Generate paging/posting links
$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.\Punbb\ForumFunction::paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['forum'], $lang_common['Paging separator'], array($id, \Punbb\ForumFunction::sef_friendly($cur_forum['forum_name']))).'</p>';

if ($forum_user['may_post'])
    $forum_page['page_post']['posting'] = '<p class="posting"><a class="newpost" href="'.\Punbb\ForumFunction::forum_link($forum_url['new_topic'], $id).'"><span>'.$lang_forum['Post topic'].'</span></a></p>';
    else if ($forum_user['is_guest'])
        $forum_page['page_post']['posting'] = '<p class="posting">'.sprintf($lang_forum['Login to post'], '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['login']).'">'.$lang_common['login'].'</a>', '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['register']).'">'.$lang_common['register'].'</a>').'</p>';
        else
            $forum_page['page_post']['posting'] = '<p class="posting">'.$lang_forum['No permission'].'</p>';
            
            // Setup main options
            $forum_page['main_head_options'] = $forum_page['main_foot_options'] = array();
            
            if (!empty($topics))
                $forum_page['main_head_options']['feed'] = '<span class="feed first-item"><a class="feed" href="'.\Punbb\ForumFunction::forum_link($forum_url['forum_rss'], $id).'">'.$lang_forum['RSS forum feed'].'</a></span>';
                
                if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1')
                {
                    if ($cur_forum['is_subscribed'])
                        $forum_page['main_head_options']['unsubscribe'] = '<span><a class="sub-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['forum_unsubscribe'], array($id, \Punbb\ForumFunction::generate_form_token('forum_unsubscribe'.$id.$forum_user['id']))).'"><em>'.$lang_forum['Unsubscribe'].'</em></a></span>';
                        else
                            $forum_page['main_head_options']['subscribe'] = '<span><a class="sub-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['forum_subscribe'], array($id, \Punbb\ForumFunction::generate_form_token('forum_subscribe'.$id.$forum_user['id']))).'" title="'.$lang_forum['Subscribe info'].'">'.$lang_forum['Subscribe'].'</a></span>';
                }
                
                if (!$forum_user['is_guest'] && !empty($topics))
                {
                    $forum_page['main_foot_options']['mark_read'] = '<span class="first-item"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['mark_forum_read'], array($id, \Punbb\ForumFunction::generate_form_token('markforumread'.$id.$forum_user['id']))).'">'.$lang_forum['Mark forum read'].'</a></span>';
                    
                    if ($forum_page['is_admmod'])
                        $forum_page['main_foot_options']['moderate'] = '<span'.(empty($forum_page['main_foot_options']) ? ' class="first-item"' : '').'><a href="'.\Punbb\ForumFunction::forum_sublink($forum_url['moderate_forum'], $forum_url['page'], $forum_page['page'], $id).'">'.$lang_forum['Moderate forum'].'</a></span>';
                }
                
                
                
                if ($forum_page['num_pages'] > 1)
                    $forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);
                    
                    ($hook = \Punbb\ForumFunction::get_hook('vf_pre_header_load')) ? eval($hook) : null;
                    

                    
                    $forum_page['item_header'] = array();
                    $forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.$lang_forum['Topics'].'</strong>';
                    $forum_page['item_header']['info']['replies'] = '<strong class="info-replies">'.$lang_forum['replies'].'</strong>';
                    
                    if ($forum_config['o_topic_views'] == '1')
                        $forum_page['item_header']['info']['views'] = '<strong class="info-views">'.$lang_forum['views'].'</strong>';
                        
                        $forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.$lang_forum['last post'].'</strong>';
                        
                        ($hook = \Punbb\ForumFunction::get_hook('vf_main_output_start')) ? eval($hook) : null;
                        
                        // If there are topics in this forum
                        if (!empty($topics))
                        {
                            
                            ?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary<?php echo ($forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>"><span><?php printf($lang_forum['Forum subtitle'], implode(' ', $forum_page['item_header']['subject']), implode(', ', $forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div id="forum<?php echo $id ?>" class="main-content main-forum<?php echo ($forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>">
<?php

	($hook = \Punbb\ForumFunction::get_hook('vf_pre_topic_loop_start')) ? eval($hook) : null;

	$forum_page['item_count'] = 0;

	foreach ($topics as $cur_topic)
	{
		($hook = \Punbb\ForumFunction::get_hook('vf_topic_loop_start')) ? eval($hook) : null;

		++$forum_page['item_count'];

		// Start from scratch
		$forum_page['item_subject'] = $forum_page['item_body'] = $forum_page['item_status'] = $forum_page['item_nav'] = $forum_page['item_title'] = $forum_page['item_title_status'] = array();

		if ($forum_config['o_censoring'] == '1')
			$cur_topic['subject'] = \Punbb\ForumFunction::censor_words($cur_topic['subject']);

		$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf($lang_forum['Topic starter'], \Punbb\ForumFunction::forum_htmlencode($cur_topic['poster'])).'</span>';

		if ($cur_topic['moved_to'] !== null)
		{
			$forum_page['item_status']['moved'] = 'moved';
			$forum_page['item_title']['link'] = '<span class="item-status"><em class="moved">'.sprintf($lang_forum['Item status'], $lang_forum['Moved']).'</em></span> <a href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($cur_topic['moved_to'], \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_topic['subject']).'</a>';

			// Combine everything to produce the Topic heading
			$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span>'.$forum_page['item_title']['link'].'</h3>';

			($hook = \Punbb\ForumFunction::get_hook('vf_topic_loop_moved_topic_pre_item_subject_merge')) ? eval($hook) : null;

			$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><span class="label">'.$lang_forum['No replies info'].'</span></li>';

			if ($forum_config['o_topic_views'] == '1')
				$forum_page['item_body']['info']['views'] = '<li class="info-views"><span class="label">'.$lang_forum['No views info'].'</span></li>';

			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_forum['No lastpost info'].'</span></li>';
		}
		else
		{
			// Assemble the Topic heading

			// Should we display the dot or not? :)
			if (!$forum_user['is_guest'] && $forum_config['o_show_dot'] == '1' && $cur_topic['has_posted'] == $forum_user['id'])
			{
				$forum_page['item_title']['posted'] = '<span class="posted-mark">'.$lang_forum['You posted indicator'].'</span>';
				$forum_page['item_status']['posted'] = 'posted';
			}

			if ($cur_topic['sticky'] == '1')
			{
				$forum_page['item_title_status']['sticky'] = '<em class="sticky">'.$lang_forum['Sticky'].'</em>';
				$forum_page['item_status']['sticky'] = 'sticky';
			}

			if ($cur_topic['closed'] == '1')
			{
				$forum_page['item_title_status']['closed'] = '<em class="closed">'.$lang_forum['Closed'].'</em>';
				$forum_page['item_status']['closed'] = 'closed';
			}

			($hook = \Punbb\ForumFunction::get_hook('vf_topic_loop_normal_topic_pre_item_title_status_merge')) ? eval($hook) : null;

			if (!empty($forum_page['item_title_status']))
				$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf($lang_forum['Item status'], implode(', ', $forum_page['item_title_status'])).'</span>';

			$forum_page['item_title']['link'] = '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($cur_topic['id'], \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_topic['subject']).'</a>';

			($hook = \Punbb\ForumFunction::get_hook('vf_topic_loop_normal_topic_pre_item_title_merge')) ? eval($hook) : null;

			$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span> '.implode(' ', $forum_page['item_title']).'</h3>';

			if (empty($forum_page['item_status']))
				$forum_page['item_status']['normal'] = 'normal';

			$forum_page['item_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);

			if ($forum_page['item_pages'] > 1)
				$forum_page['item_nav']['pages'] = '<span>'.$lang_forum['Pages'].'&#160;</span>'.\Punbb\ForumFunction::paginate($forum_page['item_pages'], -1, $forum_url['topic'], $lang_common['Page separator'], array($cur_topic['id'], \Punbb\ForumFunction::sef_friendly($cur_topic['subject'])));

			// Does this topic contain posts we haven't read? If so, tag it accordingly.
			if (!$forum_user['is_guest'] && $cur_topic['last_post'] > $forum_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$id]) || $tracked_topics['forums'][$id] < $cur_topic['last_post']))
			{
				$forum_page['item_nav']['new'] = '<em class="item-newposts"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['topic_new_posts'], array($cur_topic['id'], \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'">'.$lang_forum['New posts'].'</a></em>';
				$forum_page['item_status']['new'] = 'new';
			}

			($hook = \Punbb\ForumFunction::get_hook('vf_topic_loop_normal_topic_pre_item_nav_merge')) ? eval($hook) : null;

			if (!empty($forum_page['item_nav']))
				$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf($lang_forum['Topic navigation'], implode('&#160;&#160;', $forum_page['item_nav'])).'</span>';

			// Assemble the Topic subject

			$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_topic['num_replies']).'</strong> <span class="label">'.(($cur_topic['num_replies'] == 1) ? $lang_forum['reply'] : $lang_forum['replies']).'</span></li>';

			if ($forum_config['o_topic_views'] == '1')
				$forum_page['item_body']['info']['views'] = '<li class="info-views"><strong>'.\Punbb\ForumFunction::forum_number_format($cur_topic['num_views']).'</strong> <span class="label">'.(($cur_topic['num_views'] == 1) ? $lang_forum['view'] : $lang_forum['views']).'</span></li>';

			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_forum['Last post'].'</span> <strong><a href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_topic['last_post_id']).'">'.\Punbb\ForumFunction::format_time($cur_topic['last_post']).'</a></strong> <cite>'.sprintf($lang_forum['by poster'], \Punbb\ForumFunction::forum_htmlencode($cur_topic['last_poster'])).'</cite></li>';
		}

		($hook = \Punbb\ForumFunction::get_hook('vf_row_pre_item_subject_merge')) ? eval($hook) : null;

		$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';

		($hook = \Punbb\ForumFunction::get_hook('vf_row_pre_item_status_merge')) ? eval($hook) : null;

		$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

		($hook = \Punbb\ForumFunction::get_hook('vf_row_pre_display')) ? eval($hook) : null;

?>
		<div id="topic<?php echo $cur_topic['id'] ?>" class="main-item<?php echo $forum_page['item_style'] ?>">
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

}
// Else there are no topics in this forum
else
{
	$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.$lang_forum['No topics'].'</h3>';
	$forum_page['item_body']['subject']['desc'] = '<p>'.$lang_forum['First topic nag'].'</p>';

	($hook = \Punbb\ForumFunction::get_hook('vf_no_results_row_pre_display')) ? eval($hook) : null;

?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';
?>
		<h2 class="hn"><span><?php echo $lang_forum['Empty forum'] ?></span></h2>
	</div>
	<div id="forum<?php echo $id ?>" class="main-content main-forum">
		<div class="main-item empty main-first-item">
			<span class="icon empty"><!-- --></span>
			<div class="item-subject">
				<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['subject'])."\n" ?>
			</div>
		</div>
	</div>
	<div class="main-foot">
		<h2 class="hn"><span><?php echo $lang_forum['Empty forum'] ?></span></h2>
	</div>
<?php

}
?>

<?php $this->push('forum_main_pagepost_top') ?>
<?= $this->insert('partials/pager_top', ['forum_page' => $forum_page]) ?>
<?php $this->end() ?>

<?php $this->push('forum_main_pagepost_end') ?>
<?= $this->insert('partials/pager_end', ['forum_page' => $forum_page]) ?>
<?php $this->end() ?>