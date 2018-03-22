<?php
namespace Punbb\Controllers;

use Punbb\ForumFunction as F;
use \Pimple\Container;

define('FORUM_PAGE', 'post');

class Post extends Base {
    
    /**
     *
     * @var \Punbb\Data\PostGateway
     */
    protected $gateway;
    
    private $cur_posting;
    private $is_subscribed;
    private $is_admmod;

    public function __construct(Container $c) {
        parent::__construct(__CLASS__, $c);
    }

    function NewPostAction($topic_id, $quote_id = 0)
    {
        $c = $this->c;
        
        $cur_posting = $this->getCurPosting($topic_id);
            
        $forum_page['form_action'] = F::forum_link($c['url']['new_reply'], $topic_id);
        
        if ($quote_id) {
            $forum_page['quote'] = $this->getQuote($topic_id, $quote_id);
        }
                
        $c['breadcrumbs']->addCrumb($c['config']['o_board_title'],F::forum_link($c['url']['index']))
        ->addCrumb($cur_posting['forum_name'],F::forum_link($c['url']['forum'], array($cur_posting['id'], F::sef_friendly($cur_posting['forum_name']))))
        ->addCrumb($cur_posting['subject'], F::forum_link($c['url']['topic'], array($topic_id,F::sef_friendly($cur_posting['subject']))))
        ->addCrumb($c['lang_post']['Post reply']);
        
        $forum_page['total_post_count'] = $this->gateway->getAmountPost($topic_id);
        
        return $c['templates']->render('post', [
            'lang_post' => $c['lang_post'],
            'posts' => $this->gateway->getTopicPreview($topic_id, $c['config']),
            'tid' => $topic_id,
            'fid' => 0,
            'cur_posting' => $cur_posting,
            'is_subscribed' => $this->is_subscribed,
            'errors' => $this->errors,
            'forum_page'    => $forum_page
        ]);
    }

    function SendNewPostAction($topic_id) {
        
        $c = $this->c;
        
        $cur_posting = $this->getCurPosting($topic_id);
        $params = $this->processPosting(false);

        if (empty($this->errors) && ! isset($_POST['preview'])) {
            
            $post_info = array(
                'is_guest' => $c['user']['is_guest'],
                'poster' => $params['username'],
                'poster_id' => $c['user']['id'], // Always 1 for guest posts
                'poster_email' => ($c['user']['is_guest'] && $params['email'] != '') ? $params['email'] : null, // Always null for non-guest posts
                'subject' => $cur_posting['subject'],
                'message' => $params['message'],
                'hide_smilies' => $params['hide_smilies'],
                'posted' => time(),
                'subscr_action' => ($c['config']['o_subscriptions'] == '1' && $params['subscribe'] && ! $cur_posting['is_subscribed']) ? 1 : (($c['config']['o_subscriptions'] == '1' && ! $params['subscribe'] && $this->is_subscribed) ? 2 : 0),
                'topic_id' => $topic_id,
                'forum_id' => $cur_posting['id'],
                'update_user' => true,
                'update_unread' => true
            );
            
            F::add_post($post_info, $new_pid);
        
            F::redirect(F::forum_link($c['url']['post'], $new_pid), $c['lang_post']['Post redirect']);
        }
        
        return $this->NewPostAction($topic_id);
    }
    
    public function SendNewTopicAction($forum_id) {
        $c = $this->c;
        
        $cur_posting = $this->getCurPosting(0, $forum_id);
        $params = $this->processPosting(true);
        
        if (empty($this->errors) && ! isset($_POST['preview'])) {
            
            $post_info = array(
                'is_guest'		=> $c['user']['is_guest'],
                'poster'		=> $params['username'],
                'poster_id'		=> $c['user']['id'],	// Always 1 for guest posts
                'poster_email'	=> ($c['user']['is_guest'] && $params['email'] != '') ? $params['email'] : null,	// Always null for non-guest posts
                'subject'		=> $params['subject'],
                'message'		=> $params['message'],
                'hide_smilies'	=> $params['hide_smilies'],
                'posted'		=> time(),
                'subscribe'		=> ($c['config']['o_subscriptions'] == '1' && (isset($_POST['subscribe']) && $_POST['subscribe'] == '1')),
                'forum_id'		=> $forum_id,
                'forum_name'	=> $cur_posting['forum_name'],
                'update_user'	=> true,
                'update_unread'	=> true
            );
            
            F::add_topic($post_info, $new_tid, $new_pid);
            
            F::redirect(F::forum_link($c['url']['post'], $new_pid), $c['lang_post']['Post redirect']);
        }
        
        return $this->NewTopicAction($forum_id);
    }
    
    public function NewTopicAction($forum_id) {
        
        $c = $this->c;
        
        $cur_posting = $this->getCurPosting(0, $forum_id);
        
        $forum_page['form_action'] = F::forum_link($c['url']['new_topic'], $forum_id);
        
        $c['breadcrumbs']->addCrumb($c['config']['o_board_title'],F::forum_link($c['url']['index']))
        ->addCrumb($cur_posting['forum_name'],F::forum_link($c['url']['forum'], array($cur_posting['id'], F::sef_friendly($cur_posting['forum_name']))))
        ->addCrumb($c['lang_post']['Post new topic']);
        
        return $c['templates']->render('post', [
            'lang_post' => $c['lang_post'],
            'tid' => 0,
            'fid' => $forum_id,
            'cur_posting' => $cur_posting,
            'is_subscribed' => $this->is_subscribed,
            'errors' => $this->errors,
            'forum_page'    => $forum_page
        ]);
        
    }
    
    private function processPosting($fid = false) {
        $c = $this->c;
        // Make sure form_user is correct
        if (($c['user']['is_guest'] && $_POST['form_user'] != 'Guest') || (! $c['user']['is_guest'] && $_POST['form_user'] != $c['user']['username']))
            F::message($c['lang_common']['Bad request']);
        
        // Flood protection
        if (! isset($_POST['preview']) && $c['user']['last_post'] != '' && (time() - $c['user']['last_post']) < $c['user']['g_post_flood'] && (time() - $c['user']['last_post']) >= 0)
            $errors[] = sprintf($c['lang_post']['Flood'], $c['user']['g_post_flood']);
            
            // If it's a new topic
        if ($fid) {
            $params['subject'] = F::forum_trim($_POST['req_subject']);
            
            if ($params['subject'] == '')
                $errors[] = $c['lang_post']['No subject'];
                else if (utf8_strlen($params['subject']) > FORUM_SUBJECT_MAXIMUM_LENGTH)
                $errors[] = sprintf($c['lang_post']['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);
                else if ($c['config']['p_subject_all_caps'] == '0' && F::check_is_all_caps($params['subject']) && ! $this->is_admmod)
                $errors[] = $c['lang_post']['All caps subject'];
        }
        // If the user is logged in we get the username and e-mail from $c['user']
        if (! $c['user']['is_guest']) {
            $params['username'] = $c['user']['username'];
            $params['email'] = $c['user']['email'];
        }        // Otherwise it should be in $_POST
        else {
            $params['username'] = F::forum_trim($_POST['req_username']);
            $params['email'] = strtolower(F::forum_trim(($c['config']['p_force_guest_email'] == '1') ? $_POST['req_email'] : $_POST['email']));
            
            // Load the profile.php language file
            require FORUM_ROOT . 'lang/' . $c['user']['language'] . '/profile.php';
            
            // It's a guest, so we have to validate the username
            $errors = array_merge($errors, F::validate_username($params['username']));
            if ($c['config']['p_force_guest_email'] == '1' || $params['email'] != '') {
                if (! defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
                    require FORUM_ROOT . 'include/email.php';
                
                    if (! is_valid_email($params['email']))
                    $errors[] = $c['lang_post']['Invalid e-mail'];
                
                    if (is_banned_email($params['email']))
                    $errors[] = $lang_profile['Banned e-mail'];
            }
        }
        // If we're an administrator or moderator, make sure the CSRF token in $_POST is valid
        if ($c['user']['is_admmod'] && (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== F::generate_form_token(F::get_current_url())))
            $errors[] = $c['lang_post']['CSRF token mismatch'];
            
            // Clean up message from POST
        $params['message'] = F::forum_linebreaks(F::forum_trim($_POST['req_message']));
        
        if (strlen($params['message']) > FORUM_MAX_POSTSIZE_BYTES)
            $errors[] = sprintf($c['lang_post']['Too long message'], F::forum_number_format(strlen($params['message'])), F::forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
            else if ($c['config']['p_message_all_caps'] == '0' && F::check_is_all_caps($params['message']) && ! $this->is_admmod)
            $errors[] = $c['lang_post']['All caps message'];
        
        // Validate BBCode syntax
        if ($c['config']['p_message_bbcode'] == '1' || $c['config']['o_make_links'] == '1') {
            if (! defined('FORUM_PARSER_LOADED'))
                require FORUM_ROOT . 'include/parser.php';
            
                $params['message'] = preparse_bbcode($params['message'], $errors);
        }
        if ($params['message'] == '')
            $errors[] = $c['lang_post']['No message'];
        
        $params['hide_smilies'] = isset($_POST['hide_smilies']) ? 1 : 0;
        $params['subscribe'] = isset($_POST['subscribe']) ? 1 : 0;
        
        $this->errors = $errors;
        
        return $params;
    }
    
    private function getQuote($topic_id, $quote_id) {
        
        if (! ($quote_info = $this->gateway->getQuoteAndPoster($quote_id, $topic_id))) {
            F::message($c['lang_common']['Bad request']);
        }

        if ($this->c['config']['p_message_bbcode'] == '1') {
            // If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
            if (strpos($quote_info['poster'], '[') !== false || strpos($quote_info['poster'], ']') !== false) {
                if (strpos($quote_info['poster'], '\'') !== false)
                    $quote_info['poster'] = '"' . $quote_info['poster'] . '"';
                else
                    $quote_info['poster'] = '\'' . $quote_info['poster'] . '\'';
            } else {
                // Get the characters at the start and end of $q_poster
                $ends = utf8_substr($quote_info['poster'], 0, 1) . utf8_substr($quote_info['poster'], - 1, 1);
                
                // Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
                if ($ends == '\'\'')
                    $quote_info['poster'] = '"' . $quote_info['poster'] . '"';
                else if ($ends == '""')
                    $quote_info['poster'] = '\'' . $quote_info['poster'] . '\'';
            }
            
            $quote = '[quote=' . $quote_info['poster'] . ']' . $quote_info['message'] . '[/quote]' . "\n";
        } else
            $quote = '> ' . $quote_info['poster'] . ' ' . $this->c['lang_common']['wrote'] . ':' . "\n\n" . '> ' . $quote_info['message'] . "\n";
        
        return $quote;
    }
    
    private function getCurPosting($tid, $fid = 0) {
        if (! $this->cur_posting) {
            
            if (! ($this->cur_posting = $this->gateway->getPostingInfo($tid, $fid, $this->c['user'])))
                F::message($this->c['lang_common']['Bad request']);
            
            // Is someone trying to post into a redirect forum?
            if ($this->cur_posting['redirect_url'] != '')
                F::message($this->c['lang_common']['Bad request']);
        }
        
        $mods_array = ($this->cur_posting['moderators'] != '') ? unserialize($this->cur_posting['moderators']) : array();
        $this->is_admmod = ($this->c['user']['g_id'] == FORUM_ADMIN || ($this->c['user']['g_moderator'] == '1' && array_key_exists($this->c['user']['username'], $mods_array))) ? true : false;
        
        
        // Do we have permission to post?
        if ((($tid && (($this->cur_posting['post_replies'] == '' && $this->c['user']['g_post_replies'] == '0') || $this->cur_posting['post_replies'] == '0')) ||
            ($fid && (($this->cur_posting['post_topics'] == '' && $this->c['user']['g_post_topics'] == '0') || $this->cur_posting['post_topics'] == '0')) ||
            (isset($this->cur_posting['closed']) && $this->cur_posting['closed'] == '1')) &&
            !$this->is_admmod)
            F::message($lang_common['No permission']);
        
        $this->is_subscribed = $tid && $this->cur_posting['is_subscribed'];
        
        return $this->cur_posting;
    }
    
    
}