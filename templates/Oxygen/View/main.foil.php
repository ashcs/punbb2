<!DOCTYPE html>
<html lang="<?= $lang_common['lang_identifier'] ?>" dir="<?= $lang_common['lang_direction'] ?>">
<head>
<?php $this->section('forum_head') ?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" media="screen" href="/style/Oxygen/Oxygen.min.css" />
<!-- forum_head -->
<?php $this->stop() ?>
</head>
<body>
<?php $this->section('forum_messages') ?>
<div id="brd-messages" class="brd"><?= $this->container['forum_flash']->show(true) ?></div>
<!-- forum_messages -->
<?php $this->stop() ?>
<div id="brd-wrap" class="brd">
<div <?php $this->section('forum_page')?>id="brd-<?= FORUM_PAGE ?>" class="brd-page <?= FORUM_PAGE_TYPE ?>"<?php $this->stop() ?>>

<?= $this->insert('partials/header') ?>

<?= $this->insert('partials/navlinks') ?>

<?= $this->insert('partials/visit') ?>

<?= $this->insert('partials/announcement') ?>

		<div class="hr"><hr /></div>
		<div id="brd-main">
				<?php $this->section('forum_main_title')?>
				<h1 class="main-title"><?= $this->container['forum_page']['main_title'] ?></h1>
				<!-- forum_main_title -->
				<?php $this->stop() ?>
				
				
				<?php $this->section('forum_crumbs_top')?>
				<?php if (FORUM_PAGE != 'index') : ?>				
				<div id="brd-crumbs-top" class="crumbs">
					<?= $this->container['breadcrumbs']->render() ?>
				</div>
				<?php endif; ?>
				<!-- forum_crumbs_top -->
				<?php $this->stop() ?>
				
				<?php $this->section('forum_main_menu')?>
				<?php if (!empty($forum_page['main_menu'])) : ?>
					<div class="main-menu gen-content">
						<ul>
							<?= implode("\n\t\t", $forum_page['main_menu']) ?>
						</ul>
					</div>
				<?php endif;?>
				<!-- forum_main_menu -->
				<?php $this->stop() ?>
				
				<?php $this->section('forum_main_pagepost_top')?>
					<?php if (!empty($forum_page['page_post'])) : ?>
						<div id="brd-pagepost-top" class="main-pagepost gen-content">
						<?= implode("\n\t", $forum_page['page_post']) ?>
						</div>
					<?php endif;?>
				<!-- forum_main_pagepost_top -->
				<?php $this->stop() ?>
				
				<?php $this->section('forum_main') ?>
				<!-- forum_main -->
				<?php $this->stop() ?>
				
				<?php $this->section('forum_main_pagepost_end')?>
					<?php if (!empty($forum_page['page_post'])) : ?>
						<div id="brd-pagepost-end" class="main-pagepost gen-content">
						<?= implode("\n\t", $forum_page['page_post']) ?>
						</div>
					<?php endif;?>
				<!-- forum_main_pagepost_end -->
				<?php $this->stop() ?>
				
				<?php $this->section('forum_crumbs_end')?>
				<?php if (FORUM_PAGE != 'index') : ?>
				<div id="brd-crumbs-end" class="crumbs">
					<?= $this->container['breadcrumbs']->render() ?>
				</div>
				<?php endif; ?>
				<!-- forum_crumbs_end -->
				<?php $this->stop() ?>
				</div>
				
				<!-- forum_qpost -->
				
				<div class="hr"><hr /></div>
				<div id="brd-about">
				<p id="copyright"><?= sprintf($this->lang_common['Powered by'], '<a href="http://punbb.informer.com/">PunBB</a>'.($this->forum_config['o_show_version'] == '1' ? ' '.$this->forum_config['o_cur_version'] : ''), '<a href="http://www.informer.com/">Informer Technologies, Inc</a>') ?></p>

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
