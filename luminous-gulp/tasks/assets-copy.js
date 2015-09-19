var gulp = require('gulp');
var path = require('path');
var del = require('del');
var es = require('event-stream');
var handleError = require('../handle-error');
var buildAssets = require('../build-assets');

var lg = require('luminous-gulp');
var ns = lg.ns;
var config = lg.config('assets');

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task(ns('assets-copy'), [ns('assets-copy:prepare')], buildAssets);

var copyFiles = function (dir, files) {
  var srcPath = path.join(config.srcPath, dir),
      tmpPath = path.join(config.tmpPath, dir),
      src = files.map(function (file) { return path.join(srcPath, file); });

  // clean up
  del.sync(tmpPath);

  // copy
  return gulp.src(src)
    .pipe(handleError())
    .pipe(gulp.dest(tmpPath));
};

// watch
// -----------------------------------------------------------------------------

gulp.task(ns('assets-copy:watch'), function () {
  var targets = config.copy;

  Object.keys(targets).forEach(function (dir) {
    if (targets[dir].watch) {
      gulp.watch(path.join(config.srcPath, dir, '/**/*'), function () {
        copyFiles(dir, targets[dir].files).on('end', buildAssets);
      });
    }
  });
});

// prepare
// -----------------------------------------------------------------------------

gulp.task(ns('assets-copy:prepare'), function (done) {
  var targets = config.copy;

  var tasks = Object.keys(targets).map(function (dir) {
    return copyFiles(dir, targets[dir].files);
  });

  es.merge(tasks).on('end', done);
});
