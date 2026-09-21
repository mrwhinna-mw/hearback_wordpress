<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tags marking a model as unable to read a document and reply in text.
 * 'deprecated' is here because AI Engine flags retired models with it, and
 * offering them just leads to "model is not available" errors.
 */
function di_excluded_model_tags() {
	return array( 'deprecated', 'image', 'image-generation', 'embedding', 'audio', 'realtime', 'video' );
}

function di_provider_label( $type ) {
	$labels = array(
		'openai'     => 'OpenAI',
		'azure'      => 'Azure OpenAI',
		'google'     => 'Google Gemini',
		'anthropic'  => 'Anthropic',
		'openrouter' => 'OpenRouter',
		'mistral'    => 'Mistral',
		'perplexity' => 'Perplexity',
		'xai'        => 'xAI',
	);
	return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( (string) $type );
}

/**
 * Text-capable models for one environment, "latest" aliases first since
 * they follow the provider's newest release and so are the least likely
 * to be retired out from under a site.
 *
 * Google and OpenRouter store a fetched model list on each environment;
 * OpenAI and Anthropic share a built-in list per provider type.
 */
function di_models_for_env( $env, $options ) {
	$type = isset( $env['type'] ) ? $env['type'] : '';
	$raw  = ! empty( $env['models'] ) && is_array( $env['models'] ) ? $env['models'] : array();

	if ( empty( $raw ) && ! empty( $options['ai_engines'] ) ) {
		foreach ( $options['ai_engines'] as $engine ) {
			if ( isset( $engine['type'] ) && $engine['type'] === $type && ! empty( $engine['models'] ) ) {
				$raw = $engine['models'];
				break;
			}
		}
	}
	if ( ! empty( $options['ai_models'] ) ) {
		foreach ( $options['ai_models'] as $custom ) {
			if ( isset( $custom['type'] ) && $custom['type'] === $type ) {
				$raw[] = $custom;
			}
		}
	}

	$models = array();
	foreach ( $raw as $m ) {
		$id       = isset( $m['model'] ) ? (string) $m['model'] : '';
		$tags     = isset( $m['tags'] ) && is_array( $m['tags'] ) ? $m['tags'] : array();
		$features = isset( $m['features'] ) && is_array( $m['features'] ) ? $m['features'] : array();

		if ( '' === $id || isset( $models[ $id ] ) ) {
			continue;
		}
		$can_chat = in_array( 'chat', $tags, true ) || in_array( 'completion', $features, true );
		if ( ! $can_chat || array_intersect( $tags, di_excluded_model_tags() ) ) {
			continue;
		}

		// Detected from the ID, not AI Engine's 'latest' tag: that tag marks
		// true -latest aliases for Google but merely "newest model" for
		// OpenAI and Anthropic, whose tagged models are fixed and can still
		// be retired.
		$models[ $id ] = array(
			'id'     => $id,
			'name'   => isset( $m['name'] ) && '' !== $m['name'] ? (string) $m['name'] : $id,
			'latest' => '-latest' === substr( $id, -7 ),
		);
	}

	$models = array_values( $models );
	usort(
		$models,
		function ( $a, $b ) {
			return (int) $b['latest'] - (int) $a['latest'];
		}
	);

	return $models;
}

/**
 * Every environment configured in AI Engine, keyed ones first. Reads
 * get_all_options() rather than the raw mwai_options row: the per-type
 * model lists (ai_engines) are assembled at runtime and are not stored.
 */
function di_environments() {
	global $mwai_core;
	if ( ! isset( $mwai_core ) || ! is_object( $mwai_core ) || ! method_exists( $mwai_core, 'get_all_options' ) ) {
		return array();
	}

	$options = $mwai_core->get_all_options();
	$envs    = array();

	foreach ( isset( $options['ai_envs'] ) ? $options['ai_envs'] : array() as $env ) {
		if ( empty( $env['id'] ) ) {
			continue;
		}
		$type   = isset( $env['type'] ) ? (string) $env['type'] : '';
		$envs[] = array(
			'id'      => (string) $env['id'],
			'name'    => ! empty( $env['name'] ) ? (string) $env['name'] : di_provider_label( $type ),
			'type'    => $type,
			'has_key' => ! empty( $env['apikey'] ),
			'models'  => di_models_for_env( $env, $options ),
		);
	}

	usort(
		$envs,
		function ( $a, $b ) {
			return (int) $b['has_key'] - (int) $a['has_key'];
		}
	);

	return $envs;
}

/**
 * The previously used environment if it still exists, otherwise the first
 * one that has an API key - the auto-created OpenAI environment has none,
 * and defaulting to it is exactly what produced the "gpt-... is not
 * available" error on Gemini-only sites.
 */
function di_selected_env_id( $envs ) {
	$saved = get_option( 'di_env_id', '' );
	foreach ( $envs as $env ) {
		if ( $env['id'] === $saved ) {
			return $saved;
		}
	}
	foreach ( $envs as $env ) {
		if ( $env['has_key'] ) {
			return $env['id'];
		}
	}
	return empty( $envs ) ? '' : $envs[0]['id'];
}
