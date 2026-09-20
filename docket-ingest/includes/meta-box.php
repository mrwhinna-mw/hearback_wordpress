<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows, on an ingested item's edit screen, the verbatim excerpt the
 * extraction was based on. Without this the approval step is just a
 * person agreeing with a model's summary; with it, they can check the
 * case number against the document's own words before publishing.
 */
function di_add_meta_box() {
	add_meta_box(
		'di_provenance',
		__( 'Ingested From Document', 'docket-ingest' ),
		'di_render_meta_box',
		'hb_decision',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'di_add_meta_box' );

function di_render_meta_box( $post ) {
	$quote = get_post_meta( $post->ID, '_di_source_quote', true );
	$file  = get_post_meta( $post->ID, '_di_source_file', true );
	$when  = get_post_meta( $post->ID, '_di_ingested_at', true );

	if ( ! $quote && ! $file && ! $when ) {
		echo '<p>' . esc_html__( 'This item was created by hand, not ingested from a document.', 'docket-ingest' ) . '</p>';
		return;
	}

	if ( $file ) {
		echo '<p><strong>' . esc_html__( 'File:', 'docket-ingest' ) . '</strong> ' . esc_html( $file ) . '</p>';
	}

	$source_url = get_post_meta( $post->ID, '_hb_source_url', true );
	if ( $source_url ) {
		printf(
			'<p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
			esc_url( $source_url ),
			esc_html__( 'Open original source', 'docket-ingest' )
		);
	}

	if ( $quote ) {
		echo '<p><strong>' . esc_html__( 'Verbatim excerpt:', 'docket-ingest' ) . '</strong></p>';
		echo '<blockquote style="margin:0;padding:8px;background:#f6f7f7;border-left:3px solid #c3c4c7;font-style:italic;">'
			. esc_html( $quote ) . '</blockquote>';
		echo '<p class="description">' . esc_html__( 'Check this against the source document before publishing.', 'docket-ingest' ) . '</p>';
	}

	if ( $when ) {
		echo '<p class="description">' . esc_html(
			sprintf(
				/* translators: %s: date/time */
				__( 'Ingested %s', 'docket-ingest' ),
				$when
			)
		) . '</p>';
	}
}
