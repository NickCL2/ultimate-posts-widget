var gulp = require('gulp');
var sass = require('gulp-ruby-sass');
var prefix = require('gulp-autoprefixer');
var minify = require('gulp-minify-css');
var rename = require('gulp-rename');
var util = require('gulp-util');
var size = require('gulp-size');

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

gulp.task('watch', function () {
  gulp.watch('scss/**/*.scss', ['styles']);
});

gulp.task('default', ['styles']);
