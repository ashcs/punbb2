<?php
namespace Punbb\Controllers;

use Punbb\ForumFunction as F;
use \Pimple\Container;


define('FORUM_PAGE', 'postedit');

class Edit extends Base {
    
    /**
     *
     * @var \Punbb\Data\EditGateway
     */
    protected $gateway;
    
    private $cur_post;
    
    private $is_admmod;
    
    private $can_edit_subject;
    
    public function __construct(Container $c) {
        
        if ($c['user']['g_read_board'] == '0')
            F::message($c['lang_common']['No view']);
        
        parent::__construct(__CLASS__, $c);
        $c['lang_post'] = require FORUM_ROOT.'lang/'.$c['user']['language'].'/post.php';
    }

    function UpdatePostAction($id) {
        $c = $this->c;
        
        $cur_post = $this->getCurPost($id);
        
        if ($this->can_edit_subject) {
            $subject = F::forum_trim($_POST['req_subject']);
            
            if ($subject == '')
                $errors[] = $lang_post['No subject'];
            else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
                $errors[] = sprintf($lang_post['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);
            else if ($forum_config['p_subject_all_caps'] == '0' && F::check_is_all_caps($subject) && ! $forum_page['is_admmod'])
                $subject = utf8_ucwords(utf8_strtolower($subject));
        }
        
        // Clean up message from POST
        $message = F::forum_linebreaks(F::forum_trim($_POST['req_message']));
        
        if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
            $errors[] = sprintf($lang_post['Too long message'], F::forum_number_format(strlen($message)), F::forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
        else if ($forum_config['p_message_all_caps'] == '0' && F::check_is_all_caps($message) && ! $forum_page['is_admmod'])
            $message = utf8_ucwords(utf8_strtolower($message));
        
        // Validate BBCode syntax
        if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1') {
            if (! defined('FORUM_PARSER_LOADED'))
                require FORUM_ROOT . 'include/parser.php';
            
            $message = preparse_bbcode($message, $errors);
        }
        
        if ($message == '')
            $errors[] = $lang_post['No message'];
        
        $hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;
        
        ($hook = F::get_hook('ed_end_validation')) ? eval($hook) : null;
        
        // Did everything go according to plan?
        if (empty($errors) && ! isset($_POST['preview'])) {
            ($hook = F::get_hook('ed_pre_post_edited')) ? eval($hook) : null;
            
            if (! defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
                require FORUM_ROOT . 'include/search_idx.php';
            
            if ($can_edit_subject) {
                // Update the topic and any redirect topics
                $c['EditGateway']->setTopicSubject($subject, $cur_post);
                // We changed the subject, so we need to take that into account when we update the search words
                update_search_index('edit', $id, $message, $subject);
            } else
                update_search_index('edit', $id, $message);
            
            // Update the post
            $c['EditGateway']->setPostMessage($id, $message, $hide_smilies, $forum_user, ! (! isset($_POST['silent']) || ! $forum_page['is_admmod']));
            
            ($hook = F::get_hook('ed_pre_redirect')) ? eval($hook) : null;
            
            F::redirect(F::forum_link($forum_url['post'], $id), $lang_post['Edit redirect']);
        }
        
        
    }
    
    function GetPostAction($id)
    {
        $c = $this->c;

        $cur_post = $this->getCurPost($id);
        $forum_page['is_admmod'] = $this->is_admmod;
        
        // Setup form
        $forum_page['form_action'] = F::forum_link($c['url']['edit'], $id);
        $forum_page['form_attributes'] = array();
        
        // Setup help
        $forum_page['main_head_options'] = array();
        if ($c['config']['p_message_bbcode'] == '1')
            $forum_page['text_options']['bbcode'] = '<span' . (empty($forum_page['text_options']) ? ' class="first-item"' : '') . '><a class="exthelp" href="' . F::forum_link($c['url']['help'], 'bbcode') . '" title="' . sprintf($c['lang_common']['Help page'], $c['lang_common']['BBCode']) . '">' . $c['lang_common']['BBCode'] . '</a></span>';
        if ($c['config']['p_message_img_tag'] == '1')
            $forum_page['text_options']['img'] = '<span' . (empty($forum_page['text_options']) ? ' class="first-item"' : '') . '><a class="exthelp" href="' . F::forum_link($c['url']['help'], 'img') . '" title="' . sprintf($c['lang_common']['Help page'], $c['lang_common']['Images']) . '">' . $c['lang_common']['Images'] . '</a></span>';
        if ($c['config']['o_smilies'] == '1')
            $forum_page['text_options']['smilies'] = '<span' . (empty($forum_page['text_options']) ? ' class="first-item"' : '') . '><a class="exthelp" href="' . F::forum_link($c['url']['help'], 'smilies') . '" title="' . sprintf($c['lang_common']['Help page'], $c['lang_common']['Smilies']) . '">' . $c['lang_common']['Smilies'] . '</a></span>';
        
        $c['breadcrumbs']->addCrumb($c['config']['o_board_title'], F::forum_link($c['url']['index']));
        $c['breadcrumbs']->addCrumb($cur_post['forum_name'], F::forum_link($c['url']['forum'], array(
            $cur_post['fid'],
            F::sef_friendly($cur_post['forum_name'])
        )));
        $c['breadcrumbs']->addCrumb($cur_post['subject'], F::forum_link($c['url']['topic'], array(
            $cur_post['tid'],
            F::sef_friendly($cur_post['subject'])
        )));
        $c['breadcrumbs']->addCrumb((($this->can_edit_subject) ? $c['lang_post']['Edit topic'] : $c['lang_post']['Edit reply']));
        
        return ['template' => 'postedit', 'data' => [
            'lang_post' => $c['lang_post'],
            'cur_post' => $cur_post,
            'id' => $id,
            'can_edit_subject' => $this->can_edit_subject,
            'forum_page'    => $forum_page,
            'errors'    => $this->errors,
        ]];
                    
    }
    
    private function getCurPost($id) {
        if (! $this->cur_pos) {
            // Fetch some info about the post, the topic and the forum
            if (!($this->cur_post = $this->gateway->getPostInfo($id, $this->c['user']))) {
                F::message($this->c['lang_common']['Bad request']);
            }
        }
        
        
        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($this->cur_post['moderators'] != '') ? unserialize($this->cur_post['moderators']) : array();
        $this->is_admmod = ($this->c['user']['g_id'] == FORUM_ADMIN || ($this->c['user']['g_moderator'] == '1' && array_key_exists($this->c['user']['username'], $mods_array))) ? true : false;
        
        // Do we have permission to edit this post?
        if (($this->c['user']['g_edit_posts'] == '0' || $this->cur_post['poster_id'] != $this->c['user']['id'] || $this->cur_post['closed'] == '1') && ! $this->is_admmod)
            F::message($this->c['lang_common']['No permission']);
        
        $this->can_edit_subject = $id == $this->cur_post['first_post_id'];
            
        return $this->cur_post;
    }
}