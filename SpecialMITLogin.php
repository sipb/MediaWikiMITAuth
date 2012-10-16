<?php

/**
 * The special page which acts as a front-end to the authentication process.
 * It also handles certain logical processes like account creation.
 */
class SpecialMITLogin extends SpecialPage {
	public function __construct() {
		parent::__construct( 'MITLogin' );
	}

	function getDescription() {
		return wfMsg( 'mitauth-page-login-desc' );
	}

	function execute( $par = '' ) {
		global $wgOut, $wgRequest, $wgUser;

		$this->setHeaders();
		$wgOut->disallowUserJs();

		if( !$par ) {
			MITAuth::redirectToAuthenticator( 'login' );
			session_start();
			return;
		}

		$bits = explode( '/', $par );

		switch( $bits[0] ) {

			case 'auth':
				$data = MITAuth::getAuthenticationData();
				if( $data ) {
					$type = $wgRequest->getVal( 'type' );
					$credentialsString = $data->credentials->serialize();
					$mark = MITAuth::generateAuthenticationMark( $data );
					$url = MITAuth::getNormalURL( SpecialPage::getTitleFor( 'MITLogin', "{$type}/{$credentialsString}/{$mark}" ) );
					$wgOut->redirect( $url );
					return;
				} else {
					$wgOut->setPageTitle( wfMsg( 'mitauth-page-login-nocert-title' ) );
					$wgOut->addWikiMsg( 'mitauth-page-login-nocert' );
				}
				return;

			case 'link':
				MITAuth::redirectToAuthenticator( 'dolink' );
				return;

			case 'dolink':
				if( count( $bits ) < 3 ) break;
				list( $unused, $id, $mark ) = $bits;

				$data = MITAuth::reclaimAuthenticationMark( $mark );
				if( $data && $data->credentials->serialize() == $id ) {
					$credentials = MITAuthCredentials::unserialize( $id );
					if( !$wgUser->isLoggedIn() ) {
						$wgOut->addWikiMsg( 'mitauth-page-login-notloggedin' );
						return;
					}
					if( $existing = MITAuth::getUserCredentials( $wgUser, true ) ) {
						$wgOut->addWikiMsg( 'mitauth-page-login-alreadylinked', $existing->getDisplayName() );
						return;
					}
					if( $existing = MITAuth::getUserByCredentials( $credentials, true ) ) {
						$wgOut->addWikiMsg( 'mitauth-page-login-anotherlinked', $existing->getName(), $credentials->getDisplayName() );
						return;
					}

					MITAuth::linkUserAccount( $wgUser, $credentials );
					$wgOut->addWikiMsg( 'mitauth-page-login-linkedsuccess', $credentials->getDisplayName() );
				} else {
					$wgOut->addWikiMsg( 'mitauth-page-login-badmark' );
				}
				return;

			case 'login':
				if( count( $bits ) < 3 ) break;
				list( $unused, $id, $mark ) = $bits;

				$data = MITAuth::reclaimAuthenticationMark( $mark );
				if( $data && $data->credentials->serialize() == $id ) {
					$credentials = $data->credentials;
					$user = MITAuth::getUserByCredentials( $credentials );
					if( $user ) {
						$user->setOption( 'rememberpassword', 0 );
						$user->saveSettings();
						$user->setCookies();
						$wgOut->redirect( SpecialPage::getTitleFor( 'MITLogin', 'success' )->getLocalURL() );
					} else {
						$this->showSignupForm( $data );
					}
					
				} else {
					$wgOut->addWikiMsg( 'mitauth-page-login-badmark' );
				}
				return;

			case 'signup':
				if( !$wgRequest->wasPosted() || !($mark = $wgRequest->getVal( 'mark' )) )
					break;
				$data = MITAuth::reclaimAuthenticationMark( $mark );
				if( !$mark ) {
					$wgOut->addWikiMsg( 'mitauth-page-login-badmark' );
					return;
				}

				$username = $wgRequest->getVal( 'username' );
				$user = User::newFromName( $username, 'creatable' );

				if( !$user ) {
					$this->showSignupForm( $data, wfMsgHtml( 'mitauth-page-login-signup-badname', htmlspecialchars( $username ) ) );
					return;
				}
				if( $user->idForName() ) {
					$this->showSignupForm( $data, wfMsgHtml( 'mitauth-page-login-signup-alreadyexists', htmlspecialchars( $username ) ) );
					return;
				}

				$user->setEmail( $data->email );
				$user->confirmEmail();
				$user->setRealName( $data->realname );
				$user->setPassword( User::randomPassword() );
				$user->setOption( 'rememberpassword', 0 );
				$user->setToken();
				$user->addToDatabase();
				$user->saveSettings();
				MITAuth::linkUserAccount( $user, $data->credentials );

				$update = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
				$update->doUpdate();
				$user->addNewUserLogEntry();

				MITAuth::redirectToAuthenticator( 'login' );
				session_start();

				return;

			case 'success':
				$wgOut->addWikiMsg( 'mitauth-page-login-welcome', $wgUser->getName() );
				return;
		}
	}

	function showSignupForm( $data, $error = '' ) {
		global $wgOut, $wgRequest;

		$title = SpecialPage::getTitleFor( 'MITLogin', 'signup' );
		$username = $wgRequest->getVal( 'username' );
		$newmark = MITAuth::generateAuthenticationMark( $data, true );
		$legend = wfMsgHtml( 'mitauth-page-login-signup-legend' );
		$header = wfMsgHtml( 'mitauth-page-login-signup-header' );
		$usernamePrompt = wfMsgHtml( 'mitauth-page-login-signup-username' );
		$submit = wfMsgHtml( 'mitauth-page-login-signup-submit' );

		$html = "<fieldset><legend>{$legend}</legend>";
		if( $error ) {
			$html .= "<p><strong class='error'>{$error}</strong></p>";
		}
		$html .= "<p>{$header}</p>";

		$usernameInput = Xml::input( 'username', 64, $username );
		$submitButton = Xml::submitButton( $submit );

		$html .= Xml::openElement( 'form',
			array( 'action' => $title->getLocalURL(), 'method' => 'post' ) );
		$html .= "<table>";
		$html .= "<tr><td class='mw-label'>{$usernamePrompt}</td><td class='mw-input'>{$usernameInput}</td></tr>";
		$html .= "<tr><td class='mw-label'></td><td class='mw-input'>{$submitButton}</td></tr>";
		$html .= Xml::hidden( 'mark', $newmark );
		$html .= "</table></form></fieldset>";
		
		$wgOut->addHTML( $html );
	}
}
