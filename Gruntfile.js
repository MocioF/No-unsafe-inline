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
					banner: '/*! <%= pkg.name %> <%= pkg.version %> */\n',
					report: 'gzip'
				},
				files: [{
					expand: true,
					src: ['includes/js/*.js', '!includes/js/*.min.js', 'admin/js/*.js', '!admin/js/*.min.js'],
					dest: '.',
					cwd: '.',
					rename: function (dst, src) {
						// To keep the source js files and make new files as `*.min.js`:
						return dst + '/' + src.replace('.js', '.min.js');
						// Or to override to src:
						// return src;
					}
				}]
			},
			dev: {
				options: {
					banner: '/*! <%= pkg.name %> <%= pkg.version %> */\n',
					beautify: true,
					compress: false,
					mangle: false
				},
				files: [{
					expand: true,
					src: ['includes/js/*.js', '!includes/js/*.min.js', 'admin/js/*.js', '!admin/js/*.min.js'],
					dest: '.',
					cwd: '.',
					rename: function (dst, src) {
						// To keep the source js files and make new files as `*.min.js`:
						return dst + '/' + src.replace('.js', '.min.js');
						// Or to override to src:
						// return src;
					}
				}]
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
					banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.css <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
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
				files: ['admin/js/*.js', 'includes/js/*.js'],
				tasks: ['uglify:dist'],
				options: {
					spawn: false,
				},
			},
			stylesheets: {
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
