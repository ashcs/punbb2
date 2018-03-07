		
		<?php if ($forum_config['o_announcement'] == '1' && $forum_user['g_read_board'] == '1') :?>
			<div id="brd-announcement" class="gen-content">
				<?php if ($forum_config['o_announcement_heading'] != '') : ?> 
				<h1 class="hn"><span><?= $forum_config['o_announcement_heading'] ?></span></h1>
				<?php endif; ?>
				<div class="content"><?= $forum_config['o_announcement_message'] ?></div>
			</div>
		<?php endif;?>
		<!-- forum_announcement -->
		
		