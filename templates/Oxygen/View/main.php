<!DOCTYPE html>
<html lang="<?= $lang_common['lang_identifier'] ?>" dir="<?= $lang_common['lang_direction'] ?>">
<head>

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" media="screen" href="/style/Oxygen/Oxygen.min.css" />
<!-- forum_head -->

</head>
<body>

<div id="brd-messages" class="brd"><?= $container['forum_flash']->show(true) ?></div>
<!-- forum_messages -->

<div id="brd-wrap" class="brd">
<div id="brd-<?= FORUM_PAGE ?>" class="brd-page <?= FORUM_PAGE_TYPE ?>">

<?= $this->insert('partials/header') ?>

<?= $this->insert('partials/navlinks') ?>

<?= $this->insert('partials/visit') ?>

<?= $this->insert('partials/announcement') ?>

		<div class="hr"><hr /></div>
		<div id="brd-main">
				
				<?php if (isset($forum_page['main_title'])) : ?>
				<h1 class="main-title"><?= $forum_page['main_title'] ?></h1>
				<!-- forum_main_title -->
				<?php endif ?>
		
				
				<?php if (FORUM_PAGE != 'index') : ?>				
				<div id="brd-crumbs-top" class="crumbs">
					<?= $container['breadcrumbs']->render() ?>
				</div>
				<?php endif; ?>
				<!-- forum_crumbs_top -->

				<?= $this->section('main_menu') ?>
				<!-- forum_main_menu -->

				<?=$this->section('forum_main_pagepost_top')?>
				<!-- forum_main_pagepost_top -->

				<?= $this->section('content') ?>
				<!-- forum_main -->
				
				<?=$this->section('forum_main_pagepost_end')?>
				<!-- forum_main_pagepost_end -->
				
				<?php if (FORUM_PAGE != 'index') : ?>
				<div id="brd-crumbs-end" class="crumbs">
					<?= $container['breadcrumbs']->render() ?>
				</div>
				<?php endif; ?>
				<!-- forum_crumbs_end -->

				</div>
				
				<!-- forum_qpost -->
				
				<div class="hr"><hr /></div>
				<div id="brd-about">
				<p id="copyright"><?= sprintf($lang_common['Powered by'], '<a href="http://punbb.informer.com/">PunBB</a>'.($forum_config['o_show_version'] == '1' ? ' '.$forum_config['o_cur_version'] : ''), '<a href="http://www.informer.com/">Informer Technologies, Inc</a>') ?></p>

				</div>
				<?= $this->insert('partials/debug') ?>
				<!-- forum_debug -->
				</div>
				</div>
				<!-- forum_javascript -->
				<script>
				var main_menu = responsiveNav("#brd-navlinks", {
				label: "<!-- forum_board_title -->"
});
if(document.getElementsByClassName('admin-menu').length){
	var admin_menu = responsiveNav(".admin-menu", {
		label: "<!-- forum_lang_menu_admin -->"
		});
}
if(document.getElementsByClassName('main-menu').length){
		var profile_menu = responsiveNav(".main-menu", {
		    label: "<!-- forum_lang_menu_profile -->"
		});
	    }
		    		</script>
		    		</body>
</html>
