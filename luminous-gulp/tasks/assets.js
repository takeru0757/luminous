var gulp = require('gulp');
var buildAssets = require('../build-assets');

var lg = require('luminous-gulp');
var ns = lg.ns;

// -----------------------------------------------------------------------------
// Tasks
// -----------------------------------------------------------------------------

gulp.task(ns('assets'), [
  ns('assets-copy:prepare'),
  ns('assets-css:prepare'),
  ns('assets-img:prepare'),
  ns('assets-js:prepare')
], buildAssets);

// watch
// -----------------------------------------------------------------------------

gulp.task(ns('assets:watch'), [
  ns('assets-copy:watch'),
  ns('assets-css:watch'),
  ns('assets-img:watch'),
  ns('assets-js:watch')
]);
