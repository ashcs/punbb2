<?php $this->layout('profile', ['forum_page' => $forum_page]) ?>

<?php $this->start('profile') ?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_page['own_profile']) ? $lang_profile['Sig welcome'] : $lang_profile['Sig welcome user'], \Punbb\ForumFunction::forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

		if (!empty($forum_page['text_options']))
			echo "\t\t".'<p class="content-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

		// If there were any errors, show them
		if (!empty($errors))
		{
			$forum_page['errors'] = array();
			foreach ($errors as $cur_error)
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = \Punbb\ForumFunction::get_hook('pf_change_details_signature_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

		}

?>
		<form id="afocus" class="frm-form frm-ctrl-submit" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_signature_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Signature'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_signature_pre_signature_demo')) ? eval($hook) : null; ?>
<?php if (isset($forum_page['sig_demo'])): ?>
				<div class="ct-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="ct-box">
						<h3 class="ct-legend hn"><?php echo $lang_profile['Current signature'] ?></h3>
						<div class="sig-demo"><?php echo $forum_page['sig_demo'] ?></div>
					</div>
				</div>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_signature_pre_signature_text')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Compose signature'] ?></span> <small><?php printf($lang_profile['Sig max size'], \Punbb\ForumFunction::forum_number_format($forum_config['p_sig_length']), \Punbb\ForumFunction::forum_number_format($forum_config['p_sig_lines'])) ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="signature" rows="4" cols="65"><?php echo(isset($_POST['signature']) ? \Punbb\ForumFunction::forum_htmlencode($_POST['signature']) : \Punbb\ForumFunction::forum_htmlencode($user['signature'])) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_signature_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_signature_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo $lang_profile['Update profile'] ?>" /></span>
			</div>
		</form>
	</div>

<?php $this->stop() ?>	
