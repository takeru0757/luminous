var config = module.exports;

// -----------------------------------------------------------------------------
// assets
// -----------------------------------------------------------------------------

config.assets = {
  srcPath: './resources/assets',
  tmpPath: './storage/framework/assets',
  distPath: './assets',
};

// https://github.com/smysnk/gulp-rev-all
config.assets.revAllOptions = {
  prefix: '/assets/', // Public URL
};

// https://github.com/jstuckey/gulp-gzip
config.assets.gzipOptions = {
  gzipOptions: { level: 9 }
};

// copy
// -----------------------------------------------------------------------------

config.assets.copy = {
  'font': { files: ['**/*.{eot,svg,ttf,woff,woff2}'], watch: true },
  'vendor/jquery': { files: ['dist/*.min.{js,map}'] },
  'vendor/bootstrap': { files: ['dist/js/*.min.{js,map}'] },
  'vendor/font-awesome': { files: ['fonts/*.{eot,svg,ttf,woff,woff2}'] },
  'vendor/html5shiv': { files: ['dist/*.min.{js,map}'] },
};

// css
// -----------------------------------------------------------------------------

config.assets.css = {
  dir: 'css',
  files: ['main.*'],

  // https://github.com/sindresorhus/gulp-autoprefixer
  autoprefixerOptions: {
    browsers: ['last 2 versions']
  },

  // https://github.com/murphydanger/gulp-minify-css
  minifyCssOptions: {
  },

  // https://github.com/dlmanning/gulp-sass
  sassOptions: {
  },

  // https://github.com/plus3network/gulp-less
  lessOptions: {
  }
};

// img
// -----------------------------------------------------------------------------

config.assets.img = {
  dir: 'img',
  files: ['**/*'],

  // https://github.com/sindresorhus/gulp-imagemin
  imageminOptions: {
    use: [require('imagemin-pngquant')()]
  }
};

// js
// -----------------------------------------------------------------------------

config.assets.js = {
  dir: 'js',
  files: ['main.*'],

  // https://github.com/substack/node-browserify
  browserifyOptions: {
    transform: ['coffeeify', 'babelify', 'browserify-shim', 'debowerify']
  }
};
