var gulp = require('gulp');
var lg = require('luminous-gulp');

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task('default', ['assets']);

// watch
// -----------------------------------------------------------------------------

gulp.task('watch', [lg('assets:watch')], function () {
  // Watch views and lang.
  gulp.watch(['resources/views/**/*', 'resources/lang/**/*'], lg.reload);

  // Starts a livereload server.
  lg.listen();
});

// assets
// -----------------------------------------------------------------------------

gulp.task('assets', [lg('assets')]);
