<?php
/**
 * Admin help tabs.
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
	 * Object containing the screen in admin
	 *
	 * @var \WP_Screen
	 */
	private $screen;

	/**
	 * Class constructor
	 *
	 * Ses the screen to value passed
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \WP_Screen $screen The current screen object.
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
						'title'   => __( 'Enable tag capture', 'no-unsafe-inline' ),
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
						'title'   => __( 'Test current csp policy', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-test-policy' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-enable-protection',
						'title'   => __( 'Enable csp protection', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-enable-protection' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-tools-clean-database',
						'title'   => __( 'Clean database', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-tools-clean-database' ),
					)
				);

				break;

			case 'base-src':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-base-src',
						'title'   => __( 'Base sources for CSP', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-base-src' ),
					)
				);

				break;

			case 'external':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-external-main',
						'title'   => __( 'External scripts and styles', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-external-main' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-external-whitelist',
						'title'   => __( 'Whitelist/Blacklist', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-external-whitelist' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-external-hash',
						'title'   => __( 'Hash/Rehash', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-external-hash' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-external-delete',
						'title'   => __( 'Delete', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-external-delete' ),
					)
				);

				break;

			case 'inline':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-inline-main',
						'title'   => __( 'Inline scripts and internal and inline styles', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-inline-main' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-inline-uncluster',
						'title'   => __( 'Uncluster', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-inline-uncluster' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-inline-whitelist',
						'title'   => __( 'Whitelist/Blacklist', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-inline-whitelist' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-inline-delete',
						'title'   => __( 'Delete', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-inline-delete' ),
					)
				);

				break;

			case 'events':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-events-main',
						'title'   => __( 'Event hanlers', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-events-main' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-events-uncluster',
						'title'   => __( 'Uncluster', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-events-uncluster' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-events-whitelist',
						'title'   => __( 'Whitelist/Blacklist', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-events-whitelist' ),
					)
				);
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-events-delete',
						'title'   => __( 'Delete', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-events-delete' ),
					)
				);

				break;

			case 'settings':
				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-settings-main',
						'title'   => __( 'Plugin options', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-settings-main' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-fetch-directives',
						'title'   => __( 'Fetch directives managed', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-fetch-directives' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-external-sources',
						'title'   => __( 'External source identification', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-external-sources' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-use-sri',
						'title'   => __( 'Use Subresource Integrity', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-use-sri' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-inline-script-mode',
						'title'   => __( 'Inline script mode', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-inline-script-mode' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-upgrade-insecure',
						'title'   => __( 'Set upgrade-insecure-requests directive', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-upgrade-insecure' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-csp-admin',
						'title'   => __( 'Enforce policy in admin', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-csp-admin' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-use-unsafe-hashes',
						'title'   => __( 'Use \'unsafe-hashes\' for JS', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-use-unsafe-hashes' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-fix-setattribute',
						'title'   => __( 'Fix the use of setAttribute(\'style\')', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-fix-setattribute' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'nunil-server-log',
						'title'   => __( 'Enable Server Logs', 'no-unsafe-inline' ),
						'content' => $this->content( 'nunil-server-log' ),
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
	 * @return string|null
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

		$content['nunil-tools-capture']    = '<p>'
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
		$content['nunil-tools-clustering'] = '<p>'
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
				// translators: %1$s is the link opening tag.
				// translators: %2$s is the link closing tag.
				esc_html__( 'no-unsafe-inline uses %1$s DBSCAN %2$s as a clustering algorithm performed on local-sensitive hashes.', 'no-unsafe-inline' ),
				'<a href="https://wikipedia.org/wiki/DBSCAN" target="_blank"><b>',
				'</b></a>'
			) . '<br>'
			. sprintf(
				// translators: %1$s is the link opening tag.
				// translators: %2$s is the link closing tag.
				esc_html__( 'The hashing algorithm used is %1$snilsimsa%2$s.', 'no-unsafe-inline' ),
				'<a href="https://wikipedia.org/wiki/Nilsimsa_Hash" target="_blank"><b>',
				'</b></a>'
			) . '<br>'
			. sprintf(
				// translators: %1$s is the link opening tag.
				// translators: %2$s is the link closing tag.
				esc_html__( 'The distance measurement is %1$sHamming distance%2$s.', 'no-unsafe-inline' ),
				'<a href="https://wikipedia.org/wiki/Hamming_distance" target="_blank"><b>',
				'</b></a>'
			) . '<br>'
			. '</p>'
			. '<p><b>'
			. esc_html__( 'To cluster your data, click the "Trigger Clustering" button', 'no-unsafe-inline' )
			. '</b></p>'
			. '<p><b>'
			. esc_html__( 'When clustering, all scripts and styles placed in a group containing a whitelisted script or style will automatically be marked as whitelisted.', 'no-unsafe-inline' )
			. '</b></p>'
			. '<p><b>'
			. esc_html__( 'The clustering procedure can consume a lot of computational resources on your server and can take several minutes.', 'no-unsafe-inline' )
			. '</b></p>';
		$content['nunil-tools-test-policy']       = '<p>'
			. esc_html__( 'By enabling the "Content Security Policy" test you will be able to check your settings in the developer console of your browser.', 'no-unsafe-inline' )
			. '</p><p>'
			. esc_html__( 'In addition, during the test, additional information is captured in the database to help refine your "Content Security Policy"', 'no-unsafe-inline' )
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
		$content['nunil-tools-clean-database']    = '<p>'
			. esc_html__( 'If you have updated numerous plugins, changed themes, or upgraded your WordPress version, you may want to delete all data in the database and restart the capture process.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The procedure will not remove your options, nor will it delete the origins and keywords entered in the "Base -src" tab.', 'no-unsafe-inline' )
		. '</p>';
		$content['nunil-base-src']                = '<p>'
			. esc_html__( 'From this page you can add <hotst-source>, <scheme-source> and keywords that will be used in the CSP directives in all protected pages of your site.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'Adding sources or keywords here is equivalent to inserting a CSP policy in the .htacces file or in the configuration file of your web server.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'For each CSP directive there is a link to the "Content Security Policy Level 3" specification section which I invite you to consult if in doubt.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'In the table at the bottom of the page you will find some suggested sources which, if included in your CSP policy, would authorize the execution of external scripts captured while viewing the pages of your site; selecting the records of the table, these sources will be respectively inserted and removed from the corresponding directives.', 'no-unsafe-inline' )
			. esc_html__( 'The format of the sources is determined by the "External hosts identification" option which, however, does not limit the ability to manually insert elements in directives not listed in the table.', 'no-unsafe-inline' )
			. '<p>'
			. esc_html__( 'Avoid adding unsafe directives like \'unsafe-hashes\' and \'unsafe-inline\.', 'no-unsafe-inline' )
			. esc_html__( 'If some scripts on your site use the eval () function you will be prompted to insert \'unsafe-eval\' in the table. This is not safe, but in order to get rid of this keyword without impacting the functionality of the site it is necessary to rewrite the code that uses this function.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'Do not use the \'strict-dynamic\' and \'report-sample\' keywords which are directly managed by the plugin.', 'no-unsafe-inline' )
			. esc_html__( 'Do not enter hashes for inline scripts and styles, which are managed directly by the plugin.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'We cannot detect any needed source for all active directives, so if you have known sources you need (for worker-src connect-src frame-ancestors etc..) please add them here manually.', 'no-unsafe-inline' )
			. '</p>'
			. '<p><b>'
			. esc_html__( 'Remember to save your settings before exiting this tab.', 'no-unsafe-inline' )
			. '</b></p>';
		$content['nunil-external-main']           = '<p>'
			. esc_html__( 'In the table on this page you can view all the scripts and styles inserted in your site as files external to the html page.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The table shows the CSP directive used to authorize the execution of the script or the insertion of the style, the html tag used for this purpose in your pages, the URI of the resource (including the query) with which it is inserted in yours pages, if and which hashes sha have been calculated on the resource and if the resource is included in the whitelist of those authorized by you or not.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-external-whitelist']      = '<p>'
			. esc_html__( 'To authorize external scripts (or styles) you must explicitly whitelist them (using the link present in each line in the corresponding column, or selecting the scripts and using the selection and the massive operations button).', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( ' Scripts and styles already whitelisted can be excluded by using the corresponding link or massive action in the same way.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'If the options for using SRI are set, only the hashes of authorized scripts and styles will be dynamically inserted in your CSP policy.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'If the browser supports CSP3, the presence of the hashes will cause the browser to ignore the <host-source> entered in base-src (necessary for backwards compatibility with browsers that do not support CSP3).', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-external-hash']           = '<p>'
			. esc_html__( 'Content Security Policy Level 3 requires browsers to use Subresource Integrity (SRI) for external styles and scripts.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'From CSP3 Specs:', 'no-unsafe-inline' )
			. '</p>'
			. '<p><i>'
			. esc_html__( 'In [CSP2], hash source expressions could only match inlined script, but now that Subresource Integrity [SRI] is widely deployed, we can expand the scope to enable externalized JavaScript as well.', 'no-unsafe-inline' )
			. '</i></p>'
			. '<p><i>'
			. esc_html__( 'If multiple sets of integrity metadata are specified for a script, the request will match a policy’s hash-sources if and only if each item in a script\'s integrity metadata matches the policy.', 'no-unsafe-inline' )
			. '</i></p>'
			. '</p>'
			. esc_html__( 'When a script or external style is found and inserted into the database, the sha hashes for this resource are calculated.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'In case the resource has been modified you can perform the hash calculation again.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'If the hashes could not be calculated at the time the resource was discovered, you can use the link or massive action to calculate them.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-external-delete']         = '<p>'
			. esc_html__( 'You can delete an external resource from your database using the link in each row of the "Resource" column or the commands for bulk action.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-inline-main']             = '<p>'
			. esc_html__( 'In the table on this page, you can view all the inline scripts, internal styles and inline styles present in your site\'s pages.', 'no-unsafe-inline' )
			. '</p>'
			. '<p><ul>'
			. '<li>'
			. esc_html__( 'Inline styles are applied directly to an element in your HTML code. They use the style attribute, followed by regular CSS properties.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'For example:', 'no-unsafe-inline' ) . '<br>'
			. '<code>'
			. htmlentities( '<h1 style="color:red;margin-left:20px;">Today\'s Update</h1>' )
			. '</code>'
			. '</li>'
			. '<li>'
			. esc_html__( 'Rather than linking an external .css file, HTML files with an internal stylesheet include a set of rules in their head section. CSS rules are wrapped in <style> tags, like this:', 'no-unsafe-inline' ) . '<br>'
			. '<code>'
			. htmlentities( '<style type="text/css">' ) . '<br>'
			. htmlentities( 'h1 {' ) . '<br>'
			. htmlentities( '	color:#fff' ) . '<br>'
			. htmlentities( '	margin-left: 20px;' ) . '<br>'
			. htmlentities( '}' ) . '<br>'
			. htmlentities( '<style' )
			. '</code>'
			. '</li>'
			. '</ul></p>'
			. '<p>'
			. esc_html__( 'In the "Script" column, you can see the script or style detected in the pages of your site.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'By examining its contents, you can make certain it is a legitimate script or style and not code injected by an attacker when the capture option is on.', 'no-unsafe-inline' ) . '</strong>'
			. '</p>'
			. '<p>'
			. esc_html__( 'Inline styles are authorized by injecting in your pages an internal stylesheet created on the fly by the plugin and that only includes inline styles whitelisted through this tab.', 'no-unsafe-inline' ) . '</strong>'
			. '</p>'
			. '<p>'
			. esc_html__( 'The table shows the CSP directive used to authorize the execution of the script or the insertion of the style and the HTML tag used to insert the inline stuff.', 'no-unsafe-inline' )
			. '</p>'
			. '<p><strong>'
			. esc_html__( 'Clusters:', 'no-unsafe-inline' )
			. '</strong></br>'
			. esc_html__( 'The name of the cluster to which the script is assigned is displayed in the "Cluster" column.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'This name is generated randomly when the database data is processed by the clustering procedure. The name is not important, but if the column shows \'Unclustered\' it means that the script or style has not been grouped with others like it.', 'no-unsafe-inline' ) . '<br><b>'
			. esc_html__( 'We aim to cluster together scripts generated by the same backend code.', 'no-unsafe-inline' ) . '</b><br>'
			. esc_html__( 'The "Cl.\'s Numerosity" column indicates the number of elements grouped in the cluster (if the element does not belong to any cluster, this number is 1).', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Whitelist" column indicates whether the execution of the script or cluster code is authorized ("WL") or not ("BL").', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Pages" column contains a list of the pages where the code (or codes in the cluster) was seen. The list is visible by opening the select drop-down.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Last seen" column shows the date and time of the last time the code (or one of the codes in the cluster) was detected on the pages of your site.', 'no-unsafe-inline' )
			. esc_html__( 'This information is useful for identifying obsolete code no longer present on your site (or never legitimately present).', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-inline-uncluster']        = '<p>'
			. esc_html__( 'The "Uncluster" link under the cluster name (if any) in the "Cluster" column allows you to make ungrouped the codes that have been grouped by removing the cluster from the database.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'Clusters can only be produced by the clustering procedure executable from the tools tab.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-inline-whitelist']        = '<p>'
			. esc_html__( 'To authorize inline scripts or internal styles you must explicitly whitelist them (using the link present in each line in the corresponding column, or selecting the scripts and styles and using the selection and the bulk operations button).', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'Scripts and styles already whitelisted can be excluded by using the corresponding link or massive action in the same way.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-inline-delete']           = '<p>'
			. esc_html__( 'You can delete an inline script or internal style (or scripts and styles clustered together) from your database using the link in each row of the "Script" column or the commands for bulk action.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-events-main']             = '<p>'
			. esc_html__( 'Event handlers are scripts that are automatically executed when an event occurs.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'Event handlers are embedded in documents as attributes of HTML tags to which you assign JavaScript code to execute.', 'no-unsafe-inline' )
			. esc_html__( 'In the "Script" column, you can see the script executed when the event occurs.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'By examining its contents, you can make certain it is a legitimate script and not code injected by an attacker when the capture option is on.', 'no-unsafe-inline' ) . '</strong>'
			. '</p>'
			. '<p><strong>'
			. esc_html__( 'Event handler scripts can be authorized either by using the \'unsafe-hashes\' directive (not recommended, which authorizes all event handler scripts present, significantly reducing the level of protection offered) or by injecting in your pages an inline script created on the fly by the plugin and that only includes scripts whitelisted through this tab.', 'no-unsafe-inline' ) . '</strong>'
			. '</p>'
			. '<p>'
			. esc_html__( 'The authorization mode of the event handler scripts can be chosen by selecting the appropriate option.', 'no-unsafe-inline' )
			. '<p>'
			. '</p>'
			. '<p>'
			. esc_html__( 'The table shows the HTML tag used to insert the inline stuff.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'Also, the table shows the \'id\' attribute of the HTML tag (if any).', 'no-unsafe-inline' )
			. '</p>'
			. esc_html__( 'In the "Event" column is there the event tag attribute of the HTML tag.', 'no-unsafe-inline' )
			. '<p><strong>'
			. esc_html__( 'Clusters:', 'no-unsafe-inline' )
			. '</strong></br>'
			. esc_html__( 'The name of the cluster to which the script is assigned is displayed in the "Cluster" column.', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'This name is generated randomly when the database data is processed by the clustering procedure. The name is not important, but if the column shows \'Unclustered\' it means the script has not been grouped with other similar..', 'no-unsafe-inline' ) . '<br><b>'
			. esc_html__( 'We aim to cluster together scripts generated by the same backend code.', 'no-unsafe-inline' ) . '</b><br>'
			. esc_html__( 'The "Cl.\'s Numerosity" column indicates the number of scripts grouped in the cluster (if the script does not belong to any cluster, this number is 1).', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Whitelist" column indicates whether the execution of the script or cluster code is authorized ("WL") or not ("BL").', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Pages" column contains a list of the pages where the code (or codes in the cluster) was seen. The list is visible by opening the select drop-down.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'The "Last seen" column shows the date and time of the last time the code (or one of the codes in the cluster) was detected on the pages of your site.', 'no-unsafe-inline' )
			. esc_html__( 'This information is useful for identifying obsolete code no longer present on your site (or never legitimately present).', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-events-uncluster']        = '<p>'
			. esc_html__( 'The "Uncluster" link under the cluster name (if any) in the "Cluster" column allows you to make ungrouped the scripts that have been grouped by removing the cluster from the database.', 'no-unsafe-inline' )
			. '</p>'
			. '<p>'
			. esc_html__( 'Clusters can only be produced by the clustering procedure executable from the tools tab.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-events-whitelist']        = '<p>'
			. esc_html__( 'To authorize inline event handlers scripts you must explicitly whitelist them (using the link present in each line in the corresponding column, or selecting the scripts and using the selection and the bulk operations button).', 'no-unsafe-inline' ) . '<br>'
			. esc_html__( 'Script already whitelisted can be excluded by using the corresponding link or massive action in the same way.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-events-delete']           = '<p>'
			. esc_html__( 'You can delete an event handler script (or scripts clustered together) from your database using the link in each row of the "Script" column or the commands for bulk action.', 'no-unsafe-inline' )
			. '</p>';
		$content['nunil-settings-main']           = '<p>'
			. '</p>';
		$content['nunil-fetch-directives']        = '<p>'
			. '</p>';
		$content['nunil-external-sources']        = '<p>'
			. '</p>';
		$content['nunil-use-sri']                 = '<p>'
			. '</p>';
		$content['nunil-inline-script-mode']      = '<p>'
			. '</p>';
		$content['nunil-upgrade-insecure']        = '<p>'
			. '</p>';
		$content['nunil-csp-admin']               = '<p>'
			. '</p>';
		$content['nunil-use-unsafe-hashes']       = '<p>'
			. '</p>';
		$content['nunil-fix-setattribute']        = '<p>'
			. '</p>';
		$content['nunil-server-log']              = '<p>'
			. '</p>';

		if ( ! empty( $content[ $name ] ) ) {
			return $content[ $name ];
		} else {
			return null;
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
