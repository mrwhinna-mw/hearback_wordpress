<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comparing costs one AI call per matched item, so a document that
 * repeats a long back-catalogue of cases can't quietly run up a bill.
 */
define( 'DI_MAX_UPDATE_COMPARISONS', 12 );

function di_update_prompt( $existing_id, $item, $meeting_date ) {
	$existing_next_step = get_post_meta( $existing_id, '_hb_response_next_step', true );

	$known = wp_json_encode(
		array(
			'title'          => get_the_title( $existing_id ),
			'case_number'    => get_post_meta( $existing_id, '_hb_external_reference', true ),
			'what_we_know'   => wp_strip_all_tags( get_post_field( 'post_content', $existing_id ) ),
			'next_step'      => $existing_next_step,
		)
	);

	$incoming = wp_json_encode(
		array(
			'meeting_date'   => $meeting_date,
			'context'        => $item['context'],
			'recommendation' => $item['recommendation'],
			'address'        => $item['address'],
			'quote'          => $item['source_quote'],
		)
	);

	$instructions = <<<'EOT'
A local government body already has a docket item about a matter. A newer
meeting document mentions the same matter. Report only what the newer
document ADDS or CHANGES.

Reply with raw JSON only - no markdown fences, no commentary:
{"has_update":false,"summary":null,"quote":null,"next_step":null,"conflicts":[{"detail":"","already_recorded":"","newer_document":"","quote":""}]}

Rules, in order of importance:
1. NEVER invent or infer. Every field must come from the newer document's
   own words. Use null when it says nothing.
2. Set "has_update" to false when the newer document only repeats what is
   already known. Restating background is not an update.
3. "summary" is one or two plain sentences stating only what is new or
   changed - for example a hearing being rescheduled, a vote taken, a
   negotiation outcome, or a new count of letters. Do not restate the
   history.
4. "quote" must be a short VERBATIM excerpt from the newer document
   supporting the summary. Copy it character for character.
5. "next_step" is a single future action with its date, if the newer
   document names one, e.g. "BZA hearing on September 16, 2026".
   Otherwise null.
6. "conflicts" lists facts the newer document states DIFFERENTLY from
   what is already recorded - a changed date, count, or name. Each needs
   its own verbatim "quote". Rescheduling is a conflict only if the
   document presents it as a correction rather than a change. Use an
   empty list when there are none.
7. A proposed motion or recommendation is NOT a decision. Report it as
   proposed; never state that the body decided or approved something
   unless the document says it happened.
EOT;

	return $instructions . "\n\nALREADY RECORDED:\n" . $known . "\n\nNEWER DOCUMENT:\n" . $incoming;
}

/**
 * Asks the model what a document adds to an item already on the docket.
 *
 * @return array|WP_Error Normalized update, or WP_Error if the comparison
 *                        itself failed (the caller keeps going either way).
 */
function di_extract_update( $existing_id, $item, $meeting_date ) {
	$result = di_run_json_query( di_update_prompt( $existing_id, $item, $meeting_date ) );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$get = function ( $key ) use ( $result ) {
		if ( ! isset( $result[ $key ] ) || null === $result[ $key ] || is_array( $result[ $key ] ) ) {
			return '';
		}
		return sanitize_textarea_field( (string) $result[ $key ] );
	};

	$conflicts = array();
	if ( ! empty( $result['conflicts'] ) && is_array( $result['conflicts'] ) ) {
		foreach ( $result['conflicts'] as $conflict ) {
			if ( ! is_array( $conflict ) ) {
				continue;
			}
			$detail = isset( $conflict['detail'] ) ? sanitize_text_field( (string) $conflict['detail'] ) : '';
			if ( '' === $detail ) {
				continue;
			}
			$conflicts[] = array(
				'detail'           => $detail,
				'already_recorded' => isset( $conflict['already_recorded'] ) ? sanitize_text_field( (string) $conflict['already_recorded'] ) : '',
				'newer_document'   => isset( $conflict['newer_document'] ) ? sanitize_text_field( (string) $conflict['newer_document'] ) : '',
				'quote'            => isset( $conflict['quote'] ) ? sanitize_textarea_field( (string) $conflict['quote'] ) : '',
			);
		}
	}

	$summary = $get( 'summary' );

	return array(
		'existing_id'  => (int) $existing_id,
		'has_update'   => ! empty( $result['has_update'] ) && '' !== $summary,
		'summary'      => $summary,
		'quote'        => $get( 'quote' ),
		'next_step'    => $get( 'next_step' ),
		'meeting_date' => sanitize_text_field( $meeting_date ),
		'conflicts'    => $conflicts,
		'title'        => get_the_title( $existing_id ),
	);
}

/**
 * A document's stated meeting date, falling back to today so an update is
 * always dated by something.
 */
function di_update_date_label( $update ) {
	$date = ! empty( $update['meeting_date'] ) ? strtotime( $update['meeting_date'] ) : false;
	return date_i18n( get_option( 'date_format' ), $date ? $date : current_time( 'timestamp' ) );
}

/**
 * Appends a dated line to the item's public text and records where it came
 * from. Provenance lives under _di_ so Public Docket's own fields stay as
 * they are, apart from Next step when the admin asked for it.
 *
 * @return true|WP_Error
 */
function di_apply_update( $update, $set_next_step = false, $source_file = '', $source_url = '' ) {
	$post_id = (int) $update['existing_id'];
	$post    = get_post( $post_id );

	if ( ! $post || 'hb_decision' !== $post->post_type ) {
		return new WP_Error( 'di_missing_item', __( 'That docket item no longer exists.', 'public-docket' ) );
	}

	$label = di_update_date_label( $update );
	$line  = sprintf(
		/* translators: 1: date of the meeting, 2: what changed */
		__( 'Update (%1$s): %2$s', 'public-docket' ),
		$label,
		$update['summary']
	);

	$content = rtrim( (string) $post->post_content );
	$content = '' === $content ? $line : $content . "\n\n" . $line;

	$saved = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	if ( $set_next_step && ! empty( $update['next_step'] ) ) {
		update_post_meta( $post_id, '_hb_response_next_step', $update['next_step'] );
	}

	$log   = get_post_meta( $post_id, '_di_updates', true );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array(
		'date'        => $label,
		'summary'     => $update['summary'],
		'quote'       => $update['quote'],
		'next_step'   => $set_next_step ? $update['next_step'] : '',
		'conflicts'   => $update['conflicts'],
		'source_file' => $source_file,
		'source_url'  => $source_url,
		'applied_at'  => current_time( 'mysql' ),
	);
	update_post_meta( $post_id, '_di_updates', $log );

	return true;
}

function di_get_updates( $post_id ) {
	$log = get_post_meta( $post_id, '_di_updates', true );
	return is_array( $log ) ? $log : array();
}
