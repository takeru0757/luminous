var config = module.exports = {};

// -----------------------------------------------------------------------------
// assets
// -----------------------------------------------------------------------------

config.assets = {};

// copy
// -----------------------------------------------------------------------------

config.assets.copy = {
  'vendor/bootstrap': { files: ['dist/js/*.min.{js,map}'] },
  'vendor/font-awesome': { files: ['fonts/*.{eot,svg,ttf,woff,woff2}'] },
  'vendor/html5shiv': { files: ['dist/*.min.{js,map}'] },
  'vendor/jquery': { files: ['dist/*.min.{js,map}'] }
};

// css
// -----------------------------------------------------------------------------

config.assets.css = {
  // https://www.npmjs.com/package/gulp-autoprefixer
  autoprefixerOptions: {
    browsers: ['last 2 versions']
  },

  // https://www.npmjs.com/package/gulp-minify-css
  minifyCssOptions: {
  },

  // https://www.npmjs.com/package/gulp-sass
  sassOptions: {
  },

  // https://www.npmjs.com/package/gulp-less
  lessOptions: {
  }
};

// img
// -----------------------------------------------------------------------------

config.assets.img = {
  // https://www.npmjs.com/package/gulp-imagemin
  imageminOptions: {
    use: [require('imagemin-pngquant')()]
  }
};

// js
// -----------------------------------------------------------------------------

config.assets.js = {
  // https://www.npmjs.com/package/browserify
  browserifyOptions: {
    transform: ['coffeeify', 'babelify', 'browserify-shim', 'debowerify'],
    extensions: ['.coffee']
  },

  // https://www.npmjs.com/package/gulp-uglify
  uglifyOptions: {
  }
};
