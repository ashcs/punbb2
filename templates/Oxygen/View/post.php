<?php $this->layout('main') ?>

	<div class="main-head">
		<h2 class="hn"><span><?= $tid ? $lang_post['Post reply'] : $lang_post['Post new topic'] ?></span></h2>
	</div>
<?php

$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_attributes'] = array();

// If preview selected and there are no errors
if (isset($_POST['preview']) && empty($errors))
{
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$forum_page['preview_message'] = parse_message(\Punbb\ForumFunction::forum_trim($message), $hide_smilies);

	// Generate the post heading
	$forum_page['post_ident'] = array();
	$forum_page['post_ident']['num'] = '<span class="post-num">#</span>';
	$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($tid) ? $lang_post['Reply byline'] : $lang_post['Topic byline']), '<strong>'.\Punbb\ForumFunction::forum_htmlencode($forum_user['username']).'</strong>').'</span>';
	$forum_page['post_ident']['link'] = '<span class="post-link">'.\Punbb\ForumFunction::format_time(time()).'</span>';

	($hook = \Punbb\ForumFunction::get_hook('po_preview_pre_display')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?= $tid ? $lang_post['Preview reply'] : $lang_post['Preview new topic'] ?></span></h2>
	</div>
	<div id="post-preview" class="main-content main-frm">
		<div class="post singlepost">
			<div class="posthead">
				<h3 class="hn"><?= implode(' ', $forum_page['post_ident']) ?></h3>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_preview_new_post_head_option')) ? eval($hook) : null; ?>
			</div>
			<div class="postbody">
				<div class="post-entry">
					<div class="entry-content">
						<?= $forum_page['preview_message']."\n" ?>
					</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_preview_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
		</div>
	</div>
<?php

}

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?= ($tid) ? $lang_post['Compose your reply'] : $lang_post['Compose your topic'] ?></span></h2>
	</div>
	<div id="post-form" class="main-content main-frm">
<?php

	if (!empty($forum_page['text_options']))
		echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

	// If there were any errors, show them
	if (!empty($errors))
	{
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = \Punbb\ForumFunction::get_hook('po_pre_post_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?= $lang_post['Post errors'] ?></h2>
			<ul class="error-list">
				<?= implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?= $lang_common['Required warn'] ?></p>
		</div>
		<form id="afocus" class="frm-form frm-ctrl-submit" method="post" accept-charset="utf-8" action="<?= $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
			<div class="hidden">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="form_user" value="<?= \Punbb\ForumFunction::forum_htmlencode($forum_user['username']) ?>" />
				<input type="hidden" name="csrf_token" value="<?= \Punbb\ForumFunction::generate_form_token($forum_page['form_action']) ?>" />
			</div>
<?php

if ($forum_user['is_guest'])
{
	$forum_page['email_form_name'] = ($forum_config['p_force_guest_email'] == '1') ? 'req_email' : 'email';

	($hook = \Punbb\ForumFunction::get_hook('po_pre_guest_info_fieldset')) ? eval($hook) : null;

?>
			<fieldset class="frm-group group<?= ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?= $lang_post['Guest post legend'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_pre_guest_username')) ? eval($hook) : null; ?>
				<div class="sf-set set<?= ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?= ++$forum_page['fld_count'] ?>"><span><?= $lang_post['Guest name'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?= $forum_page['fld_count'] ?>" name="req_username" value="<?php if (isset($_POST['req_username'])) echo \Punbb\ForumFunction::forum_htmlencode($username); ?>" size="35" maxlength="25" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_pre_guest_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?= ++$forum_page['item_count'] ?>">
					<div class="sf-box text<?php if ($forum_config['p_force_guest_email'] == '1') echo ' required' ?>">
						<label for="fld<?= ++$forum_page['fld_count'] ?>"><span><?= $lang_post['Guest e-mail'] ?></span></label><br />
						<span class="fld-input"><input type="email" id="fld<?= $forum_page['fld_count'] ?>" name="<?= $forum_page['email_form_name'] ?>" value="<?php if (isset($_POST[$forum_page['email_form_name']])) echo \Punbb\ForumFunction::forum_htmlencode($email); ?>" size="35" maxlength="80" <?php if ($forum_config['p_force_guest_email'] == '1') echo 'required' ?> /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_pre_guest_info_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = \Punbb\ForumFunction::get_hook('po_guest_info_fieldset_end')) ? eval($hook) : null;

	// Reset counters
	$forum_page['group_count'] = $forum_page['item_count'] = 0;
}

($hook = \Punbb\ForumFunction::get_hook('po_pre_req_info_fieldset')) ? eval($hook) : null;

?>
			<fieldset class="frm-group group<?= ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?= $lang_common['Required information'] ?></strong></legend>
<?php

if ($fid)
{
	($hook = \Punbb\ForumFunction::get_hook('po_pre_req_subject')) ? eval($hook) : null;

?>
				<div class="sf-set set<?= ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?= ++$forum_page['fld_count'] ?>"><span><?= $lang_post['Topic subject'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?= $forum_page['fld_count'] ?>" type="text" name="req_subject" value="<?php if (isset($_POST['req_subject'])) echo \Punbb\ForumFunction::forum_htmlencode($subject); ?>" size="<?= FORUM_SUBJECT_MAXIMUM_LENGTH ?>" maxlength="<?= FORUM_SUBJECT_MAXIMUM_LENGTH ?>" required /></span>
					</div>
				</div>
<?php

}

($hook = \Punbb\ForumFunction::get_hook('po_pre_post_contents')) ? eval($hook) : null;

?>
				<div class="txt-set set<?= ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?= ++$forum_page['fld_count'] ?>"><span><?= $lang_post['Write message'] ?></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?= $forum_page['fld_count'] ?>" name="req_message" rows="15" cols="95" required spellcheck="true"><?= isset($_POST['req_message']) ? \Punbb\ForumFunction::forum_htmlencode($message) : (isset($forum_page['quote']) ? \Punbb\ForumFunction::forum_htmlencode($forum_page['quote']) : '') ?></textarea></span></div>
					</div>
				</div>
<?php

$forum_page['checkboxes'] = array();
if ($forum_config['o_smilies'] == '1')
	$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="hide_smilies" value="1"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Hide smilies'].'</label></div>';

// Check/uncheck the checkbox for subscriptions depending on scenario
if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1')
{
	$subscr_checked = false;

	// If it's a preview
	if (isset($_POST['preview']))
		$subscr_checked = isset($_POST['subscribe']) ? true : false;
	// If auto subscribed
	else if ($forum_user['auto_notify'])
		$subscr_checked = true;
	// If already subscribed to the topic
	else if ($is_subscribed)
		$subscr_checked = true;

	$forum_page['checkboxes']['subscribe'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="subscribe" value="1"'.($subscr_checked ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.($is_subscribed ? $lang_post['Stay subscribed'] : $lang_post['Subscribe']).'</label></div>';
}

($hook = \Punbb\ForumFunction::get_hook('po_pre_optional_fieldset')) ? eval($hook) : null;

if (!empty($forum_page['checkboxes']))
{

?>
				<fieldset class="mf-set set<?= ++$forum_page['item_count'] ?>">
					<div class="mf-box checkbox">
						<?= implode("\n\t\t\t\t\t", $forum_page['checkboxes'])."\n" ?>
					</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_pre_checkbox_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

}

($hook = \Punbb\ForumFunction::get_hook('po_pre_req_info_fieldset_end')) ? eval($hook) : null;

?>
			</fieldset>
<?php

($hook = \Punbb\ForumFunction::get_hook('po_req_info_fieldset_end')) ? eval($hook) : null;

?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="submit_button" value="<?= ($tid) ? $lang_post['Submit reply'] : $lang_post['Submit topic'] ?>" /></span>
				<span class="submit"><input type="submit" name="preview" value="<?= ($tid) ? $lang_post['Preview reply'] : $lang_post['Preview topic'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

($hook = \Punbb\ForumFunction::get_hook('po_main_output_end')) ? eval($hook) : null;

// Check if the topic review is to be displayed
if ($tid && $forum_config['o_topic_review'] != '0')
{
    if (!defined('FORUM_PARSER_LOADED'))
        require FORUM_ROOT.'include/parser.php';

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?= $lang_post['Topic review'] ?></span></h2>
	</div>
	<div id="topic-review" class="main-content main-frm">
<?php

	$forum_page['item_count'] = 0;
	$forum_page['item_total'] = count($posts);

	foreach ($posts as $cur_post)
	{
		++$forum_page['item_count'];

		$forum_page['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

		// Generate the post heading
		$forum_page['post_ident'] = array();
		$forum_page['post_ident']['num'] = '<span class="post-num">'.\Punbb\ForumFunction::forum_number_format($forum_page['total_post_count'] - $forum_page['item_count'] + 1).'</span>';
		$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf($lang_post['Post byline'], '<strong>'.\Punbb\ForumFunction::forum_htmlencode($cur_post['poster']).'</strong>').'</span>';
		$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.$lang_post['Permalink post'].'" href="'.\Punbb\ForumFunction::forum_link($forum_url['post'], $cur_post['id']).'">'.\Punbb\ForumFunction::format_time($cur_post['posted']).'</a></span>';

		($hook = \Punbb\ForumFunction::get_hook('po_topic_review_row_pre_display')) ? eval($hook) : null;

?>
		<div class="post<?php if ($forum_page['item_count'] == 1) echo ' firstpost'; ?><?php if ($forum_page['item_total'] == $forum_page['item_count']) echo ' lastpost'; ?>">
			<div class="posthead">
				<h3 class="hn post-ident"><?= implode(' ', $forum_page['post_ident']) ?></h3>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_topic_review_new_post_head_option')) ? eval($hook) : null; ?>
			</div>
			<div class="postbody">
				<div class="post-entry">
					<div class="entry-content">
						<?= $forum_page['message']."\n" ?>
<?php ($hook = \Punbb\ForumFunction::get_hook('po_topic_review_new_post_entry_data')) ? eval($hook) : null; ?>
					</div>
				</div>
			</div>
		</div>
<?php

	}

?>
	</div>
	
	<?php

}
