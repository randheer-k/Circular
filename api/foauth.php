<?php

/**
 * @see tmhOAuth/examples/oauth_flow.php
 * 
 * If the user doesn't already have an active session, we use `authenticate` instead of `authorize` 
 * so that a user having already authorized the app doesn't have to do it again.
 * If on the other hand, the user already has an active session, we use `authorize`
 * so that he can log out from his current Twitter account and log in as somebody else if needed.
 */

require 'vendor/autoload.php'; 
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;

header('Content-type: application/json');

session_set_cookie_params(60*60*24*30);
ini_set('session.gc_maxlifetime', 60*60*24*30);
session_start();


function outputError($tmhOAuth) {
	header('HTTP/1.1 500 Internal Server Error');
	echo json_encode($tmhOAuth->response['response']);
}

function wipe() {
	session_destroy();
	echo json_encode(array('wiped' => "success"));
}


// Step 1: Request a temporary token
function request_token() {
	FacebookSession::setDefaultApplication(APP_ID, APP_SECRET);
	$helper = new FacebookRedirectLoginHelper(APP_REDIRECT_URL, $apiVersion = NULL);
	return $helper->getLoginUrl(); 
}
  

/* Auth Flow */

if (isset($_REQUEST['wipe'])) {
	// Logging out
	wipe();
	return;
}

if (isset($_REQUEST['start'])) {
	// Let's start the OAuth dance
	request_token();
}
elseif (isset($_REQUEST['oauth_verifier'])) {
	access_token($tmhOAuth);
}
elseif (isset($_SESSION['account'])) {
	// Some credentials already stored in this browser session.
	
	foreach ($_SESSION['account']['users'] as $id => $user) {
		if (!isset($user['profile_image_url'])) {
			verify_credentials($tmhOAuth, $id);
		}
	}
	
	echo json_encode($_SESSION['account']);
}
else {
	// User's not logged in.
	echo json_encode(array('loggedin' => false));
}

