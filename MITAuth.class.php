<?php

/**
 * Class which represents the credentials for an account from MIT or related
 * identity provider.
 */
class MITAuthCredentials {
	public $provider;
	public $username;

	public function __construct( $username, $provider = 'MIT' ) {
		$this->provider = $provider;
		$this->username = $username;
	}

	public function isMIT() {
		return $this->provider == 'MIT';
	}

	public function getDisplayName() {
		switch( $this->provider ) {
			case 'MIT':
				return wfMsg( 'mitauth-accttype-native', $this->username );
		}
	}
	
	public function serialize() {
		return "{$this->provider}::{$this->username}";
	}

	public static function unserialize( $str ) {
		list( $provider, $username ) = explode( '::', $str, 2 );
		return new self( $username, $provider );
	}
}

/**
 * Generic methods related to authentication.
 */
class MITAuth {
	const MARK_LENGTH = 16;
	const MARK_EXPIRY = 60;
	const MARK_EXPIRY_LONG = 600;

	private static function getBackend() {
		static $singleton;

		if( !$singleton ) 
			$singleton = new MITAuthCertificates();
		return $singleton;
	}

	/**
	 * Gets the cache which actually works, avoiding dummy clients
	 * which will cause authentication marks fail epically.
	 */
	public static function getRealCache() {
		global $wgMainCacheType, $wgMemc, $parserMemc;

		if( $wgMainCacheType == CACHE_NONE ) {
			return $parserMemc;
		} else {
			return $wgMemc;
		}
	}

	/**
	 * Lookup user credential for the given user. Returns MITAuthCredentials
	 * or null if the account is not linked.
	 */
	public static function getUserCredentials( $user, $useMaster = false ) {
		// TODO: add caching
		if( !$user instanceof User ) {
			$user = User::newFromName( $user );
			if( !$user )
				return null;
		}

		$dbr = wfGetDB( $useMaster ? DB_MASTER : DB_SLAVE );
		$row = $dbr->selectRow( 'mit_account_links', '*', array( 'mal_user_id' => $user->getID() ), __METHOD__ );
		if( $row ) {
			return new MITAuthCredentials( $row->mal_linked_name, $row->mal_linked_provider );
		} else {
			return null;
		}
	}

	/**
	 * Lookup user by linked credentials. Returns User object or null.
	 */
	public static function getUserByCredentials( $credentials, $useMaster = false ) {
		// TODO: add caching

		$dbr = wfGetDB( $useMaster ? DB_MASTER : DB_SLAVE );
		$row = $dbr->selectRow( 'mit_account_links', 'mal_user_id', array( 'mal_linked_provider' => $credentials->provider, 'mal_linked_name' => $credentials->username ), __METHOD__ );
		if( $row ) {
			return User::newFromId( $row->mal_user_id );
		} else {
			return null;
		}
	}

	/**
	 * Establish a link between a MediaWiki user and MIT account.
	 */
	public static function linkUserAccount( $user, $credentials ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'mit_account_links',
			array(
				'mal_user_id' => $user->getID(),
				'mal_linked_provider' => $credentials->provider,
				'mal_linked_name' => $credentials->username,
			),
			__METHOD__
		);
	}

	/**
	 * Returns a URL of a wiki page which does not have the 444
	 * port.
	 */
	public static function getNormalURL( $title, $query = '' ) {
		global $wgMITCertificateServer;

		$base = $title->getLocalURL( $query );
		if( $wgMITNormalServer ) {
			return "https://{$wgMITNormalServer}{$base}";
		} else {
			return "https://{$_SERVER['SERVER_NAME']}{$base}";
		}
	}

	public static function getAuthenticationData() { return self::getBackend()->getAuthenticationData(); }
	public static function redirectToAuthenticator( $type ) { return self::getBackend()->redirectToAuthenticator( $type ); }

	public static function generateAuthenticationMark( $id, $longTerm = false ) {
		$memc = self::getRealCache();

		$mark = base_convert( MWCryptRand::generateHex( self::MARK_LENGTH ), 16, 32 );
		$expiry = wfTimestamp() + $longTerm ? self::MARK_EXPIRY_LONG : self::MARK_EXPIRY;
		$memc->set( wfMemcKey( 'mitauth', 'mark', $mark ), $id, $expiry  );
		return $mark;
	}

	public static function reclaimAuthenticationMark( $mark ) {
		$memc = self::getRealCache();

		$key = wfMemcKey( 'mitauth', 'mark', $mark );
		$id = $memc->get( $key );
		$memc->delete( $key );
		return $id;
	}
}

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
}

interface MITAuthBackend {
	function getAuthenticationData();
	function redirectToAuthenticator( $type );
}

class MITAuthCertificates implements MITAuthBackend {
	private static function parseCertificate( $cert ) {
		$bits = explode( '/', $cert );
		$result = array();
		foreach( $bits as $bit ) {
			if( preg_match( '/^\s*(.*?)\s*=\s*(.*)\s*$/s', $bit, $match ) ) {
				$result[ $match[1] ] = $match[2];
			}
		}
		return $result;
	}

	function getAuthenticationData() {
		global $wgMITCertificateOrganization;
		if( $_SERVER['SSL_CLIENT_VERIFY'] != 'SUCCESS' )
			return null;

		$cert = self::parseCertificate( $_SERVER['SSL_CLIENT_S_DN'] );
		if( $cert['O'] != $wgMITCertificateOrganization )
			return null;

		$username = preg_replace( '/@.*/', '', $cert['emailAddress'] );
		return (object)array(
			'username' => $username,
			'email' => $cert['emailAddress'],
			'realname' => $cert['CN'],
			'credentials' => new MITAuthCredentials( $username ),
		);
	}

	function redirectToAuthenticator( $type ) {
		global $wgOut;

		$url = $this->getCertificateURL( SpecialPage::getTitleFor( 'MITLogin', 'auth' ), array( 'type' => $type ) );
		$wgOut->redirect( $url );
	}

	function getCertificateURL( $title, $query = '' ) {
		global $wgMITCertificateServer;

		$base = $title->getLocalURL( $query );
		if( $wgMITCertificateServer ) {
			return "https://{$wgMITCertificateServer}{$base}";
		} else {
			return "https://{$_SERVER['SERVER_NAME']}:444{$base}";
		}
	}
}
