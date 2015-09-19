var gulp = require('gulp');
var gulpIf = require('gulp-if');
var path = require('path');
var del = require('del');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
var minifyCss = require('gulp-minify-css');
var sass = require('gulp-sass');
var less = require('gulp-less');
var handleError = require('../handle-error');
var buildAssets = require('../build-assets');

var lg = require('luminous-gulp');
var ns = lg.ns;
var config = lg.config('assets');

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task(ns('assets-css'), [ns('assets-css:prepare')], buildAssets);

// watch
// -----------------------------------------------------------------------------

gulp.task(ns('assets-css:watch'), function () {
  gulp.watch(path.join(config.srcPath, config.css.dir, '/**/*'), ns('assets-css'));
});

// prepare
// -----------------------------------------------------------------------------

gulp.task(ns('assets-css:prepare'), function () {
  var srcPath = path.join(config.srcPath, config.css.dir),
      tmpPath = path.join(config.tmpPath, config.css.dir),
      src = config.css.files.map(function (file) { return path.join(srcPath, file); });

  // clean up
  del.sync(tmpPath);

  // compile
  return gulp.src(src)
    .pipe(handleError())
    .pipe(sourcemaps.init())
    .pipe(gulpIf(function (f) { return /\.scss$/i.test(f.path); }, sass(config.css.sassOptions)))
    .pipe(gulpIf(function (f) { return /\.less$/i.test(f.path); }, less(config.css.lessOptions)))
    .pipe(autoprefixer(config.css.autoprefixerOptions))
    .pipe(minifyCss(config.css.minifyCssOptions))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(tmpPath));
});
