<?php

/**
 * Provides authentication via MIT certificates.
 */
class MITAuthCertificates implements MITAuthBackend {
	/**
	 * Returns the authentication data stored in the client-supplied certificate.
	 */
	function getAuthenticationData() {
		global $wgMITCertificateOrganization;

		if( $_SERVER['SSL_CLIENT_VERIFY'] != 'SUCCESS' )
			return null;

		if( $_SERVER['SSL_CLIENT_S_DN_O'] != $wgMITCertificateOrganization )
			return null;

		$username = preg_replace( '/@.*/', '', $_SERVER['SSL_CLIENT_S_DN_Email'] );
		return (object)array(
			'username' => $username,
			'email' => $_SERVER['SSL_CLIENT_S_DN_Email'],
			'realname' => $_SERVER['SSL_CLIENT_S_DN_CN'],
			'credentials' => new MITAuthCredentials( $username ),
		);
	}

	/**
	 * Redirects the user to the port/server with certificate request enabled.
	 */
	function redirectToAuthenticator( $type, $query ) {
		global $wgOut;

		if( is_array( $query ) ) {
			$query['type'] = $type;
		} elseif( is_string( $query ) && $query ) {
			$query .= "&type={$type}";
		} else {
			$query = "&type={$type}";
		}
		$url = $this->getCertificateURL( SpecialPage::getTitleFor( 'MITLogin', 'auth' ), $query );
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
