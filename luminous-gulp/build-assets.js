var gulp = require('gulp');
var gulpFilter = require('gulp-filter');
var path = require('path');
var del = require('del');
var RevAll = require('gulp-rev-all');
var gzip = require('gulp-gzip');
var livereload = require('gulp-livereload');
var notify = require('gulp-notify');
var handleError = require('./handle-error');

var lg = require('luminous-gulp');
var config = lg.config('assets');

module.exports = function () {
  var revAll = new RevAll(config.revAllOptions),
      gzipFilter = gulpFilter('**/*.{css,js}', { restore: true });

  // clean up
  del.sync(path.join(config.distPath, '/**/*'));

  // build
  return gulp.src(path.join(config.tmpPath, '/**/*'))
    .pipe(handleError())
    .pipe(revAll.revision())
    .pipe(gulp.dest(config.distPath))
    .pipe(gzipFilter)
    .pipe(gzip(config.gzipOptions))
    .pipe(gulp.dest(config.distPath))
    .pipe(gzipFilter.restore)
    .pipe(livereload())
    .pipe(revAll.manifestFile())
    .pipe(gulp.dest(config.distPath))
    .pipe(notify({ message: "Build Assets Successful" }));
};
