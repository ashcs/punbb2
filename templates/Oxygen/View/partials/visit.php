		<div id="brd-visit" class="gen-content">
		
		<?php if ($forum_user['is_guest']) : ?>
			<p id="welcome"><span><?= $lang_common['Not logged in'] ?></span> <span><?= $lang_common['Login nag'] ?></span></p>
		<?php else : ?>
			<p id="welcome"><span><?= sprintf($lang_common['Logged in as'], '<strong>'.\Punbb\ForumFunction::forum_htmlencode($forum_user['username']).'</strong>') ?></span></p>
			<?php endif; ?>
			<!-- forum_welcome -->
		
		
		
		<p id="visit-links" class="options">
		<?php if ($forum_user['g_read_board'] == '1' && $forum_user['g_search'] == '1') : ?>
			<?php if (!$forum_user['is_guest']) :?>
			<span id="visit-new"><a href="<?= \Punbb\ForumFunction::forum_link($forum_url['search_new']) ?>" title="<?= $lang_common['New posts title'] ?>"><?= $lang_common['New posts'] ?></a></span>
			<?php endif;?>
			<span id="visit-recent"><a href="<?= \Punbb\ForumFunction::forum_link($forum_url['search_recent']) ?>" title="<?= $lang_common['Active topics title'] ?>"><?= $lang_common['Active topics'] ?></a></span>
			<span id="visit-unanswered"><a href="<?= \Punbb\ForumFunction::forum_link($forum_url['search_unanswered']) ?>" title="<?= $lang_common['Unanswered topics title'] ?>"><?= $lang_common['Unanswered topics'] ?></a></span>
		<?php endif;?>
		</p>
		<!-- forum_visit -->
		
		</div>