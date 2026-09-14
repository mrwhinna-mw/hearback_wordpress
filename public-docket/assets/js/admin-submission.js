/**
 * On the Submission edit screen, the "consent" checkbox is read-only
 * (set by the resident, not the admin) so this only needs to run once
 * on load - there's nothing for it to react to.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	var consent = document.querySelector( '#hb_submission_review input[type="checkbox"][disabled]' );
	var featured = document.getElementById( 'hb_featured' );
	if ( ! consent || ! featured ) {
		return;
	}
	featured.disabled = ! consent.checked;
} );
