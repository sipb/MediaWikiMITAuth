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
}
