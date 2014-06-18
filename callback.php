<?php

// Include required libraries
require_once('../../config.php');
require_once('ElggApiClient.php');

// Require that the user is logged in
require_login();

// Get global objects
global $CFG, $USER;

// Create API instance
$elgg = ElggApiClient::create_instance($CFG, $USER);

// Produce the login token
$login_token = $elgg->produceLoginToken();

// Create the login URL
$login_url = $elgg->getTokenLoginUrl() . "?token=" . $login_token . "&group_url=" . urlencode($_GET['group_url']);

// Redirect to destination
header('Location: ' . $login_url);
?>
