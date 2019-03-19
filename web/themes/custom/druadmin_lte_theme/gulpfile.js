'use strict';
 
var gulp = require('gulp'), 
    sass = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    prefixer = require('gulp-autoprefixer'),
    imagemin = require('gulp-imagemin'),
    scsslint = require('gulp-scss-lint'),
    csscomb = require('gulp-csscomb'),
    jshint = require('gulp-jshint'),
    prettify = require('gulp-jsbeautifier'),
    uglifycss = require('gulp-uglifycss'),
    uglify = require('gulp-uglify'),
    livereload = require('gulp-livereload'),
    browserSync = require('browser-sync').create();

// Variables for folder path, you can change it as per your requirement
var sass_url = "sass/",
    images_url = "images/",
    js_url = "js/",
    css_url = "stylesheets/",
    optimize_css_url = "stylesheets/dist",
    optimize_js_url = "js/dist";

// SASS to CSS compilation, Sourcemap, CSS Auto prefixer
gulp.task('sass', function () {
  return gulp.src(sass_url+"**/*.scss")
    // .pipe(sourcemaps.init()) 
    /*.pipe(sass().on('error', sass.logError))*/
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    // check out the prefixer available option here: https://github.com/postcss/autoprefixer#options
    .pipe(prefixer( 'last 2 version', 'safari 5', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4' ))
    // .pipe(sourcemaps.write('css-maps'))
    .pipe(gulp.dest('stylesheets'))
    // Check out the livereload available options here: https://scotch.io/tutorials/a-quick-guide-to-using-livereload-with-gulp
    // .pipe(livereload());
    .pipe(browserSync.reload({
      stream:true
    }));
});


gulp.task('sass-watch', function () {
  // livereload.listen()
  // livereload.reload()
  gulp.watch(sass_url+"**/*.scss", ['sass']);
});

// BrowserSync task
gulp.task('browserSync', function() {
  browserSync.init({
    proxy: "192.168.6.219:8888/drupal7/" // Here you need to add your local machine ip & project path
  });
  gulp.watch(sass_url+"**/*.scss", ['sass']);
});

// Image optimization task
gulp.task('images', function() {
  gulp.src(images_url+"**/*.+(png|jpg|gif|svg)")
  // Check out the imagemin available options here: https://github.com/sindresorhus/gulp-imagemin
  .pipe(imagemin())
  .pipe(gulp.dest(images_url));
});

// SCSS Lint for SCSS validation
// You also need to install scss lint for that use command "gem install scss_lint"
gulp.task('scss-lint', function() {
  return gulp.src(sass_url+"**/*.scss")
    .pipe(scsslint());
});

// SCSS formatter
// Download csscomb.json which is already sorted as per standard and replace it in your node_modules/csscomb/config
gulp.task('stylescomb', function() {
  return gulp.src(sass_url+"**/*.scss")
    // Check out the csscomb available options here: https://github.com/csscomb/sublime-csscomb/blob/master/node_modules/csscomb/doc/options.md
    .pipe(csscomb())
    .pipe(gulp.dest(sass_url));
});

// JS Hint for JS validation
gulp.task('js-hint', function() {
  return gulp.src(js_url+"*.js")
    .pipe(jshint())
    // You need to install: npm install --save-dev jshint-stylish
    .pipe(jshint.reporter('jshint-stylish'));
});

// JS Formatter
// Download defaults.json which is already sorted as per standard and replace it in your node_modules/js-beautify/js/config/
gulp.task('prettify', function() {
  gulp.src([js_url+"*.js"])
    // Check out the JSPrettify available options here: https://github.com/beautify-web/js-beautify
    .pipe(prettify({
      js: {
        indent_size: 2
      }
    }))
    .pipe(gulp.dest('js'));
});

// CSS minification
gulp.task('uglifycss', function () {
  gulp.src(css_url+"style.css")
    // Check out the uglifycss available options here: https://github.com/fmarcia/UglifyCSS
    .pipe(uglifycss({
      "maxLineLen": 80,
      "uglyComments": true
    }))
    .pipe(gulp.dest(optimize_css_url));
});

// JS minification
gulp.task('compress-js', function (cb) {
  pump([
        gulp.src(js_url+"*.js"),
        // Check out the uglifyjs available options here: https://www.npmjs.com/package/gulp-uglify
        uglify(),
        gulp.dest(optimize_js_url)
    ],
    cb
  );
});

