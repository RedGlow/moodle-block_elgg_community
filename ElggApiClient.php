<?php

// Require class responsible for creating the actual API calls
require_once('ElggApiCall.php');

/**
 * Class with customized API methods
 */
class ElggApiClient {
    private $error = '';
    private $keys = array();
    private $elgg_url = '';
    private $client;
    private $token;
    private $groupGuid;

    /**
     * Set API credentials.
     */
    public function __construct(array $params) {
        if (is_array($params)) {
            if (isset($params['keys'])) {
                $this->keys = $params['keys'];
            }
            $this->elgg_url = $params['elgg_url'];
        }
        $this->client = new ElggApiCall();
    }

    /**
     * Initialize connection to Elgg.
     *
     * Get user specific authentication token if user is found from Elgg.
     * If user doesn't exist a new Elgg user account will be created.
     * 
     * @param array $params Array of user's name, username nad email
     */
    public function init ($params) {
        $this->token = $this->post('elgg.get_auth_token', $params);
        return !empty($this->token);
    }

    /**
     * Create a new ElggApiClient object and initializes it starting from
     * the current configuration and user.
     *
     * @param $CFG The global configuration object.
     * @param $USER The global user object.
     */
    public static function create_instance ($CFG, $USER) {
        // Set API credentials
        $api_params = array(
            'elgg_url' => $CFG->block_elgg_community_elgg_url,
            'keys' => array(
                'public' => $CFG->block_elgg_community_public,
                'private' => $CFG->block_elgg_community_secret,
            ),
        );

        // Initialize the API
        $elgg = new ElggApiClient($api_params);

        $params = array(
            'username' => $USER->username,
            'name' => "$USER->firstname $USER->lastname",
            'email' => $USER->email,
	    'community' => $CFG->block_elgg_community_name
        );
    
        // Test connection to Elgg
        $success = $elgg->init($params);

	// Return accordingly
	if ($success) {
	    return $elgg;
	} else {
	    return null;
	}
    }

    /**
     * Get group GUID.
     *
     * @param string $shortname The shortname of the course
     * @return int
     */
    public function getGroupGUID($shortname) {
        global $CFG;
        if (isset($this->groupGuid)) {
            return $this->groupGuid;
        } else {
            $this->groupGuid = $this->post('elgg.get_groupGUID', array(
	        'shortname' => $shortname,
		'community' => $CFG->block_elgg_community_name
	    ));
            return $this->groupGuid;
        }
    }

    /**
     * Get the login url of elgg.
     *
     * @return The login url to enter in Elgg.
     */
    public function getLoginURL() {
        if (isset($this->loginUrl)) {
            return $this->loginUrl;
        } else {
            $this->loginUrl = $this->post('elgg.get_login_url');
            return $this->loginUrl;
        }
    }

    /**
     * Get the URL where a token login can be performed.
     *
     * @return the login URL for token login.
     */
    public function getTokenLoginUrl() {
        if (isset($this->tokenLoginUrl)) {
            return $this->tokenLoginUrl;
        } else {
            $this->tokenLoginUrl = $this->post('elgg.get_token_login_url');
            return $this->tokenLoginUrl;
        }
    }

    /**
     * Produce a login token from elgg, valid only for a few seconds.
     *
     * @return The login token.
     */
    function produceLoginToken() {
        return $this->post('elgg.produce_login_token');
    }

    /**
     * Send request and handle the result.
     *
     * @param string $method
     * @param string $post_data
     * @return mixed The result or false on error
     */
    public function post($method, $params) {
        $url = "{$this->elgg_url}services/api/rest/json/";
        $call = array('method' => $method);

        if (isset($this->token)) {
            $params['auth_token'] = $this->token;
        }

        $post_data = null;
        if (isset($params) && is_array($params)) {
            $post_data = http_build_query($params);
        }

        try {
            $results = $this->client->sendApiCall($this->keys, $url, $call, 'POST', $post_data);

            $response = json_decode($results);

            if (is_object($response)) {
                if ($response->status === 0) {
                    // Request was succesfull. Return the requested data.
                    return $response->result;
                } else {
                    $this->error = $response->message;
                    return false;
                }
            } else {
                $this->error = get_string('misconfigured', 'block_elgg_community');
                return false;
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Return the possible error message.
     *
     * @return string
     */
    public function getError() {
        return $this->error;
    }
}
