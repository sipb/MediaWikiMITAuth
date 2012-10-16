<?php
/**
 * Internationalisation file for extension MITAuth.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 */
$messages['en'] = array(
	'mitauth-desc' => 'Authenticate to MediaWiki with MIT credentials',

	'mitauth-page-login-desc' => 'Authentication via MIT',
	'mitauth-page-login-nocert-title' => 'Authentication failure',
	'mitauth-page-login-nocert' => 'Unable to authenticate the user, because no valid MIT authentication certificates were supplied.',
	'mitauth-page-login-notloggedin' => 'Unable to link your account because you are not logged in.',
	'mitauth-page-login-alreadylinked' => 'Unable to link your account to MIT because you MediaWiki account is already linked to $1.',
	'mitauth-page-login-anotherlinked' => 'Unable to link your MediaWiki account to $2, because $1 is already linked to it.',
	'mitauth-page-login-linkedsuccess' => 'Your account is successfully linked to $1.',
	'mitauth-page-login-badmark' => 'The authentication mark supplied is invalid. Please retry the process again.',
	'mitauth-page-login-welcome' => "You are now successfully logged as '''\$1'''.",
	'mitauth-page-login-signup-header' => 'You do not have an account on this wiki. Please enter your preferred MediaWiki username (which will be displayed through the user interface), and it will be created for you.',
	'mitauth-page-login-signup-legend' => 'Account creation',
	'mitauth-page-login-signup-submit' => 'Create account',
	'mitauth-page-login-signup-username' => 'Username:',
	'mitauth-page-login-signup-badname' => '"$1" is not a valid MediaWiki username.',
	'mitauth-page-login-signup-alreadyexists' => 'Name "$1" is already in use.',
	'mitauth-page-login-signup-success' => 'Account $1 was successfully created and linked to your MIT identity. Welcome!',
	//'mitauth-page-login-signup-' => '',
	//'mitauth-page-login-' => '',

	'mitauth-prefs-status' => 'Link to MIT account:',
	'mitauth-prefs-notlinked' => "'''Not linked''' ([[Special:MITLogin/link|link]])",

	'mitauth-accttype-native' => '$1 (native MIT account)',
);

