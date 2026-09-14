document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.hb-submission-row' ).forEach( function ( row ) {
		var consent = row.querySelector( '.hb-consent-flag' );
		var featured = row.querySelector( '.hb-featured-checkbox' );
		if ( ! consent || ! featured ) {
			return;
		}
		featured.disabled = consent.value !== '1';
	} );
} );
