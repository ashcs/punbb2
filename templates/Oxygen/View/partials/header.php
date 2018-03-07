<div id="brd-head" class="gen-content">

<p id="brd-access"><a href="#brd-main"><?= $lang_common['Skip to content'] ?></a></p>
<!-- forum_skip -->


<p id="brd-title"><a href="<?= \Punbb\ForumFunction::forum_link($forum_url['index']) ?>"><?= \Punbb\ForumFunction::forum_htmlencode($forum_config['o_board_title']) ?></a></p>
<!-- forum_title -->

	<?php if ($forum_config['o_board_desc'] != '') :?>
	<p id="brd-desc"><?= \Punbb\ForumFunction::forum_htmlencode($forum_config['o_board_desc']) ?></p>
	<?php endif;?>
<!-- forum_desc -->

</div>
