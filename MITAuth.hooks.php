<?php

/**
 * Hooks to integrate MITAuth with MediaWiki.
 */
class MITAuthHooks {
	static function addPreferencesInfo( $user, &$preferences ) {
		$credentials = MITAuth::getUserCredentials( $user );

		if( $credentials )
			$text = $credentials->getDisplayName();
		else
			$text = wfMsgExt( 'mitauth-prefs-notlinked', array('parseinline') );

		// Code based on CentralAuth one
		$prefInsert =
			array( 'mitaccountlink' =>
				array(
					'section' => 'personal/info',
					'label-message' => 'mitauth-prefs-status',
					'type' => 'info',
					'raw' => true,
					'default' => "$text",
				),
			);

		$after = array_key_exists( 'registrationdate', $preferences ) ? 'registrationdate' : 'editcount';
		$preferences = wfArrayInsertAfter( $preferences, $prefInsert, $after );

		return true;
	}

	public static function registerSchemaUpdates( $updater = null ) {
		$updater->addExtensionTable( 'mit_account_links', dirname( __FILE__ ) . '/mitauth.sql' );
		return true;
	}

	public static function changeLoginForm( &$template ) {
		global $wgMITAuthenticationMode;

		switch( $wgMITAuthenticationMode ) {
			case 'mitonly':
			case 'integrated':
				$template = new MITOnlyTemplate();
				return false;
			case 'combined':
				$oldTemplate = $template;
				$template = new CombinedTemplate( $template->data );
				$template->data = $oldTemplate->data;
				return true;
		}
	}

	public static function setupAuthPlugin( &$auth ) {
		global $wgMITAuthenticationMode;

		if( $wgMITAuthenticationMode == 'integrated' || $wgMITAuthenticationMode == 'mitonly' ) {
			$auth = new MITAuthDisablingPlugin();
		}
		return true;
	}

	public static function abortNewAccount( $user, &$message ) {
		global $wgMITAuthenticationMode;

		if( $wgMITAuthenticationMode == 'integrated' || $wgMITAuthenticationMode == 'mitonly' ) {
			$message = "No, this will not work. This website is on restricted authentication mode";
			return false;
		}
		return true;
	}

	public static function handleAutopromoteCondition( $cond, $args, $user, &$result ) {
		if( $cond == APCOND_IN_MOIRA_GROUP ) {
			$credentials = MITAuth::getUserCredentials( $user );
			if( $credentials && $credentials->isMIT() ) {
				$members = MITAuth::getMoiraGroupMembers( $args[0] );
				if( in_array( $credentials->username, $members ) ) {
					$result = true;
					return false;
				}
			}

			$result = false;
			return false;
		}

		return true;
	}
}

/**
 * Plugin to block authentication from non-MITLogin soucres.
 */
class MITAuthDisablingPlugin extends AuthPlugin {
	public function authenticate( $username, $password ) {
		return false;
	}

	public function canCreateAccounts() {
		return false;
	}
}

/**
 * 
 */
class MITOnlyTemplate extends QuickTemplate {
	function execute() {
		global $wgOut, $wgRequest;

		$title = SpecialPage::getTitleFor( 'MITLogin' );
		$query = array();
		if( $returnto = $wgRequest->getVal( 'returnto' ) )
			$query['returnto'] = $returnto;
		if( $returntoquery = $wgRequest->getVal( 'returntoquery' ) )
			$query['returntoquery'] = $returntoquery;

		$wgOut->redirect( $title->getLocalURL( $query) );
	}
}

/**
 * 
 */
class CombinedTemplate extends UserloginTemplate {

	function execute() {
		global $wgOut, $wgRequest;

		$wgOut->addModuleStyles( 'ext.mitauth.userlogin' );

		$header = wfMsgHtml( 'mitauth-userlogin-header' );
		$loginUrl = SpecialPage::getTitleFor( 'MITLogin' )->getLocalURL( array(
			'returnto' => $wgRequest->getVal( 'returnto' ),
			'returntoquery' => $wgRequest->getVal( 'returntoquery' ),
		) );

		// We wrap both forms in a table in order
		// to make them have the same width
		echo "<table><tr><td>";

		echo "<div id='mit-certlogin'><h2>{$header}</h2>";
		echo "<a href='{$loginUrl}'><div id='mit-certlogin-wrapper'><div id='mit-certlogin-button'>Log in using MIT certificates<div id='mit-certlogin-smallremark'>You do not need to create an account</div></div></a>";
		echo "</div>";

		echo "<div class='visualClear'></div>";
		echo "</td></tr>";

		echo "<tr><td>";
		parent::execute();
		echo "</td></tr></table>";
	}

	function msg( $msg ) {
		if( $msg == 'login' )
			echo wfMsgHtml( 'mitauth-userlogin-otherheader' );
		else
			parent::msg( $msg );
	}
}
