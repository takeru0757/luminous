var gulp = require('gulp');
var path = require('path');
var livereload = require('gulp-livereload');
var luminousGulp = require('luminous-gulp');

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task('default', ['assets']);

// watch
// -----------------------------------------------------------------------------

gulp.task('watch', [luminousGulp.task('asstes:watch')], function () {
  // views, lang
  gulp.watch(['resources/views/**/*', 'resources/lang/**/*'], function () {
    livereload.reload('/');
  });

  livereload.listen();
});

// assets
// -----------------------------------------------------------------------------

gulp.task('assets', [luminousGulp.task('assets')]);
