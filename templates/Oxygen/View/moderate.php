<?php $this->layout('main') ?>

<?php ($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_output_start')) ? eval($hook) : null; ?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<form id="mr-post-actions-form" class="newform" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
	<div class="main-content main-topic">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo \Punbb\ForumFunction::generate_form_token($forum_page['form_action']) ?>" />
		</div>

<?php

	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$forum_page['item_count'] = 0;	// Keep track of post numbers
	
	foreach ($posts as $cur_post)
	{
		($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_loop_start')) ? eval($hook) : null;

		++$forum_page['item_count'];

		$forum_page['post_ident'] = array();
		$forum_page['message'] = array();
		$forum_page['user_ident'] = array();
		$cur_post['username'] = $cur_post['poster'];

		// Generate the post heading
		$forum_page['post_ident']['num'] = '<span class="post-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span>';

		if ($cur_post['poster_id'] > 1)
			$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), (($forum_user['g_view_users'] == '1') ? '<a title="'.sprintf($lang_topic['Go to profile'], \Punbb\ForumFunction::forum_htmlencode($cur_post['username'])).'" href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $cur_post['poster_id']).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong>')).'</span>';
		else
			$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong>').'</span>';

		$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_post['id']).'">'.\Punbb\ForumFunction::format_time($cur_post['posted']).'</a></span>';

		if ($cur_post['edited'] != '')
			$forum_page['post_ident']['edited'] = '<span class="post-edit">'.sprintf($lang_topic['Last edited'], \Punbb\ForumFunction::forum_htmlencode($cur_post['edited_by']), \Punbb\ForumFunction::format_time($cur_post['edited'])).'</span>';

		($hook = \Punbb\ForumFunction::get_hook('mr_row_pre_item_ident_merge')) ? eval($hook) : null;

		// Generate the checkbox field
		if ($cur_post['id'] != $cur_topic['first_post_id'])
			$forum_page['item_select'] = '<p class="item-select"><input type="checkbox" id="fld'.$cur_post['id'].'" name="posts[]" value="'.$cur_post['id'].'" /> <label for="fld'.$cur_post['id'].'">'.$lang_misc['Select post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</label></p>';

		// Generate author identification
		$forum_page['author_ident']['username'] = '<li class="username">'.(($cur_post['poster_id'] > '1') ? '<a title="'.sprintf($lang_topic['Go to profile'], \Punbb\ForumFunction::forum_htmlencode($cur_post['username'])).'" href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $cur_post['poster_id']).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong>').'</li>';
		$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.\Punbb\ForumFunction::get_title($cur_post).'</span></li>';

		// Give the post some class
		$forum_page['item_status'] = array(
			'post',
			($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even'
		);

		if ($forum_page['item_count'] == 1)
			$forum_page['item_status']['firstpost'] = 'firstpost';

		if (($forum_page['start_from'] + $forum_page['item_count']) == $forum_page['finish_at'])
			$forum_page['item_status']['lastpost'] = 'lastpost';

		if ($cur_post['id'] == $cur_topic['first_post_id'])
			$forum_page['item_status']['topicpost'] = 'topicpost';
		else
			$forum_page['item_status']['replypost'] = 'replypost';

		// Generate the post title
		if ($cur_post['id'] == $cur_topic['first_post_id'])
			$forum_page['item_subject'] = sprintf($lang_topic['Topic title'], $cur_topic['subject']);
		else
			$forum_page['item_subject'] = sprintf($lang_topic['Reply title'], $cur_topic['subject']);

		$forum_page['item_subject'] = \Punbb\ForumFunction::forum_htmlencode($forum_page['item_subject']);

		// Perform the main parsing of the message (BBCode, smilies, censor words etc)
		$forum_page['message']['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

		($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_row_pre_display')) ? eval($hook) : null;

?>
			<div class="<?php echo implode(' ', $forum_page['item_status']) ?>">
				<div id="p<?php echo $cur_post['id'] ?>" class="posthead">
					<h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
<?php ($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_pre_item_select')) ? eval($hook) : null; ?>
<?php if (isset($forum_page['item_select'])) echo "\t\t\t\t".$forum_page['item_select']."\n" ?>
<?php ($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_new_post_head_option')) ? eval($hook) : null; ?>
				</div>
				<div class="postbody">
					<div class="post-author">
						<ul class="author-ident">
							<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_ident'])."\n" ?>
						</ul>
<?php ($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_new_user_ident_data')) ? eval($hook) : null; ?>
					</div>
					<div class="post-entry">
						<h4 class="entry-title"><?php echo $forum_page['item_subject'] ?></h4>
						<div class="entry-content">
							<?php echo implode("\n\t\t\t\t\t\t\t", $forum_page['message'])."\n" ?>
						</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_new_post_entry_data')) ? eval($hook) : null; ?>
					</div>
				</div>
			</div>
<?php

	}

?>
	</div>
<?php

$forum_page['mod_options'] = array(
	'del_posts'		=> '<span class="submit first-item"><input type="submit" name="delete_posts" value="'.$lang_misc['Delete posts'].'" /></span>',
	'split_posts'	=> '<span class="submit"><input type="submit" name="split_posts" value="'.$lang_misc['Split posts'].'" /></span>',
	'del_topic'		=> '<span><a href="'.\Punbb\ForumFunction::forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_misc['Delete whole topic'].'</a></span>'
);

($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_pre_mod_options')) ? eval($hook) : null;

?>

	<div class="main-options mod-options gen-content">
		<p class="options"><?php echo implode(' ', $forum_page['mod_options']) ?></p>
	</div>
	</form>
	<div class="main-foot">
<?php

	if (!empty($forum_page['main_foot_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_foot_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('mr_post_actions_end')) ? eval($hook) : null; ?>


