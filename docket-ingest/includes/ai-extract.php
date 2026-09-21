<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DI_MAX_CHARS', 60000 );

/**
 * Every field below except 'question' is extracted verbatim from the
 * document. 'question' is the one field the model writes rather than
 * copies, because an agenda never contains a resident-facing question
 * - the review screen labels it as drafted for exactly that reason.
 */
function di_extraction_prompt( $text ) {
	$instructions = <<<'EOT'
You are extracting agenda items from a local government meeting document
(an agenda, minutes, or meeting transcript) so they can be published for
public comment.

Reply with raw JSON only - no markdown code fences, no commentary before
or after. Return a JSON object shaped like:
{"items":[{"title":"","question":"","context":"","external_reference":null,"address":null,"recommendation":null,"source_quote":""}]}

Rules, in order of importance:
1. NEVER invent, infer, or embellish. If the document does not state
   something, use null. An empty field is always better than a guessed one.
2. "context", "external_reference", "address" and "recommendation" must
   come from the document's own words. Do not paraphrase case numbers,
   addresses, dates, or vote outcomes.
3. "source_quote" must be a short VERBATIM excerpt, copied character for
   character from the document, that a human can search for to verify this
   item. Never paraphrase inside source_quote.
4. "question" is the only field you may write yourself: a short, plain-language
   yes/no question a resident could answer, derived strictly from the item.
   Example: "Should ANC 6A support a third-story addition at 1226 F Street NE?"
5. "title" is a short label for the item (under 100 characters).
6. Only include substantive items a resident could reasonably comment on.
   Skip procedural entries like "Call to order", "Adoption of Agenda",
   "Approval of Minutes", "Adjourn", and routine officer/committee report
   acceptances.
7. If the document contains no such items, return {"items":[]}.

The document follows.
EOT;

	return $instructions . "\n\n---\n\n" . $text;
}

/**
 * @return array|WP_Error List of normalized item arrays.
 */
function di_extract_items( $text ) {
	if ( ! class_exists( 'Meow_MWAI_Query_Text' ) ) {
		return new WP_Error( 'di_no_ai_engine', __( 'AI Engine is not active, so the document cannot be analyzed.', 'docket-ingest' ) );
	}

	global $mwai_core;
	if ( ! isset( $mwai_core ) || ! is_object( $mwai_core ) ) {
		return new WP_Error( 'di_no_ai_engine', __( 'AI Engine is active but did not initialize. Try reloading, or check its settings.', 'docket-ingest' ) );
	}

	$env_id = get_option( 'di_env_id', '' );
	$model  = get_option( 'di_model', '' );

	// Without an explicit environment, AI Engine falls back to its default
	// one, which it auto-creates as OpenAI - on a Gemini-only site that
	// surfaces as a baffling "model gpt-... is not available" error.
	if ( empty( $env_id ) ) {
		return new WP_Error( 'di_no_env', __( 'Choose an AI provider on this page first. If the list is empty, add an API key under Meow Apps > AI Engine > Settings > AI.', 'docket-ingest' ) );
	}

	$truncated = false;
	if ( strlen( $text ) > DI_MAX_CHARS ) {
		$text      = substr( $text, 0, DI_MAX_CHARS );
		$truncated = true;
	}

	// Built directly rather than via $mwai->simpleJsonQuery(): that helper
	// json_decodes internally and returns only the result, so when a model
	// wraps its JSON in ```json fences (Gemini does, even in JSON mode) the
	// raw text is already lost and there is nothing left to clean up.
	try {
		$query = new Meow_MWAI_Query_Text( di_extraction_prompt( $text ) );
		$query->set_env_id( $env_id );
		if ( ! empty( $model ) ) {
			$query->set_model( $model );
		}
		$query->set_response_format( 'json' );
		$reply = $mwai_core->run_query( $query );
	} catch ( Exception $e ) {
		return new WP_Error( 'di_ai_failed', $e->getMessage() );
	}

	$raw = isset( $reply->result ) ? (string) $reply->result : '';

	if ( '' === trim( $raw ) ) {
		return new WP_Error(
			'di_ai_empty',
			__( 'The AI returned an empty reply. If you are using Gemini, try enabling "Use Standard API" under AI Engine > Settings > AI, which switches Gemini off its newer Interactions API.', 'docket-ingest' )
		);
	}

	$result = di_decode_json_reply( $raw );

	if ( ! is_array( $result ) ) {
		return new WP_Error(
			'di_ai_bad_json',
			sprintf(
				/* translators: %s: start of the model's reply */
				__( 'The AI replied, but not with valid JSON, so nothing could be extracted. Start of its reply: %s', 'docket-ingest' ),
				mb_substr( $raw, 0, 300 )
			)
		);
	}

	// Accept both {"items":[...]} and a bare [...] - models drift on this.
	$raw_items = isset( $result['items'] ) && is_array( $result['items'] ) ? $result['items'] : $result;

	$items = array();
	foreach ( $raw_items as $raw_item ) {
		$item = di_normalize_item( $raw_item );
		if ( ! empty( $item['title'] ) ) {
			$items[] = $item;
		}
	}

	if ( empty( $items ) ) {
		return new WP_Error( 'di_no_items', __( 'No commentable agenda items were found in that document.', 'docket-ingest' ) );
	}

	if ( $truncated ) {
		$items[0]['_truncated'] = true;
	}

	return $items;
}

/**
 * Models don't reliably return bare JSON even when asked: Gemini wraps it
 * in markdown fences, others add a sentence before or after. Try the
 * clean cases first, then fall back to the outermost {...} or [...] span.
 *
 * @return array|null
 */
function di_decode_json_reply( $raw ) {
	$raw = trim( (string) $raw );

	if ( preg_match( '/^```(?:json)?\s*(.*?)\s*```$/s', $raw, $m ) ) {
		$raw = $m[1];
	}

	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) ) {
		return $decoded;
	}

	$start = strcspn( $raw, '{[' );
	$end   = max( (int) strrpos( $raw, '}' ), (int) strrpos( $raw, ']' ) );
	if ( $start < strlen( $raw ) && $end > $start ) {
		$decoded = json_decode( substr( $raw, $start, $end - $start + 1 ), true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}

	return null;
}

/**
 * Model output is untrusted input like any other: every field is cast
 * and sanitized before it goes near the database or the screen.
 */
function di_normalize_item( $raw ) {
	if ( ! is_array( $raw ) ) {
		return array( 'title' => '' );
	}

	$get = function ( $key ) use ( $raw ) {
		if ( ! isset( $raw[ $key ] ) || null === $raw[ $key ] || is_array( $raw[ $key ] ) ) {
			return '';
		}
		return sanitize_textarea_field( (string) $raw[ $key ] );
	};

	return array(
		'title'              => sanitize_text_field( mb_substr( $get( 'title' ), 0, 200 ) ),
		'question'           => $get( 'question' ),
		'context'            => $get( 'context' ),
		'external_reference' => sanitize_text_field( $get( 'external_reference' ) ),
		'address'            => sanitize_text_field( $get( 'address' ) ),
		'recommendation'     => $get( 'recommendation' ),
		'source_quote'       => $get( 'source_quote' ),
	);
}
