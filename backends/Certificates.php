<?php

/**
 * Provides authentication via MIT certificates.
 */
class MITAuthCertificates implements MITAuthBackend {
	/**
	 * Parses a certificate string into a dictionary.
	 */
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

	/**
	 * Returns the authentication data stored in the client-supplied certificate.
	 */
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

	/**
	 * Redirects the user to the port/server with certificate request enabled.
	 */
	function redirectToAuthenticator( $type ) {
		global $wgOut;

		$url = $this->getCertificateURL( SpecialPage::getTitleFor( 'MITLogin', 'auth' ), array( 'type' => $type ) );
		$wgOut->redirect( $url );
	}

	/**
	 * Returns the URL of the server where the certificate reading is performed.
	 */
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
