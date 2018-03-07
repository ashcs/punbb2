<?php $this->layout('main') ?>

<?php


// Navigation links for header and page numbering for title/meta description
if ($forum_page['page'] < $forum_page['num_pages'])
{
	$forum_page['nav']['last'] = '<link rel="last" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['topic'], $forum_url['page'], $forum_page['num_pages'], array($id, \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
	$forum_page['nav']['next'] = '<link rel="next" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['topic'], $forum_url['page'], ($forum_page['page'] + 1), array($id, \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
}
if ($forum_page['page'] > 1)
{
	$forum_page['nav']['prev'] = '<link rel="prev" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['topic'], $forum_url['page'], ($forum_page['page'] - 1), array($id, \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
	$forum_page['nav']['first'] = '<link rel="first" href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($id, \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' 1" />';
}

if ($forum_config['o_censoring'] == '1')
	$cur_topic['subject'] = \Punbb\ForumFunction::censor_words($cur_topic['subject']);

// Generate paging and posting links
$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.\Punbb\ForumFunction::paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['topic'], $lang_common['Paging separator'], array($id, \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'</p>';

if ($forum_user['may_post'])
	$forum_page['page_post']['posting'] = '<p class="posting"><a class="newpost" href="'.\Punbb\ForumFunction::forum_link($forum_url['new_reply'], $id).'"><span>'.$lang_topic['Post reply'].'</span></a></p>';
else if ($forum_user['is_guest'])
	$forum_page['page_post']['posting'] = '<p class="posting">'.sprintf($lang_topic['Login to post'], '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['login']).'">'.$lang_common['login'].'</a>', '<a href="'.\Punbb\ForumFunction::forum_link($forum_url['register']).'">'.$lang_common['register'].'</a>').'</p>';
else if ($cur_topic['closed'] == '1')
	$forum_page['page_post']['posting'] = '<p class="posting">'.$lang_topic['Topic closed info'].'</p>';
else
	$forum_page['page_post']['posting'] = '<p class="posting">'.$lang_topic['No permission'].'</p>';

// Setup main options
$forum_page['main_title'] = $lang_topic['Topic options'];
$forum_page['main_head_options'] = array(
	'rss' => '<span class="feed first-item"><a class="feed" href="'.\Punbb\ForumFunction::forum_link($forum_url['topic_rss'], $id).'">'.$lang_topic['RSS topic feed'].'</a></span>'
);

if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1')
{
	if ($cur_topic['is_subscribed'])
		$forum_page['main_head_options']['unsubscribe'] = '<span><a class="sub-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['unsubscribe'], array($id, \Punbb\ForumFunction::generate_form_token('unsubscribe'.$id.$forum_user['id']))).'"><em>'.$lang_topic['Unsubscribe'].'</em></a></span>';
	else
		$forum_page['main_head_options']['subscribe'] = '<span><a class="sub-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['subscribe'], array($id, \Punbb\ForumFunction::generate_form_token('subscribe'.$id.$forum_user['id']))).'" title="'.$lang_topic['Subscribe info'].'">'.$lang_topic['Subscribe'].'</a></span>';
}

if ($forum_page['is_admmod'])
{
	$forum_page['main_foot_options'] = array(
		'move' => '<span class="first-item"><a class="mod-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['move'], array($cur_topic['forum_id'], $id)).'">'.$lang_topic['Move'].'</a></span>',
		'delete' => '<span><a class="mod-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_topic['Delete topic'].'</a></span>',
		'close' => (($cur_topic['closed'] == '1') ? '<span><a class="mod-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['open'], array($cur_topic['forum_id'], $id, \Punbb\ForumFunction::generate_form_token('open'.$id))).'">'.$lang_topic['Open'].'</a></span>' : '<span><a class="mod-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['close'], array($cur_topic['forum_id'], $id, \Punbb\ForumFunction::generate_form_token('close'.$id))).'">'.$lang_topic['Close'].'</a></span>'),
		'sticky' => (($cur_topic['sticky'] == '1') ? '<span><a class="mod-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['unstick'], array($cur_topic['forum_id'], $id, \Punbb\ForumFunction::generate_form_token('unstick'.$id))).'">'.$lang_topic['Unstick'].'</a></span>' : '<span><a class="mod-option" href="'.\Punbb\ForumFunction::forum_link($forum_url['stick'], array($cur_topic['forum_id'], $id, \Punbb\ForumFunction::generate_form_token('stick'.$id))).'">'.$lang_topic['Stick'].'</a></span>')
	);

	if ($cur_topic['num_replies'] != 0)
		$forum_page['main_foot_options']['moderate_topic'] = '<span><a class="mod-option" href="'.\Punbb\ForumFunction::forum_sublink($forum_url['moderate_topic'], $forum_url['page'], $forum_page['page'], array($cur_topic['forum_id'], $id)).'">'.$lang_topic['Moderate topic'].'</a></span>';
}



// Setup main heading
$forum_page['main_title'] = (($cur_topic['closed'] == '1') ? $lang_topic['Topic closed'].' ' : '').'<a class="permalink" href="'.\Punbb\ForumFunction::forum_link($forum_url['topic'], array($id, \Punbb\ForumFunction::sef_friendly($cur_topic['subject']))).'" rel="bookmark" title="'.$lang_topic['Permalink topic'].'">'.\Punbb\ForumFunction::forum_htmlencode($cur_topic['subject']).'</a>';

if ($forum_page['num_pages'] > 1)
	$forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);

($hook = \Punbb\ForumFunction::get_hook('vt_pre_header_load')) ? eval($hook) : null;

// Allow indexing if this is a permalink
if (!$pid)
	define('FORUM_ALLOW_INDEX', 1);

($hook = \Punbb\ForumFunction::get_hook('vt_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>'."\n";

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div id="forum<?php echo $cur_topic['forum_id'] ?>" class="main-content main-topic">
<?php

if (!defined('FORUM_PARSER_LOADED'))
	require FORUM_ROOT.'include/parser.php';

$forum_page['item_count'] = 0;	// Keep track of post numbers

if (!empty($posts))
{

	$user_data_cache = array();
	foreach ($posts as $cur_post)
	{
		($hook = \Punbb\ForumFunction::get_hook('vt_post_loop_start')) ? eval($hook) : null;

		++$forum_page['item_count'];

		$forum_page['post_ident'] = array();
		$forum_page['author_ident'] = array();
		$forum_page['author_info'] = array();
		$forum_page['post_options'] = array();
		$forum_page['post_contacts'] = array();
		$forum_page['post_actions'] = array();
		$forum_page['message'] = array();

		// Generate the post heading
		$forum_page['post_ident']['num'] = '<span class="post-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span>';

		if ($cur_post['poster_id'] > 1)
			$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), (($forum_user['g_view_users'] == '1') ? '<a title="'.sprintf($lang_topic['Go to profile'], \Punbb\ForumFunction::forum_htmlencode($cur_post['username'])).'" href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $cur_post['poster_id']).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong>')).'</span>';
		else
			$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong>').'</span>';

		$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_post['id']).'">'.\Punbb\ForumFunction::format_time($cur_post['posted']).'</a></span>';

		if ($cur_post['edited'] != '')
			$forum_page['post_ident']['edited'] = '<span class="post-edit">'.sprintf($lang_topic['Last edited'], \Punbb\ForumFunction::forum_htmlencode($cur_post['edited_by']), \Punbb\ForumFunction::format_time($cur_post['edited'])).'</span>';


		($hook = \Punbb\ForumFunction::get_hook('vt_row_pre_post_ident_merge')) ? eval($hook) : null;

		if (isset($user_data_cache[$cur_post['poster_id']]['author_ident']))
			$forum_page['author_ident'] = $user_data_cache[$cur_post['poster_id']]['author_ident'];
		else
		{
			// Generate author identification
			if ($cur_post['poster_id'] > 1)
			{
				if ($forum_config['o_avatars'] == '1' && $forum_user['show_avatars'] != '0')
				{
					$forum_page['avatar_markup'] = \Punbb\ForumFunction::generate_avatar_markup($cur_post['poster_id'], $cur_post['avatar'], $cur_post['avatar_width'], $cur_post['avatar_height'], $cur_post['username']);

					if (!empty($forum_page['avatar_markup']))
						$forum_page['author_ident']['avatar'] = '<li class="useravatar">'.$forum_page['avatar_markup'].'</li>';
				}

				$forum_page['author_ident']['username'] = '<li class="username">'.(($forum_user['g_view_users'] == '1') ? '<a title="'.sprintf($lang_topic['Go to profile'], \Punbb\ForumFunction::forum_htmlencode($cur_post['username'])).'" href="'.\Punbb\ForumFunction::forum_link($forum_url['user'], $cur_post['poster_id']).'">'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong>').'</li>';
				$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.\Punbb\ForumFunction::get_title($cur_post).'</span></li>';

				if ($cur_post['is_online'] == $cur_post['poster_id'])
					$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.$lang_topic['Online'].'</span></li>';
				else
					$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.$lang_topic['Offline'].'</span></li>';
			}
			else
			{
				$forum_page['author_ident']['username'] = '<li class="username"><strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</strong></li>';
				$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.\Punbb\ForumFunction::get_title($cur_post).'</span></li>';
			}
		}

		if (isset($user_data_cache[$cur_post['poster_id']]['author_info']))
			$forum_page['author_info'] = $user_data_cache[$cur_post['poster_id']]['author_info'];
		else
		{
			// Generate author information
			if ($cur_post['poster_id'] > 1)
			{
				if ($forum_config['o_show_user_info'] == '1')
				{
					if ($cur_post['location'] != '')
					{
						if ($forum_config['o_censoring'] == '1')
							$cur_post['location'] = \Punbb\ForumFunction::censor_words($cur_post['location']);

						$forum_page['author_info']['from'] = '<li><span>'.$lang_topic['From'].' <strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['location']).'</strong></span></li>';
					}

					$forum_page['author_info']['registered'] = '<li><span>'.$lang_topic['Registered'].' <strong>'.\Punbb\ForumFunction::format_time($cur_post['registered'], 1).'</strong></span></li>';

					if ($forum_config['o_show_post_count'] == '1' || $forum_user['is_admmod'])
						$forum_page['author_info']['posts'] = '<li><span>'.$lang_topic['Posts info'].' <strong>'.\Punbb\ForumFunction::forum_number_format($cur_post['num_posts']).'</strong></span></li>';
				}

				if ($forum_user['is_admmod'])
				{
					if ($cur_post['admin_note'] != '')
						$forum_page['author_info']['note'] = '<li><span>'.$lang_topic['Note'].' <strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['admin_note']).'</strong></span></li>';
				}
			}
		}

		// Generate IP information for moderators/administrators
		if ($forum_user['is_admmod'])
			$forum_page['author_info']['ip'] = '<li><span>'.$lang_topic['IP'].' <a href="'.\Punbb\ForumFunction::forum_link($forum_url['get_host'], $cur_post['id']).'">'.$cur_post['poster_ip'].'</a></span></li>';

		// Generate author contact details
		if ($forum_config['o_show_user_info'] == '1')
		{
			if (isset($user_data_cache[$cur_post['poster_id']]['post_contacts']))
				$forum_page['post_contacts'] = $user_data_cache[$cur_post['poster_id']]['post_contacts'];
			else
			{
				if ($cur_post['poster_id'] > 1)
				{
					if ($cur_post['url'] != '')
						$forum_page['post_contacts']['url'] = '<span class="user-url'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a class="external" href="'.\Punbb\ForumFunction::forum_htmlencode(($forum_config['o_censoring'] == '1') ? \Punbb\ForumFunction::censor_words($cur_post['url']) : $cur_post['url']).'">'.sprintf($lang_topic['Visit website'], '<span>'.sprintf($lang_topic['User possessive'], \Punbb\ForumFunction::forum_htmlencode($cur_post['username'])).'</span>').'</a></span>';
					if ((($cur_post['email_setting'] == '0' && !$forum_user['is_guest']) || $forum_user['is_admmod']) && $forum_user['g_send_email'] == '1')
						$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.\Punbb\ForumFunction::forum_htmlencode($cur_post['email']).'">'.$lang_topic['E-mail'].'<span>&#160;'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</span></a></span>';
					else if ($cur_post['email_setting'] == '1' && !$forum_user['is_guest'] && $forum_user['g_send_email'] == '1')
						$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['email'], $cur_post['poster_id']).'">'.$lang_topic['E-mail'].'<span>&#160;'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</span></a></span>';
				}
				else
				{
					if ($cur_post['poster_email'] != '' && $forum_user['is_admmod'] && $forum_user['g_send_email'] == '1')
						$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.\Punbb\ForumFunction::forum_htmlencode($cur_post['poster_email']).'">'.$lang_topic['E-mail'].'<span>&#160;'.\Punbb\ForumFunction::forum_htmlencode($cur_post['username']).'</span></a></span>';
				}
			}

			($hook = \Punbb\ForumFunction::get_hook('vt_row_pre_post_contacts_merge')) ? eval($hook) : null;

			if (!empty($forum_page['post_contacts']))
				$forum_page['post_options']['contacts'] = '<p class="post-contacts">'.implode(' ', $forum_page['post_contacts']).'</p>';
		}

		// Generate the post options links
		if (!$forum_user['is_guest'])
		{
			$forum_page['post_actions']['report'] = '<span class="report-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['report'], $cur_post['id']).'">'.$lang_topic['Report'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';

			if (!$forum_page['is_admmod'])
			{
				if ($cur_topic['closed'] == '0')
				{
					if ($cur_post['poster_id'] == $forum_user['id'])
					{
						if (($forum_page['start_from'] + $forum_page['item_count']) == 1 && $forum_user['g_delete_topics'] == '1')
							$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_topic['Delete topic'].'</a></span>';
						if (($forum_page['start_from'] + $forum_page['item_count']) > 1 && $forum_user['g_delete_posts'] == '1')
							$forum_page['post_actions']['delete'] = '<span class="delete-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['delete'], $cur_post['id']).'">'.$lang_topic['Delete'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
						if ($forum_user['g_edit_posts'] == '1')
							$forum_page['post_actions']['edit'] = '<span class="edit-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['edit'], $cur_post['id']).'">'.$lang_topic['Edit'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
					}

					if (($cur_topic['post_replies'] == '' && $forum_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
						$forum_page['post_actions']['quote'] = '<span class="quote-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['quote'], array($id, $cur_post['id'])).'">'.$lang_topic['Quote'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
				}
			}
			else
			{
				if (($forum_page['start_from'] + $forum_page['item_count']) == 1)
					$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_topic['Delete topic'].'</a></span>';
				else
					$forum_page['post_actions']['delete'] = '<span class="delete-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['delete'], $cur_post['id']).'">'.$lang_topic['Delete'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';

				$forum_page['post_actions']['edit'] = '<span class="edit-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['edit'], $cur_post['id']).'">'.$lang_topic['Edit'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
				$forum_page['post_actions']['quote'] = '<span class="quote-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['quote'], array($id, $cur_post['id'])).'">'.$lang_topic['Quote'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
			}
		}
		else
		{
			if ($cur_topic['closed'] == '0')
			{
				if (($cur_topic['post_replies'] == '' && $forum_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
					$forum_page['post_actions']['quote'] = '<span class="report-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.\Punbb\ForumFunction::forum_link($forum_url['quote'], array($id, $cur_post['id'])).'">'.$lang_topic['Quote'].'<span> '.$lang_topic['Post'].' '.\Punbb\ForumFunction::forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
			}
		}

		($hook = \Punbb\ForumFunction::get_hook('vt_row_pre_post_actions_merge')) ? eval($hook) : null;

		if (!empty($forum_page['post_actions']))
			$forum_page['post_options']['actions'] = '<p class="post-actions">'.implode(' ', $forum_page['post_actions']).'</p>';

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

		// Do signature parsing/caching
		if ($cur_post['signature'] != '' && $forum_user['show_sig'] != '0' && $forum_config['o_signatures'] == '1')
		{
			if (!isset($signature_cache[$cur_post['poster_id']]))
				$signature_cache[$cur_post['poster_id']] = parse_signature($cur_post['signature']);

			$forum_page['message']['signature'] = '<div class="sig-content"><span class="sig-line"><!-- --></span>'.$signature_cache[$cur_post['poster_id']].'</div>';
		}

		($hook = \Punbb\ForumFunction::get_hook('vt_row_pre_display')) ? eval($hook) : null;

		// Do user data caching for the post
		if ($cur_post['poster_id'] > 1 && !isset($user_data_cache[$cur_post['poster_id']]))
		{
			$user_data_cache[$cur_post['poster_id']] = array(
				'author_ident'	=> $forum_page['author_ident'],
				'author_info'	=> $forum_page['author_info'],
				'post_contacts'	=> $forum_page['post_contacts']
			);

			($hook = \Punbb\ForumFunction::get_hook('vt_row_\Punbb\ForumFunction::add_user_data_cache')) ? eval($hook) : null;
		}

?>
		<div class="<?php echo implode(' ', $forum_page['item_status']) ?>">
			<div id="p<?php echo $cur_post['id'] ?>" class="posthead">
				<h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
			</div>
			<div class="postbody<?php if ($cur_post['is_online'] == $cur_post['poster_id']) echo ' online'; ?>">
				<div class="post-author">
					<ul class="author-ident">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_ident'])."\n" ?>
					</ul>
					<ul class="author-info">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_info'])."\n" ?>
					</ul>
				</div>
				<div class="post-entry">
					<h4 id="pc<?php echo $cur_post['id'] ?>" class="entry-title hn"><?php echo $forum_page['item_subject'] ?></h4>
					<div class="entry-content">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['message'])."\n" ?>
					</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('vt_row_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
<?php if (!empty($forum_page['post_options'])): ?>
			<div class="postfoot">
				<div class="post-options">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['post_options'])."\n" ?>
				</div>
			</div>
<?php endif; ?>
		</div>
<?php

	}
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

($hook = \Punbb\ForumFunction::get_hook('vt_end')) ? eval($hook) : null;




// Display quick post if enabled
if ($forum_config['o_quickpost'] == '1' &&
	!$forum_user['is_guest'] &&
	($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $forum_user['g_post_replies'] == '1')) &&
	($cur_topic['closed'] == '0' || $forum_page['is_admmod']))
{


($hook = \Punbb\ForumFunction::get_hook('vt_qpost_output_start')) ? eval($hook) : null;

// Setup form
$forum_page['form_action'] = \Punbb\ForumFunction::forum_link($forum_url['new_reply'], $id);
$forum_page['form_attributes'] = array();

$forum_page['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'form_user'		=> '<input type="hidden" name="form_user" value="'.((!$forum_user['is_guest']) ? \Punbb\ForumFunction::forum_htmlencode($forum_user['username']) : 'Guest').'" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.\Punbb\ForumFunction::generate_form_token($forum_page['form_action']).'" />'
);

if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1' && ($forum_user['auto_notify'] == '1' || $cur_topic['is_subscribed']))
	$forum_page['hidden_fields']['subscribe'] = '<input type="hidden" name="subscribe" value="1" />';

// Setup help
$forum_page['main_head_options'] = array();
if ($forum_config['p_message_bbcode'] == '1')
	$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.\Punbb\ForumFunction::forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
if ($forum_config['p_message_img_tag'] == '1')
	$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.\Punbb\ForumFunction::forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
if ($forum_config['o_smilies'] == '1')
	$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.\Punbb\ForumFunction::forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

($hook = \Punbb\ForumFunction::get_hook('vt_quickpost_pre_display')) ? eval($hook) : null;

?>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo $lang_topic['Quick post'] ?></span></h2>
</div>
<div id="brd-qpost" class="main-content main-frm">
<?php if (!empty($forum_page['text_options'])) echo "\t".'<p class="content-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n" ?>
	<div id="req-msg" class="req-warn ct-box error-box">
		<p class="important"><?php echo $lang_topic['Required warn'] ?></p>
	</div>
	<form class="frm-form frm-ctrl-submit" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
		<div class="hidden">
			<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
		</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('vt_quickpost_pre_fieldset')) ? eval($hook) : null; ?>
		<fieldset class="frm-group group1">
			<legend class="group-legend"><strong><?php echo $lang_common['Write message legend'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('vt_quickpost_pre_message_box')) ? eval($hook) : null; ?>
			<div class="txt-set set1">
				<div class="txt-box textarea required">
					<label for="fld1"><span><?php echo $lang_common['Write message'] ?></span></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld1" name="req_message" rows="7" cols="95" required spellcheck="true" ></textarea></span></div>
				</div>
			</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('vt_quickpost_pre_fieldset_end')) ? eval($hook) : null; ?>
		</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('vt_quickpost_fieldset_end')) ? eval($hook) : null; ?>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="submit_button" value="<?php echo $lang_common['Submit'] ?>" /></span>
			<span class="submit"><input type="submit" name="preview" value="<?php echo $lang_common['Preview'] ?>" /></span>
		</div>
	</form>
</div>

<?php  } ?>

<?php $this->push('forum_main_pagepost_top') ?>
<?= $this->insert('partials/pager_top', ['forum_page' => $forum_page]) ?>
<?php $this->end() ?>

<?php $this->push('forum_main_pagepost_end') ?>
<?= $this->insert('partials/pager_end', ['forum_page' => $forum_page]) ?>
<?php $this->end() ?>