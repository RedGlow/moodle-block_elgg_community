<?php

require_once('ElggApiClient.php');

class block_elgg_community extends block_list {
    public function init() {
        $this->title = get_string('elgg_community', 'block_elgg_community');
        $this->version = 2009092102;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
            'all' => true,
            'site-index' => false
        );
    }

    public function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $COURSE, $USER;

        $this->content        = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->text  = '';

        // Check that admin has configured the block properly
        if (empty($CFG->block_elgg_community_elgg_url) ||
            empty($CFG->block_elgg_community_secret) ||
            empty($CFG->block_elgg_community_public)
        ) {
            $this->content->footer = get_string('no_config', 'block_elgg_community');
            return $this->content;
        }

	    // Create the API object
        try {
            $elgg = ElggApiClient::create_instance($CFG, $USER);
        } catch(Exception $e) {
            $this->content->footer = $e->getMessage();
            return $this->content;
        }

        $url = $CFG->block_elgg_community_elgg_url;

        // Create groups url
        if ($this->config->use_groups) {
            $group_guid = $elgg->getGroupGUID($COURSE->shortname);

            $group_url = "{$url}groups/profile/{$group_guid}/{$COURSE->shortname}-{$CFG->block_elgg_community_name}/";
        }

	// Create callback moodle url
	$callback_url = $CFG->wwwroot . "/blocks/elgg_community/callback.php";

	// Get login url
	$login_url = $elgg->getLoginURL();
	$login_url .= "?callback_url=" . urlencode($callback_url) . "&group_url=" . urlencode($group_url) . "&username=" . $USER->username;

	// Produce login link
        $elgg_link = get_string('go_to_community', 'block_elgg_community');
        $this->content->footer = "<a href=\"$login_url\">$elgg_link</a>";

        // Start the main content of the block
        // Get the recent posts
        if ($this->config->display_discussions && $this->config->use_groups) {
            $discussions = get_string('discussions', 'block_elgg_community');
            $this->content->items[] = "<em style=\"font-weight: bold;\">$discussions</em><hr>";
            if ($posts = $elgg->post('elgg.get_group_discussions', array('group_guid' => $group_guid))) {
                foreach($posts as $post) {
                    $this->content->items[] = "<a href=\"{$post->url}\">{$post->title}</a><br />" .
                    "<span style=\"color:grey; font-style:italic; font-size: 90%;\">{$post->user}, {$post->time}</span>";
                }
                $discussions_link = get_string('all_discussions', 'block_elgg_community');
                $this->content->items[] = "<a href=\"{$url}discussion/owner/{$group_guid}\" style=\"font-size: 90%; float:right;\">$discussions_link</a>";
            } else {
                $this->content->items[] = get_string('no_posts_found', 'block_elgg_community');
            }
            $this->content->items[] = '';
        }

        // Get blog posts
        if ($this->config->display_blogs) {
            $blogs_title = get_string('blog_posts', 'block_elgg_community');
            $this->content->items[] = "<em style=\"font-weight: bold;\">$blogs_title</em><hr>";

            $posts = $elgg->post('elgg.get_objects', array('object_type' => 'blog', 'tag' => $COURSE->shortname));
            if ($posts) {
                foreach($posts as $post) {
                    $this->content->items[] = '<a href="' . $post->url . '">' . $post->title . '</a><br /><span style="color:grey; font-style:italic; font-size: 90%;">' . $post->user . ', ' . $post->time . '</span>';
                }

                $this->content->items[] = "<a href=\"{$url}blog/group/{$group_guid}/all\" style=\"font-size: 90%; float:right;\">" . get_string('all_blogs', 'block_elgg_community') . '</a>';
            } else {
                $this->content->items[] = get_string('no_blog_posts_found', 'block_elgg_community');
            }
            $this->content->items[] = '';
        }

        // Get files
        if ($this->config->display_files) {
            $this->content->items[] = '<em style="font-weight: bold;">' . get_string('files', 'block_elgg_community') . '</em><hr>';
            $posts = $elgg->post('elgg.get_objects', array('object_type' => 'file', 'tag' => $COURSE->shortname));
            if ($posts) {
                foreach($posts as $post) {
                    $this->content->items[] = '<a href="' . $post->url . '">' . $post->title . '</a><br /><span style="color:grey; font-style:italic; font-size: 90%;">' . $post->user . ', ' . $post->time . '</span>';
                }
                $all_files = get_string('all_files', 'block_elgg_community');
                $this->content->items[] = "<a href=\"{$url}file/group/{$group_guid}/all\" style=\"font-size: 90%; float:right;\">$all_files</a>";
            } else {
                $this->content->items[] = get_string('no_files_found', 'block_elgg_community');
            }
            $this->content->items[] = '';
        }

        return $this->content;
    }
}
