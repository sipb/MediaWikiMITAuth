<?php
/**
 * Copyright (c) 2012 Victor Vasiliev
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN
 * AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Extension credits
 */
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'MITAuth',
	//'url' => '',
	'author' => 'Victor Vasiliev',
	'descriptionmsg' => 'mitauth-desc',
);

/**
 * Initialization of the autoloaders, and special extension pages.
 */
$mitauthBase = __DIR__;
$wgAutoloadClasses['MITAuth'] = "$mitauthBase/MITAuth.class.php";
$wgAutoloadClasses['MITAuthBackend'] = "$mitauthBase/MITAuth.class.php";
$wgAutoloadClasses['MITAuthHooks'] = "$mitauthBase/MITAuth.hooks.php";
$wgAutoloadClasses['MITAuthCertificates'] = "$mitauthBase/backends/Certificates.php";
$wgAutoloadClasses['SpecialMITLogin'] = "$mitauthBase/SpecialMITLogin.php";

$wgExtensionMessagesFiles['MITAuth'] = "$mitauthBase/MITAuth.i18n.php";

$wgHooks['GetPreferences'][] = 'MITAuthHooks::addPreferencesInfo';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'MITAuthHooks::registerSchemaUpdates';
$wgHooks['UserLoginForm'][] = 'MITAuthHooks::changeLoginForm';
$wgHooks['AuthPluginSetup'][] = 'MITAuthHooks::setupAuthPlugin';
$wgHooks['AbortNewAccount'][] = 'MITAuthHooks::abortNewAccount';
$wgHooks['AutopromoteCondition'][] = 'MITAuthHooks::handleAutopromoteCondition';

$wgSpecialPages['MITLogin'] = 'SpecialMITLogin';

$wgResourceModules['ext.mitauth.userlogin'] = array(
	'localBasePath' => __DIR__ . '/ui',
	'remoteExtPath' => 'MITAuth/ui',
	'styles' => 'userlogin.css',
);

define( 'APCOND_IN_MOIRA_GROUP', 'inmoiragroup' );

//==============================================================================

/**
 * Authentication modes:
 * * combined — allows both people with MIT ID and without it to sign on and login
 * * mitonly — allows only MIT people to sign on, but they get to choose their own usename
 * * integrated (not implemented) — allows only MIT people to sign on
 *     and automatically makes their account names equal to their real names
 */
$wgMITAuthenticationMode = 'mitonly';

$wgMITAuthenticationMethod = 'certificate';

$wgMITCertificateServer = false;
$wgMITNormalServer = false;

$wgMITCertificateOrganization = 'Massachusetts Institute of Technology';
