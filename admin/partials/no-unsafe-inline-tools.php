<form method="post" action="options.php" class="no-unsafe-inline-tools-form">
	<?php
	settings_fields( 'no-unsafe-inline_tools_group' );
	do_settings_sections( 'no-unsafe-inline-tools-page' );
	submit_button();
	?>
</form>

<div id="nunil-spinner-blocks" class="nunil-spinner-blocks hidden"><div class="nunil-spinner-tools">
<div class='nunil-spinner-box-1'></div><div class='nunil-spinner-box-2'></div><div class='nunil-spinner-box-3'></div><div class='nunil-spinner-box-4'></div><div class='nunil-spinner-box-5'></div><div class='nunil-spinner-box-6'></div><div class='nunil-spinner-box-7'></div><div class='nunil-spinner-box-8'></div>
</div></div>
<hr class='nunil-tools-hr'>
<ul class="nunil-tools-wrapper">
	<li class="nunil-tools-box">
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
	<li class="nunil-tools-box">
		<div class="nunil-tools-button-wrapper no-unsafe-inline-prune-database">
			<div class="nunil-tools-button-container">
				<?php
				echo '<form method="post" action="" class="no-unsafe-inline-tools-button-form">';
				submit_button(
					esc_html__( 'Prune database', 'no-unsafe-inline' ),
					'secondary nunil_prune_database',
					'nunil_prune_database',
					true
				);
				wp_nonce_field( 'nunil_trigger_prune_database', 'prune_db_nonce' );
				echo '</form>';
				?>
			</div>
			<div class="nunil-tools-button-description">
				<?php
				echo ( esc_html__( 'Prune scripts from the database.', 'no-unsafe-inline' ) );
				echo '<br>';
				echo ( esc_html__( 'This will delete orphaned entries in occurences and reduce scripts numerosity in clusters.', 'no-unsafe-inline' ) );
				?>
			</div>
		</div>
	</li>
	<li class="nunil-tools-box">
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
	<li class="nunil-tools-box">
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
</ul>
<hr class='nunil-tools-hr'>

<div id="nunil-db-sum-tabs">
	<ul>
		<li><a href="#nunil-db-sum-tabs-1"><?php printf( esc_html__( 'Total DB Status', 'no-unsafe-inline' ) ); ?></a></li>
		<li><a href="#nunil-db-sum-tabs-2"><?php printf( esc_html__( 'External scripts', 'no-unsafe-inline' ) ); ?></a></li>
		<li><a href="#nunil-db-sum-tabs-3"><?php printf( esc_html__( 'Inline scripts', 'no-unsafe-inline' ) ); ?></a></li>
		<li><a href="#nunil-db-sum-tabs-4"><?php printf( esc_html__( 'Events scripts', 'no-unsafe-inline' ) ); ?></a></li>
		<li><a href="#nunil-db-sum-tabs-5"><?php printf( esc_html__( 'Operations Report', 'no-unsafe-inline' ) ); ?></a></li>
	</ul>
	<div id="nunil-db-sum-tabs-1">
		<div id="nunil_nunil_db_summary_container" class="nunil_nunil_db_summary_container">
		<?php echo No_Unsafe_Inline_Admin::output_summary_tables(); ?>
		</div>
	</div>
	<div id="nunil-db-sum-tabs-2">
		<div id="nunil_summary_external_table_container" class="nunil_summary_external_table_container">
		<?php echo No_Unsafe_Inline_Admin::output_summary_external_table(); ?>
		</div>
	</div>
	<div id="nunil-db-sum-tabs-3">
		<div id="nunil_summary_inline_table_container" class="nunil_summary_inline_table_container">
		<?php echo No_Unsafe_Inline_Admin::output_summary_inline_table(); ?>
		</div>
	</div>
	<div id="nunil-db-sum-tabs-4">
		<div id="nunil_summary_eventhandlers_table_container" class="nunil_summary_eventhandlers_table_container">
		<?php echo No_Unsafe_Inline_Admin::output_summary_eventhandlers_table(); ?>
		</div>
	</div>
	<div id="nunil-db-sum-tabs-5">
		<div id="nunil_tools_operation_report_container" class="nunil_tools_operation_report_container">
			<p class="nunil_tools_operation_report_title"><?php printf( esc_html__( 'Operations performed in this section', 'no-unsafe-inline' ) ); ?></p>
			<div id="nunil_tools_operation_report" class="nunil_tools_operation_report">
			</div>
		</div>
	</div>
</div>
