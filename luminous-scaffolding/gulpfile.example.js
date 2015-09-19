var gulp = require('gulp');
var path = require('path');
var livereload = require('gulp-livereload');
//var requireDir = require('require-dir');

// -----------------------------------------------------------------------------
// Load tasks provided by the Luminous framework
// -----------------------------------------------------------------------------

// npm install ../luminous/luminous-gulp
var luminousGulp = require('luminous-gulp');

// var isChildTheme = path.basename(path.join(__dirname, '../')) !== 'luminous';
// var luminousGulpPath = isChildTheme ? '../../luminous/gulp' : '../gulp';
//
// requireDir(path.join(luminousGulpPath, 'tasks'), { recurse: true });

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
