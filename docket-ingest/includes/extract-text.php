<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File types we can turn into plain text today. PDF is deliberately
 * absent: it needs either a parser dependency or a vision model call,
 * and neither is worth carrying until an organization actually hits
 * the limitation. di_extract_text() returns a WP_Error naming the
 * workaround rather than failing silently.
 */
function di_supported_extensions() {
	return array( 'txt', 'md', 'docx' );
}

/**
 * @return string|WP_Error Plain text, or an error explaining why not.
 */
function di_extract_text( $file_path, $original_name ) {
	$ext = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );

	if ( ! in_array( $ext, di_supported_extensions(), true ) ) {
		if ( 'pdf' === $ext || 'doc' === $ext ) {
			return new WP_Error(
				'di_unsupported',
				sprintf(
					/* translators: %s: file extension */
					__( '.%s files are not supported yet. Open the file, copy its text, and save it as a .txt or .docx file instead.', 'docket-ingest' ),
					$ext
				)
			);
		}
		return new WP_Error(
			'di_unsupported',
			sprintf(
				/* translators: %s: comma-separated list of extensions */
				__( 'Unsupported file type. Upload one of: %s', 'docket-ingest' ),
				implode( ', ', di_supported_extensions() )
			)
		);
	}

	if ( 'docx' === $ext ) {
		$text = di_extract_docx( $file_path );
	} else {
		$text = file_get_contents( $file_path );
	}

	if ( is_wp_error( $text ) ) {
		return $text;
	}

	$text = trim( preg_replace( "/\n{3,}/", "\n\n", (string) $text ) );

	if ( '' === $text ) {
		return new WP_Error( 'di_empty', __( 'No readable text was found in that file.', 'docket-ingest' ) );
	}

	return $text;
}

/**
 * A .docx is a ZIP containing word/document.xml, so PHP can read it
 * natively with no parser library. Paragraph and break tags become
 * newlines before the rest of the XML is stripped, otherwise every
 * line in the document would run together into one blob.
 */
function di_extract_docx( $file_path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'di_no_zip', __( 'This server cannot read .docx files (PHP is missing ZipArchive). Save the file as .txt instead.', 'docket-ingest' ) );
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $file_path ) ) {
		return new WP_Error( 'di_bad_docx', __( 'That .docx file could not be opened. It may be corrupted.', 'docket-ingest' ) );
	}

	$xml = $zip->getFromName( 'word/document.xml' );
	$zip->close();

	if ( false === $xml ) {
		return new WP_Error( 'di_bad_docx', __( 'That file is not a readable Word document.', 'docket-ingest' ) );
	}

	$xml = str_replace( array( '</w:p>', '<w:br/>', '<w:br />' ), "\n", $xml );
	$xml = str_replace( array( '<w:tab/>', '<w:tab />' ), "\t", $xml );

	$text = wp_strip_all_tags( $xml );

	return html_entity_decode( $text, ENT_QUOTES | ENT_XML1, 'UTF-8' );
}
