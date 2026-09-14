<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the docket item content below the normal post content
 * whenever a single hb_decision is viewed - so any theme's default
 * single-post template already works, with no custom page template
 * required.
 */
function hb_filter_decision_content( $content ) {
	if ( is_singular( 'hb_decision' ) && in_the_loop() && is_main_query() ) {
		return $content . hb_render_decision( get_the_ID() );
	}
	return $content;
}
add_filter( 'the_content', 'hb_filter_decision_content' );

function hb_render_timeline_html( $decision_id ) {
	$timeline = hb_get_timeline( $decision_id );
	$html     = '<ol class="hb-timeline">';
	foreach ( $timeline['stages'] as $i => $label ) {
		$step  = $i + 1;
		$state = $step < $timeline['current'] ? 'done' : ( $step === $timeline['current'] ? 'current' : 'upcoming' );
		$html .= sprintf(
			'<li class="hb-timeline__step hb-timeline__step--%1$s"><span class="hb-timeline__num">%2$d</span><span class="hb-timeline__label">%3$s</span></li>',
			esc_attr( $state ),
			(int) $step,
			esc_html( $label )
		);
	}
	$html .= '</ol>';
	return $html;
}

function hb_render_decision( $decision_id ) {
	$decision_question = get_post_meta( $decision_id, '_hb_decision_question', true );
	$owner_name        = get_post_meta( $decision_id, '_hb_response_owner_name', true );
	$owner_role        = get_post_meta( $decision_id, '_hb_response_owner_role', true );
	$response_by       = get_post_meta( $decision_id, '_hb_response_by_date', true );
	$status            = hb_get_decision_status( $decision_id );
	$comments_on       = hb_comments_enabled( $decision_id );

	ob_start();
	?>
	<div class="hb-decision" id="hb-decision-<?php echo esc_attr( $decision_id ); ?>">

		<?php if ( $decision_question ) : ?>
			<p class="hb-decision__eyebrow"><?php esc_html_e( 'The question', 'hearback-cabinet' ); ?></p>
			<p class="hb-decision__question"><?php echo esc_html( $decision_question ); ?></p>
		<?php endif; ?>

		<p class="hb-decision__meta">
			<?php if ( $owner_name ) : ?>
				<?php
				printf(
					/* translators: 1: name, 2: role */
					esc_html__( 'Owns the response: %1$s (%2$s).', 'hearback-cabinet' ),
					esc_html( $owner_name ),
					esc_html( $owner_role )
				);
				?>
			<?php endif; ?>
			<?php if ( $response_by ) : ?>
				<?php
				printf(
					/* translators: %s: date */
					esc_html__( ' Response due by %s.', 'hearback-cabinet' ),
					esc_html( $response_by )
				);
				?>
			<?php endif; ?>
		</p>

		<?php if ( $comments_on ) : ?>

			<?php echo hb_render_timeline_html( $decision_id ); // phpcs:ignore -- built from escaped parts above. ?>

			<?php if ( in_array( $status, array( 'synthesis_published', 'reviewing', 'answered' ), true ) ) : ?>
				<?php echo hb_render_synthesis( $decision_id ); // phpcs:ignore ?>
			<?php endif; ?>

			<?php if ( 'answered' === $status ) : ?>
				<?php echo hb_render_response( $decision_id ); // phpcs:ignore ?>
			<?php endif; ?>

			<?php if ( 'open' === $status ) : ?>
				<?php echo hb_render_submission_form( $decision_id ); // phpcs:ignore ?>
			<?php else : ?>
				<p class="hb-decision__closed"><?php esc_html_e( 'Comment period is not currently open for this item.', 'hearback-cabinet' ); ?></p>
			<?php endif; ?>

		<?php else : ?>

			<?php // Comments disabled: skip straight to the outcome, if there is one. ?>
			<?php if ( 'answered' === $status ) : ?>
				<?php echo hb_render_response( $decision_id ); // phpcs:ignore ?>
			<?php else : ?>
				<p class="hb-decision__closed"><?php esc_html_e( 'This is a posted item. No public comment period applies.', 'hearback-cabinet' ); ?></p>
			<?php endif; ?>

		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}

function hb_render_synthesis( $decision_id ) {
	$themes = get_posts(
		array(
			'post_type'      => 'hb_theme',
			'posts_per_page' => -1,
			'meta_key'       => '_hb_decision_id',
			'meta_value'     => $decision_id,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	$total_submissions = count(
		get_posts(
			array(
				'post_type'      => 'hb_submission',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_hb_decision_id',
				'meta_value'     => $decision_id,
			)
		)
	);

	ob_start();
	?>
	<section class="hb-synthesis">
		<h2><?php esc_html_e( 'What we heard', 'hearback-cabinet' ); ?></h2>
		<p><?php
			printf(
				/* translators: %d: number of submissions */
				esc_html( _n( '%d comment received.', '%d comments received.', $total_submissions, 'hearback-cabinet' ) ),
				(int) $total_submissions
			);
		?></p>
		<?php foreach ( $themes as $theme ) : ?>
			<?php
			$quotes = get_posts(
				array(
					'post_type'      => 'hb_submission',
					'posts_per_page' => 3,
					'meta_query'     => array( // phpcs:ignore -- small, admin-controlled dataset.
						array(
							'key'   => '_hb_theme_id',
							'value' => $theme->ID,
						),
						array(
							'key'   => '_hb_featured',
							'value' => 1,
						),
					),
				)
			);
			?>
			<div class="hb-theme">
				<h3><?php echo esc_html( $theme->post_title ); ?></h3>
				<?php if ( $theme->post_content ) : ?>
					<p><?php echo esc_html( $theme->post_content ); ?></p>
				<?php endif; ?>
				<?php foreach ( $quotes as $quote ) : ?>
					<?php $neighborhood = get_post_meta( $quote->ID, '_hb_neighborhood', true ); ?>
					<blockquote class="hb-quote">
						<p>&ldquo;<?php echo esc_html( $quote->post_content ); ?>&rdquo;</p>
						<?php if ( $neighborhood ) : ?>
							<cite><?php echo esc_html( $neighborhood ); ?></cite>
						<?php endif; ?>
					</blockquote>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</section>
	<?php
	return ob_get_clean();
}

function hb_render_response( $decision_id ) {
	$status_key = get_post_meta( $decision_id, '_hb_response_status', true );
	$option     = hb_get_outcome_option( $status_key );
	$rationale  = get_post_meta( $decision_id, '_hb_response_rationale', true );
	$next_step  = get_post_meta( $decision_id, '_hb_response_next_step', true );
	$date       = get_post_meta( $decision_id, '_hb_response_published_at', true );
	$tone       = $option ? $option['tone'] : 'neutral';
	$label      = $option ? $option['label'] : '';

	ob_start();
	?>
	<section class="hb-response hb-response--<?php echo esc_attr( $tone ); ?>">
		<h2><?php esc_html_e( 'Outcome', 'hearback-cabinet' ); ?></h2>
		<div class="hb-response__badge-row">
			<span class="hb-response__badge"><?php echo esc_html( $label ); ?></span>
			<?php if ( $date ) : ?>
				<span class="hb-response__date"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $date ) ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $rationale ) : ?>
			<p><?php echo esc_html( $rationale ); ?></p>
		<?php endif; ?>
		<?php if ( $next_step ) : ?>
			<div class="hb-next-step">
				<span class="hb-next-step__badge"><?php esc_html_e( 'Next step', 'hearback-cabinet' ); ?></span>
				<p><?php echo esc_html( $next_step ); ?></p>
			</div>
		<?php endif; ?>
	</section>
	<?php
	return ob_get_clean();
}

function hb_render_submission_form( $decision_id ) {
	$nonce_action = 'hb_submit_' . $decision_id;
	ob_start();
	?>
	<section class="hb-form">
		<?php if ( isset( $_GET['hb_submitted'] ) ) : ?>
			<p class="hb-form__notice hb-form__notice--success"><?php esc_html_e( 'Thanks — your comment has been received.', 'hearback-cabinet' ); ?></p>
		<?php elseif ( isset( $_GET['hb_error'] ) ) : ?>
			<p class="hb-form__notice hb-form__notice--error"><?php esc_html_e( 'Your comment could not be submitted. Please try again.', 'hearback-cabinet' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Add a public comment', 'hearback-cabinet' ); ?></h2>
		<div class="hb-form__sample">
			<?php esc_html_e( 'This is where a public comment would go if a resident had submitted one.', 'hearback-cabinet' ); ?>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="hb_submit" />
			<input type="hidden" name="hb_decision_id" value="<?php echo esc_attr( $decision_id ); ?>" />
			<?php wp_nonce_field( $nonce_action, 'hb_submission_nonce_field' ); ?>

			<!-- Honeypot: hidden from real visitors via CSS, left blank by them. -->
			<p class="hb-honeypot" aria-hidden="true">
				<label for="hb_website"><?php esc_html_e( 'Website', 'hearback-cabinet' ); ?></label>
				<input type="text" id="hb_website" name="hb_website" tabindex="-1" autocomplete="off" />
			</p>

			<p>
				<label for="hb_comment"><?php esc_html_e( 'Your comment (required)', 'hearback-cabinet' ); ?></label><br />
				<textarea id="hb_comment" name="hb_comment" rows="5" required></textarea>
			</p>
			<p>
				<label for="hb_name"><?php esc_html_e( 'Name (optional)', 'hearback-cabinet' ); ?></label>
				<input type="text" id="hb_name" name="hb_name" />
			</p>
			<p>
				<label for="hb_email"><?php esc_html_e( 'Email (optional, never shown publicly)', 'hearback-cabinet' ); ?></label>
				<input type="email" id="hb_email" name="hb_email" />
			</p>
			<p>
				<label for="hb_neighborhood"><?php esc_html_e( 'Neighborhood (optional)', 'hearback-cabinet' ); ?></label>
				<input type="text" id="hb_neighborhood" name="hb_neighborhood" />
			</p>
			<p>
				<label>
					<input type="checkbox" name="hb_consent" value="1" />
					<?php esc_html_e( 'I understand this comment becomes part of the public record and may be quoted anonymously in the synthesis.', 'hearback-cabinet' ); ?>
				</label>
			</p>
			<p>
				<button type="submit" class="hb-submit"><?php esc_html_e( 'Submit comment', 'hearback-cabinet' ); ?></button>
			</p>
		</form>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * The docket archive: every item, newest first, with its current
 * status.
 */
function hb_render_cabinet() {
	$decisions = get_posts(
		array(
			'post_type'      => 'hb_decision',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$status_labels = hb_get_status_labels();

	ob_start();
	?>
	<div class="hb-cabinet">
		<?php if ( empty( $decisions ) ) : ?>
			<p><?php esc_html_e( 'No items have been posted yet.', 'hearback-cabinet' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $decisions as $decision ) : ?>
			<?php $status = hb_get_decision_status( $decision->ID ); ?>
			<article class="hb-cabinet__item hb-cabinet__item--<?php echo esc_attr( $status ); ?>">
				<h2><a href="<?php echo esc_url( get_permalink( $decision->ID ) ); ?>"><?php echo esc_html( $decision->post_title ); ?></a></h2>
				<p class="hb-cabinet__status"><?php echo esc_html( $status_labels[ $status ] ); ?></p>
				<p class="hb-cabinet__excerpt"><?php echo esc_html( wp_trim_words( $decision->post_content, 24 ) ); ?></p>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
