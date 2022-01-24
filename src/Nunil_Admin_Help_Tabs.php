<?php
/**
 * Admin help tabs
 *
 * Class used to add help tabs on the screen
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used to write help tabs content.
 *
 * @package No unsafe inline
 * @since   1.0.0
 */
class Nunil_Admin_Help_Tabs {

	/**
	 * @var \WP_Screen Object containing the screen in admin.
	 */
	private $screen;

	/**
	 * Class constructor
	 *
	 * Ses the screen to value passed
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct( \WP_Screen $screen ) {
		$this->screen = $screen;
	}

	/**
	 * Sets help tab based on type
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $type A string related to the tab selected in the admin page.
	 * @return void
	 */
	public function set_help_tabs( $type ): void {
		switch ( $type ) {
			case 'nunil-tools':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-overview',
						'title'   => __( 'Overview', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-overview' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-capture',
						'title'   => __( 'Start Capturing', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-capture' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-clustering',
						'title'   => __( 'Clustering', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-clustering' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-test-policy',
						'title'   => __( 'Test policy', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-test-policy' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-enable-protection',
						'title'   => __( 'Enable protection', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-enable-protection' ),
					)
				);

				break;

			case 'base-src':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-base-src',
						'title'   => __( 'Base sources for CSP', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-base-src' ),
					)
				);

				break;
		}

		$this->sidebar();
	}

	/**
	 * Sets help tab based on type
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $name A string related to single help voice.
	 * @return string
	 */
	private function content( $name ) {
		$content                         = array();
		$content['nunil-tools-overview'] = '<p>'
			. esc_html__( 'Welcome to the main page of the no-unsafe-inline plugin.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'This plugin helps you create a restrictive content security policy without having to use the \'unsafe-inline\' keyword. To achieve this result the plugin uses Machine Learning techniques that you can govern from this page.', 'no-unsafe-inline' ) . '<br>'
			. '</p>'
			. '<p><b>'
			. esc_html__( 'The steps you are supposed to do are the following.', 'no-unsafe-inline' )
			. '</b>'
			. '<ol>'
			. '<li>' . esc_html__( 'Activate the capture of the tags and use your site by visiting all the pages or making them visit from your users for a long time long period based on the use of your site (hours or days).', 'no-unsafe-inline' ) . '</li>'
			. '<li>' . esc_html__( 'Perform the data clustering in the database.', 'no-unsafe-inline' ) . '</li>'
			. '<li>' . esc_html__( 'Visit the page related to the base src rules and include in the CSP directives the desired values ​​(help you with the table at the bottom of the page).', 'no-unsafe-inline' ) . '</li>'
			. '<li>' . esc_html__( 'Visit the pages related to external scripts, inline scripts and scripts invoked by event handlers and authorize the execution of all the legitimate scripts present on the pages of your site.', 'no-unsafe-inline' ) . '</li>'
			. '<li>' . esc_html__( 'Leaving the tag capture active, activate the policy test (at this stage the plugin will generate some violations of the temporary policy used to record additional values to be included in the directives of your "content security policy").', 'no-unsafe-inline' ) . '</li>'
			. '<li>' . esc_html__( 'After visiting again your site pages, disable the capture of the tags and repeat the previous steps 2, 3 and 4.', 'no-unsafe-inline' ) . '</li>'
			. '<li>' . esc_html__( 'Enable site protection.', 'no-unsafe-inline' ) . '</li>'
			. '</ol>'
			. '</p>'
			. '<p><b>'
			. esc_html__( 'N.B. When you update plugins or themes, if something doesn\'t work properly on your site pages, temporarily deactivate the protection and repeat steps 1 to 7.', 'no-unsafe-inline' )
			. '</b></p>';

		$content['nunil-tools-capture']           = '<p>'
			. esc_html__( 'By enabling the option to capture tags, the pages of your site will be processed before being sent to the browser and the scripts and style sheets contained in them are extracted from the pages.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'During this phase, the server will have to withstand a higher than normal workload and the use of the site may be slowed down.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'Tag capture is necessary to take place over an extended period (hours or days, depending on how often your site is viewed) because some scripts are dynamically generated with time-related variables, and in order to instruct the classifier, you need to have a sufficient number of examples.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'It is also important that during this phase all the pages of the site are visited (even in the administration area, if you intend to protect this part of the site as well).', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-tools-clustering']        = '<p>'
			. esc_html__( 'Clustering is an operation used to classify the scripts extracted from your site into groups.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'This operation is done both for inline scripts and inline styles and for those contained in html tag event handlers (onclick, onmouseover, etc ...)', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'For more information on clustering you can visit these pages:', 'no-unsafe-inline' ) . '<br>'
			. sprintf(
				'%s DBSCAN - PHP-ML - Machine Learning library for PHP %s',
				'<a href="https://php-ml.readthedocs.io/en/latest/machine-learning/clustering/dbscan/" target="_blank">',
				'</a>'
			) . '<br>'
			. sprintf(
				'%s What is Clustering? &nbsp;|&nbsp; Clustering in Machine Learning &nbsp;|&nbsp; Google Developers %s',
				'<a href="https://developers.google.com/machine-learning/clustering/overview" target="_blank">',
				'</a>'
			) . '<br>'
			. '</p>'
			. '<p>'
			. sprintf(
				esc_html__( 'no-unsafe-inline uses %1$s DBSCAN %2$s as a clustering algorithm performed on local-sensitive hashes.', 'no-unsafe-inline' ),
				'<a href="https://wikipedia.org/wiki/DBSCAN" target="_blank"><b>',
				'</b></a>'
			) . '<br>'
			. sprintf(
				esc_html__( 'The hashing algorithm used is %1$snilsimsa%2$s.', 'no-unsafe-inline' ),
				'<a href="https://wikipedia.org/wiki/Nilsimsa_Hash" target="_blank"><b>',
				'</b></a>'
			) . '<br>'
			. sprintf(
				esc_html__( 'The distance measurement is %1$sHamming distance%2$s.', 'no-unsafe-inline' ),
				'<a href="https://wikipedia.org/wiki/Hamming_distance" target="_blank"><b>',
				'</b></a>'
			) . '<br>'
			. '</p>'
			. '<p><b>'
			. esc_html__( 'To cluster your data, click the "Trigger Clustering" button', 'no-unsafe-inline' )
			. '</b></p>';
		$content['nunil-tools-test-policy']       = '<p>'
			. esc_html( 'By enabling the "Content Security Policy" test you will be able to check your settings in the developer console of your browser.', 'no-unsafe-inline' )
			. '</p><p>'
			. esc_html( 'In addition, during the test, additional information is captured in the database to help refine your "Content Security Policy"', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-tools-enable-protection'] = '<p>'
			. esc_html__( 'By enabling the protection, your "Content Security Policy" will be used by WordPress in the pages of the site.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Content Security Policy" is sent via the HTTP headers and is defined according to the plugin settings (see the "Settings" tab).', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'When the protection is enabled, if you have NOT set the option to use \'unsafe-hash\', the scripts launched by the events present in the tags are transferred to a script created when the page is generated and the styles present in the html tags transferred to a inline style sheet.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'Furthermore, with the option enabled, the dynamic scripts present in the page are compared with those present in the database and if the classifier recognizes them as classifiable in an authorized cluster, they are authorized in the sent policy and inserted into the database.', 'no-unsafe-inline' )
		. '</p>';
		$content['nunil-tools-base-src']          = '<p>'

		. '</p>';

		if ( ! empty( $content[ $name ] ) ) {
			return $content[ $name ];
		}
	}

	/**
	 * Sets the help sidebar
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function sidebar(): void {
		$content  = '<p><strong>' . __( 'For more information:', 'no-unsafe-inline' ) . '</strong></p>';
		$content .= sprintf( '<p><a href="%s" target="_blank">', 'https://wordpress.org/plugins/no-unsafe-inline/' ) . __( 'Plugin page', 'no-unsafe-inline' ) . '</a></p>';
		$this->screen->set_help_sidebar( $content );
	}

}
