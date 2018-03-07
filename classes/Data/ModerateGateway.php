<?php
namespace Punbb\Data;

use Punbb\ForumFunction;

class ModerateGateway
{

    private $forum_db;

    public function __construct($forum_db)
    {
        $this->forum_db = $forum_db;
    }

    public function getPosterIp($get_host)
    {
        $query = array(
            'SELECT' => 'p.poster_ip',
            'FROM' => 'posts AS p',
            'WHERE' => 'p.id=' . $get_host
        );
        
        ($hook = ForumFunction::get_hook('mr_view_ip_qr_get_poster_ip')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $this->forum_db->result($result);
    }

    public function getModeratingForum($fid, $forum_user)
    {
        // Get some info about the forum we're moderating
        $query = array(
            'SELECT' => 'f.forum_name, f.redirect_url, f.num_topics, f.moderators, f.sort_by',
            'FROM' => 'forums AS f',
            'JOINS' => array(
                array(
                    'LEFT JOIN' => 'forum_perms AS fp',
                    'ON' => '(fp.forum_id=f.id AND fp.group_id=' . $forum_user['g_id'] . ')'
                )
            ),
            'WHERE' => '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id=' . $fid
        );
        
        ($hook = ForumFunction::get_hook('mr_qr_get_forum_data')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $this->forum_db->fetch_assoc($result);
    }

    public function getTopicInfo($tid)
    {
        // Fetch some info about the topic
        $query = array(
            'SELECT' => 't.subject, t.poster, t.first_post_id, t.posted, t.num_replies',
            'FROM' => 'topics AS t',
            'WHERE' => 't.id=' . $tid . ' AND t.moved_to IS NULL'
        );
        
        ($hook = ForumFunction::get_hook('mr_post_actions_qr_get_topic_info')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $this->forum_db->fetch_assoc($result);
    }

    public function getPostCount($posts, $cur_topic, $tid)
    {
        // Verify that the post IDs are valid
        $query = array(
            'SELECT' => 'COUNT(p.id)',
            'FROM' => 'posts AS p',
            'WHERE' => 'p.id IN(' . implode(',', $posts) . ') AND p.id!=' . $cur_topic['first_post_id'] . ' AND p.topic_id=' . $tid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_posts_qr_verify_post_ids')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $this->forum_db->result($result);
    }

    public function deletePosts($posts)
    {
        
        // Delete the posts
        $query = array(
            'DELETE' => 'posts',
            'WHERE' => 'id IN(' . implode(',', $posts) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_posts_qr_delete_posts')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
    }

    public function splitPosts($posts, $new_subject, $fid)
    {
        // Get data from the new first post
        $query = array(
            'SELECT' => 'p.id, p.poster, p.posted',
            'FROM' => 'posts AS p',
            'WHERE' => 'p.id = ' . min($posts)
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_split_posts_qr_get_first_post_data')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        $first_post_data = $this->forum_db->fetch_assoc($result);
        
        // Create the new topic
        $query = array(
            'INSERT' => 'poster, subject, posted, first_post_id, forum_id',
            'INTO' => 'topics',
            'VALUES' => '\'' . $this->forum_db->escape($first_post_data['poster']) . '\', \'' . $this->forum_db->escape($new_subject) . '\', ' . $first_post_data['posted'] . ', ' . $first_post_data['id'] . ', ' . $fid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_split_posts_qr_ForumFunction::add_topic')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        $new_tid = $this->forum_db->insert_id();
        
        // Move the posts to the new topic
        $query = array(
            'UPDATE' => 'posts',
            'SET' => 'topic_id=' . $new_tid,
            'WHERE' => 'id IN(' . implode(',', $posts) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_split_posts_qr_move_posts')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $new_tid;
    }

    public function getPostFromTopic($tid, $forum_page, $forum_user)
    {
        // Retrieve the posts (and their respective poster)
        $query = array(
            'SELECT' => 'u.title, u.num_posts, g.g_id, g.g_user_title, p.id, p.poster, p.poster_id, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by',
            'FROM' => 'posts AS p',
            'JOINS' => array(
                array(
                    'INNER JOIN' => 'users AS u',
                    'ON' => 'u.id=p.poster_id'
                ),
                array(
                    'INNER JOIN' => 'groups AS g',
                    'ON' => 'g.g_id=u.group_id'
                )
            ),
            'WHERE' => 'p.topic_id=' . $tid,
            'ORDER BY' => 'p.id',
            'LIMIT' => $forum_page['start_from'] . ',' . $forum_user['disp_posts']
        );
        
        ($hook = ForumFunction::get_hook('mr_post_actions_qr_get_posts')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        $posts = [];
        
        while ($cur_post = $this->forum_db->fetch_assoc($result)) {
            $posts[] = $cur_post;
        }
        
        return $posts;
    }

    public function moveTopics($move_to_forum, $topics, $fid)
    {
        // Fetch the forum name for the forum we're moving to
        $query = array(
            'SELECT' => 'f.forum_name',
            'FROM' => 'forums AS f',
            'WHERE' => 'f.id=' . $move_to_forum
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_move_topics_qr_get_move_to_forum_name')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        $move_to_forum_name = $this->forum_db->result($result);
        
        if (! $move_to_forum_name)
            return false;
        
        // Verify that the topic IDs are valid
        $query = array(
            'SELECT' => 'COUNT(t.id)',
            'FROM' => 'topics AS t',
            'WHERE' => 't.id IN(' . implode(',', $topics) . ') AND t.forum_id=' . $fid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_move_topics_qr_verify_topic_ids')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        if ($this->forum_db->result($result) != count($topics))
            return false;
        
        // Delete any redirect topics if there are any (only if we moved/copied the topic back to where it where it was once moved from)
        $query = array(
            'DELETE' => 'topics',
            'WHERE' => 'forum_id=' . $move_to_forum . ' AND moved_to IN(' . implode(',', $topics) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_move_topics_qr_delete_redirect_topics')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        // Move the topic(s)
        $query = array(
            'UPDATE' => 'topics',
            'SET' => 'forum_id=' . $move_to_forum,
            'WHERE' => 'id IN(' . implode(',', $topics) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_move_topics_qr_move_topics')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $move_to_forum_name;
    }

    public function setRedirect($cur_topic, $fid)
    {
        // Fetch info for the redirect topic
        $query = array(
            'SELECT' => 't.poster, t.subject, t.posted, t.last_post',
            'FROM' => 'topics AS t',
            'WHERE' => 't.id=' . $cur_topic
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_move_topics_qr_get_redirect_topic_data')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        $moved_to = $this->forum_db->fetch_assoc($result);
        
        // Create the redirect topic
        $query = array(
            'INSERT' => 'poster, subject, posted, last_post, moved_to, forum_id',
            'INTO' => 'topics',
            'VALUES' => '\'' . $this->forum_db->escape($moved_to['poster']) . '\', \'' . $this->forum_db->escape($moved_to['subject']) . '\', ' . $moved_to['posted'] . ', ' . $moved_to['last_post'] . ', ' . $cur_topic . ', ' . $fid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_move_topics_qr_add_redirect_topic')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
    }

    public function getTopicSubject($topics, $fid = null)
    {
        // Fetch the topic subject
        $query = array(
            'SELECT' => 't.subject',
            'FROM' => 'topics AS t',
            'WHERE' => 't.id=' . $topics
        );
        
        if ($fid) {
            $query['WHERE'] .= ' AND forum_id='.$fid;
        }
        
        ($hook = ForumFunction::get_hook('mr_move_topics_qr_get_topic_to_move_subject')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        return $this->forum_db->result($result);
    }

    public function getForumsToMove($fid, $forum_user)
    {
        // Get forums we can move the post into
        $query = array(
            'SELECT' => 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name',
            'FROM' => 'categories AS c',
            'JOINS' => array(
                array(
                    'INNER JOIN' => 'forums AS f',
                    'ON' => 'c.id=f.cat_id'
                ),
                array(
                    'LEFT JOIN' => 'forum_perms AS fp',
                    'ON' => '(fp.forum_id=f.id AND fp.group_id=' . $forum_user['g_id'] . ')'
                )
            ),
            'WHERE' => '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL AND f.id!=' . $fid,
            'ORDER BY' => 'c.disp_position, c.id, f.disp_position'
        );
        
        ($hook = ForumFunction::get_hook('mr_move_topics_qr_get_target_forums')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        $forum_list = array();
        while ($cur_sel_forum = $this->forum_db->fetch_assoc($result)) {
            $forum_list[] = $cur_sel_forum;
        }
        
        return $forum_list;
    }

    public function mergeTopics($topics, $fid, $with_redirect)
    {
        // Verify that the topic IDs are valid
        $query = array(
            'SELECT' => 'COUNT(t.id), MIN(t.id)',
            'FROM' => 'topics AS t',
            'WHERE' => 't.id IN(' . implode(',', $topics) . ') AND t.moved_to IS NULL AND t.forum_id=' . $fid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_merge_topics_qr_verify_topic_ids')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        list ($num_topics, $merge_to_tid) = $this->forum_db->fetch_row($result);
        if ($num_topics != count($topics))
            return false;
        
        // Make any redirect topics point to our new, merged topic
        $query = array(
            'UPDATE' => 'topics',
            'SET' => 'moved_to=' . $merge_to_tid,
            'WHERE' => 'moved_to IN(' . implode(',', $topics) . ')'
        );
        
        // Should we create redirect topics?
        if ($with_redirect)
            $query['WHERE'] .= ' OR (id IN(' . implode(',', $topics) . ') AND id != ' . $merge_to_tid . ')';
        
        ($hook = ForumFunction::get_hook('mr_confirm_merge_topics_qr_fix_redirect_topics')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        // Merge the posts into the topic
        $query = array(
            'UPDATE' => 'posts',
            'SET' => 'topic_id=' . $merge_to_tid,
            'WHERE' => 'topic_id IN(' . implode(',', $topics) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_merge_topics_qr_merge_posts')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        // Delete any subscriptions
        $query = array(
            'DELETE' => 'subscriptions',
            'WHERE' => 'topic_id IN(' . implode(',', $topics) . ') AND topic_id != ' . $merge_to_tid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_merge_topics_qr_delete_subscriptions')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        if (! $with_redirect) {
            // Delete the topics that have been merged
            $query = array(
                'DELETE' => 'topics',
                'WHERE' => 'id IN(' . implode(',', $topics) . ') AND id != ' . $merge_to_tid
            );
            
            ($hook = ForumFunction::get_hook('mr_confirm_merge_topics_qr_delete_merged_topics')) ? eval($hook) : null;
            $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        }
        
        return $merge_to_tid;
    }

    public function getForumIds($topics, $fid)
    {
        // Verify that the topic IDs are valid
        $query = array(
            'SELECT' => 'COUNT(t.id)',
            'FROM' => 'topics AS t',
            'WHERE' => 't.id IN(' . implode(',', $topics) . ') AND t.forum_id=' . $fid
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_topics_qr_verify_topic_ids')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        if ($this->forum_db->result($result) != count($topics))
            return false;
        
        // Create an array of forum IDs that need to be synced
        $forum_ids = array(
            $fid
        );
        $query = array(
            'SELECT' => 't.forum_id',
            'FROM' => 'topics AS t',
            'WHERE' => 't.moved_to IN(' . implode(',', $topics) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_topics_qr_get_forums_to_sync')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        while ($row = $this->forum_db->fetch_row($result))
            $forum_ids[] = $row[0];
        
        return $forum_ids;
    }
    
    public function getPostIdsByTopics($topics) {
        // Create a list of the post ID's in the deleted topic and strip the search index
        $query = array(
            'SELECT'	=> 'p.id',
            'FROM'		=> 'posts AS p',
            'WHERE'		=> 'p.topic_id IN('.implode(',', $topics).')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_topics_qr_get_deleted_posts')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        $post_ids = array();
        while ($row = $this->forum_db->fetch_row($result))
            $post_ids[] = $row[0];
           
        return $post_ids;
    }
    
    
    public function deleteTopicsData($topics)
    {
        // Delete the topics and any redirect topics
        $query = array(
            'DELETE' => 'topics',
            'WHERE' => 'id IN(' . implode(',', $topics) . ') OR moved_to IN(' . implode(',', $topics) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_topics_qr_delete_topics')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        // Delete any subscriptions
        $query = array(
            'DELETE' => 'subscriptions',
            'WHERE' => 'topic_id IN(' . implode(',', $topics) . ')'
        );
        
        ($hook = ForumFunction::get_hook('mr_confirm_delete_topics_qr_delete_subscriptions')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        
        // Delete posts
        $query = array(
            'DELETE' => 'posts',
            'WHERE' => 'topic_id IN(' . implode(',', $topics) . ')'
        );
    }
    
    public function setTopicClosed($action, $topics, $fid) {
        $query = array(
            'UPDATE'	=> 'topics',
            'SET'		=> 'closed='.$action,
        );
        
        if (is_array($topics)) {
            $query['WHERE']	= 'id IN('.implode(',', $topics).') AND forum_id='.$fid;
        }
        else {
            $query['WHERE']	= 'id='.$topics.' AND forum_id='.$fid;
        }
        ($hook = ForumFunction::get_hook('mr_open_close_multi_topics_qr_open_close_topics')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
    }
    
    public function setTopicSticky($stick_id, $state, $fid) {
        $query = array(
            'UPDATE'	=> 'topics',
            'SET'		=> 'sticky='.$state,
            'WHERE'		=> 'id='.$stick_id.' AND forum_id='.$fid
        );
        
        ($hook = ForumFunction::get_hook('mr_stick_topic_qr_stick_topic')) ? eval($hook) : null;
        $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
    }
    
    public function getTopics($fid, $cur_forum, $forum_page, $forum_user, $forum_config) {
        // Select topics
        $query = array(
            'SELECT'	=> 't.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to',
            'FROM'		=> 'topics AS t',
            'WHERE'		=> 'forum_id='.$fid,
            'ORDER BY'	=> 't.sticky DESC, '.(($cur_forum['sort_by'] == '1') ? 't.posted' : 't.last_post').' DESC',
            'LIMIT'		=>	$forum_page['start_from'].', '.$forum_user['disp_topics']
        );
        
        // With "has posted" indication
        if (!$forum_user['is_guest'] && $forum_config['o_show_dot'] == '1')
        {
            $query['SELECT'] .= ', p.poster_id AS has_posted';
            $query['JOINS'][]	= array(
                'LEFT JOIN'		=> 'posts AS p',
                'ON'			=> '(p.poster_id='.$forum_user['id'].' AND p.topic_id=t.id)'
            );
            
            // Must have same columns as in prev SELECT
            $query['GROUP BY'] = 't.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id';
            
            ($hook = ForumFunction::get_hook('mr_qr_get_has_posted')) ? eval($hook) : null;
        }
        
        ($hook = ForumFunction::get_hook('mr_qr_get_topics')) ? eval($hook) : null;
        $result = $this->forum_db->query_build($query) or ForumFunction::error(__FILE__, __LINE__);
        $topics = [];
        while ($cur_topic = $this->forum_db->fetch_assoc($result))
        {
            $topics[] = $cur_topic;
        }
        
        return $topics;
    }
}

