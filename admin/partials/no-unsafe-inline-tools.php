<form method="post" action="options.php" class="no-unsafe-inline-tools-form">
	<?php
	settings_fields( 'no-unsafe-inline_tools_group' );
	do_settings_sections( 'no-unsafe-inline-tools-page' );
	submit_button();
	?>
</form>
<hr style="width:100%;text-align:center;margin-left:0">
<ul class="nunil-tools-wrapper">
	<li class="nunil-tools-box nunil-trigger-clustering">
		<div class="nunil-tools-button-wrapper no-unsafe-inline-clustering">
			<div class="nunil-tools-button-container">
				<?php
				echo '<form method="post" action="" class="no-unsafe-inline-tools-button-form">';
				submit_button(
					esc_html__( 'Trigger Clustering', 'no-unsafe-inline' ),
					'primary nunil_trigger_clustering',
					'nunil_trigger_clustering',
					true
				);
				wp_nonce_field( 'nunil_trigger_clustering_nonce', 'clustering_nonce' );
				echo '</form>';
				?>
			</div>
			<div class="nunil-tools-button-description">
				<?php
				echo ( esc_html__( 'Clustering scripts is important to machine learning.', 'no-unsafe-inline' ) );
				echo '<br>';
				echo ( esc_html__( 'You should cluster your scripts when you stop capturing new ones.', 'no-unsafe-inline' ) );
				?>
			</div>
		</div>
	</li>
	<li class="nunil-tools-box nunil-test-policy">
		<div class="nunil-tools-button-wrapper no-unsafe-inline-clean-database">
			<div class="nunil-tools-button-container">
				<?php
				echo '<form method="post" action="" class="no-unsafe-inline-tools-button-form">';
				submit_button(
					esc_html__( 'Clean database', 'no-unsafe-inline' ),
					'secondary nunil_clean_database',
					'nunil_clean_database',
					true
				);
				wp_nonce_field( 'nunil_trigger_clean_database', 'clean_db_nonce' );
				echo '</form>';
				?>
			</div>
			<div class="nunil-tools-button-description">
				<?php
				echo ( esc_html__( 'Clean all scripts from the database.', 'no-unsafe-inline' ) );
				echo '<br>';
				echo ( esc_html__( 'This will not clear your settings and base -src csp rules.', 'no-unsafe-inline' ) );
				?>
			</div>
		</div>
	</li>
	<li class="nunil-tools-box nunil-enable-protection">
		<div class="nunil-tools-button-wrapper no-unsafe-inline-test-classifier">
			<div class="nunil-tools-button-container">
				<?php
				echo '<form method="post" action="" class="no-unsafe-inline-tools-button-form">';
				submit_button(
					esc_html__( 'Test Classifier', 'no-unsafe-inline' ),
					'secondary nunil_test_classifier',
					'nunil_test_classifier',
					true
				);
				wp_nonce_field( 'nunil_test_classifier_nonce', 'test_clussifier_nonce' );
				echo '</form>';
				?>
			</div>
			<div class="nunil-tools-button-description">
				<?php
				echo ( esc_html__( 'Test the classifier.', 'no-unsafe-inline' ) );
				echo '<br>';
				echo ( esc_html__( 'This is just a classifier test to check php-ml settings.', 'no-unsafe-inline' ) );
				?>
				
			</div>
		</div>
	</li>
</ul>
<hr style="width:100%;text-align:center;margin-left:0">

<?php
	// ~ echo '<form method="post" action="" class="no-unsafe-inline-clustering">';
	// ~ submit_button(
		// ~ esc_html__( 'Trigger Clustering', 'no-unsafe-inline' ),
		// ~ 'primary nunil_trigger_clustering',
		// ~ 'nunil_trigger_clustering',
		// ~ true );
		// ~ wp_nonce_field( 'nunil_trigger_clustering_nonce', 'clustering_nonce' );
	// ~ echo '</form>';
	echo '<div id="trigger_clustering_result"></div>';

	echo '<div id="nunil_summary_inline_table_container" class="nunil_summary_inline_table_container">';
	echo No_Unsafe_Inline_Admin::output_summary_inline_table();
	echo '</div>';


	echo '<div id="clean_database_result"></div>';

	echo '<div id="test_classifier_result"></div>';


?>
