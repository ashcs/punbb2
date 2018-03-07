<?php 
// Display the "Jump to" drop list
if ($this->forum_user['g_read_board'] == '1' && $this->forum_config['o_quickjump'] == '1')
{
    ($hook = \Punbb\ForumFunction::get_hook('ft_about_pre_quickjump')) ? eval($hook) : null;

	// Load cached quickjump
	if (file_exists(FORUM_CACHE_DIR.'cache_quickjump_'.$this->forum_user['g_id'].'.php'))
	    include FORUM_CACHE_DIR.'cache_quickjump_'.$this->forum_user['g_id'].'.php';

	if (!defined('FORUM_QJ_LOADED'))
	{
	    \Punbb\Cache::generate_quickjump_cache($this->forum_user['g_id']);
	    require FORUM_CACHE_DIR.'cache_quickjump_'.$this->forum_user['g_id'].'.php';
	}
}

($hook = \Punbb\ForumFunction::get_hook('ft_about_pre_copyright')) ? eval($hook) : null;

?>
	<p id="copyright"><?php echo sprintf($this->lang_common['Powered by'], '<a href="http://punbb.informer.com/">PunBB</a>'.($this->forum_config['o_show_version'] == '1' ? ' '.$this->forum_config['o_cur_version'] : ''), '<a href="http://www.informer.com/">Informer Technologies, Inc</a>') ?></p>
