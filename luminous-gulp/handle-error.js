var plumber = require('gulp-plumber');
var notify = require('gulp-notify');

module.exports = function () {
  return plumber({
    errorHandler: notify.onError("Error: <%= error.message %>")
  });
};
