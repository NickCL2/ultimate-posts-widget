var gulp = require('gulp');
var sass = require('gulp-ruby-sass');
var prefix = require('gulp-autoprefixer');
var minify = require('gulp-minify-css');
var rename = require('gulp-rename');
var util = require('gulp-util');
var size = require('gulp-size');
var uglify = require('gulp-uglifyjs');

gulp.task('styles', function () {
  return gulp.src([
      'scss/*.scss'
    ])
    .pipe(sass({
      precision: 10
    }))
    .pipe(prefix('last 2 versions', 'ie 8'))
    .pipe(minify({ keepSpecialComments: 1 }))
    .pipe(size({
      showFiles: true,
      gzip: true,
      title: 'Styles'
    }))
    .pipe(rename({suffix: '.min'}))
    .pipe(gulp.dest('css'));
});

gulp.task('scripts', function() {

  return gulp.src([
      'js/*.js',
      '!js/*.min.js'
    ])
    .pipe(uglify())
    .pipe(size({
      gzip: true,
      title: 'Scripts'
    }))
    .pipe(rename({suffix: '.min'}))
    .pipe(gulp.dest('js'));

});

gulp.task('watch', function () {
  gulp.watch('scss/**/*.scss', ['styles']);
  gulp.watch('js/**/*.js', ['scripts']);
});

gulp.task('default', ['styles', 'scripts']);
