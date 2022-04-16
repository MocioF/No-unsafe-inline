module.exports = function( grunt ) {

	'use strict';

	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'no-unsafe-inline',
			},
			update_all_domains: {
				options: {
					updateDomains: true
				},
				src: [ '*.php', '**/*.php', '!\.git/**/*', '!bin/**/*', '!vendor/**/*', '!node_modules/**/*', '!tests/**/*' ]
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: [ '\.git/*', 'bin/*', 'node_modules/*', 'vendor/*', 'tests/*' ],
					mainFile: 'no-unsafe-inline.php',
					potFilename: 'no-unsafe-inline.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},
		
		uglify: {
			dist: {
				options: {
					banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.min.js <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
					report: 'gzip'
				},
				files: {
					'admin/js/no-unsafe-inline-admin.min.js' : [
						'admin/js/no-unsafe-inline-admin.js'
					],
					'public/js/no-unsafe-inline-admin.min.js' : [
						'public/js/no-unsafe-inline-admin.js'
					],
					'includes/js/no-unsafe-inline-fix-style.min.js' : [
						'includes/js/no-unsafe-inline-fix-style.js'
					],
					'includes/js/no-unsafe-inline-prefilter-override.min.js' : [
						'includes/js/no-unsafe-inline-prefilter-override.js'
					]
				}
			},
			dev: {
				options: {
					banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.js <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
					beautify: true,
					compress: false,
					mangle: false
				},
				files: {
					'admin/js/no-unsafe-inline-admin.min.js' : [
						'admin/js/no-unsafe-inline-admin.js'
					],
					'public/js/no-unsafe-inline-admin.min.js' : [
						'public/js/no-unsafe-inline-admin.js'
					],
					'includes/js/no-unsafe-inline-fix-style.min.js' : [
						'includes/js/no-unsafe-inline-fix-style.js'
					],
					'includes/js/no-unsafe-inline-prefilter-override.min.js' : [
						'includes/js/no-unsafe-inline-prefilter-override.js'
					]
				}
			}
		},

		cssmin: {
			dist:{
				files: {
					'admin/css/no-unsafe-inline-admin.min.css':'admin/css/no-unsafe-inline-admin.css',
					'public/css/no-unsafe-inline-public.min.css':'public/css/no-unsafe-inline-public.css'
				}
			}
		},

		sass: {
			dist: {
				options: {
					banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.min.css <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
					style: 'compressed'
				},
				files: [{
					expand: true,
					cwd: 'assets/scss',
					src: [
						'*.scss'
					],
					dest: 'assets/css',
					ext: '.min.css'
				}]
			},
			dev: {
				options: {
					banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.css <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
					style: 'expanded'
				},
				files: [{
					expand: true,
					cwd: 'assets/scss',
					src: [
						'*.scss'
					],
					dest: 'assets/css',
					ext: '.css'
				}]
			}
		},
		
		jshint: {
			files: {
				src: [
				'Gruntfile.js',
				'admin/**/*.js',
				'public/**/*.js',
				'includes/**/*.js'
				]
			}
		},
		
		watch: {
			scripts: {
				files: ['admin/js/*.js'],
				tasks: ['jshint','uglify:dist'],
				options: {
					spawn: false,
				},
			},
			scripts: {
				files: ['admin/css/*.css'],
				tasks: ['cssmin']
			}
		}
	} );

    grunt.loadNpmTasks( 'grunt-contrib-jshint' );
    grunt.loadNpmTasks( 'grunt-contrib-sass' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );
	grunt.registerTask( 'jshint', ['jshint'] );

	grunt.registerTask('default', [
        'jshint',
        'uglify:dev',
        'uglify:dist',
        'sass:dev',
        'sass:dist',
        'makepot',
        'wp_readme_to_markdown',
        'i18n',
        'readme'
    ]);

	grunt.util.linefeed = '\n';

};
