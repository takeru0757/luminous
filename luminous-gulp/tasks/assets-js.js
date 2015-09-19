var gulp = require('gulp');
var gulpIf = require('gulp-if');
var path = require('path');
var del = require('del');
var assign = require('lodash').assign;
var globby = require('globby');
var browserify = require('browserify');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var es = require('event-stream');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var notify = require('gulp-notify');
var handleError = require('../handle-error');
var buildAssets = require('../build-assets');

var lg = require('luminous-gulp');
var ns = lg.ns;
var config = lg.config('assets');

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task(ns('assets-js'), [ns('assets-js:prepare')], buildAssets);

// watch
// -----------------------------------------------------------------------------

gulp.task(ns('assets-js:watch'), function () {
  gulp.watch(path.join(config.srcPath, config.js.dir, '/**/*'), ns('assets-js'));
});

// prepare
// -----------------------------------------------------------------------------

gulp.task(ns('assets-js:prepare'), function (done) {
  var srcPath = path.join(config.srcPath, config.js.dir),
      tmpPath = path.join(config.tmpPath, config.js.dir),
      src = config.js.files.map(function (file) { return path.join(srcPath, file); });

  // clean up
  del.sync(tmpPath);

  // compile
  globby(src).then(function (files) {
    var tasks = files.map(function (entry) {

      var opts = assign({}, config.js.browserifyOptions, {
        entries: [entry],
        debug: true
      });

      return browserify(opts)
        .bundle().on('error', notify.onError("Error: <%= error.message %>"))
        .pipe(source(path.relative(srcPath, entry)))
        .pipe(handleError())
        .pipe(rename({extname: '.js'}))
        .pipe(buffer())
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(tmpPath));
    });

    es.merge(tasks).on('end', done);
  });
});
