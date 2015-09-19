var gulp = require('gulp');
var path = require('path');
var del = require('del');
var imagemin = require('gulp-imagemin');
var handleError = require('../handle-error');
var buildAssets = require('../build-assets');

var lg = require('luminous-gulp');
var ns = lg.ns;
var config = lg.config('assets');

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task(ns('assets-img'), [ns('assets-img:prepare')], buildAssets);

// watch
// -----------------------------------------------------------------------------

gulp.task(ns('assets-img:watch'), function () {
  gulp.watch(path.join(config.srcPath, config.img.dir, '/**/*'), ns('assets-img'));
});

// prepare
// -----------------------------------------------------------------------------

gulp.task(ns('assets-img:prepare'), function () {
  var srcPath = path.join(config.srcPath, config.img.dir),
      tmpPath = path.join(config.tmpPath, config.img.dir),
      src = config.img.files.map(function (file) { return path.join(srcPath, file); });

  // clean up
  del.sync(tmpPath);

  // compile
  return gulp.src(src)
    .pipe(handleError())
    .pipe(imagemin(config.img.imageminOptions))
    .pipe(gulp.dest(tmpPath));
});
