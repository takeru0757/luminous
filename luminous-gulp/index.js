var path = require('path');
var requireDir = require('require-dir');

var ns = 'luminous';
var config = require(path.join(process.cwd(), 'lg-config.js'));
var loaded = false;

var LuminousGulp = function () {
};

/**
 * Get the configuration
 *
 * @param {string} key
 * @return {Object}
 */
LuminousGulp.config = function (key) {
  return key ? config[key] : config;
};

/**
 * Get the task name with the namespace
 *
 * @param {string} name
 * @return {string}
 */
LuminousGulp.ns = function (name) {
  return [ns, name].join(':');
};

/**
 * Get the task name with the namespace after loading all tasks
 *
 * @param {string} name
 * @return {string}
 */
LuminousGulp.task = function (name) {
  if (!loaded) {
    requireDir('./tasks', { recurse: true })
    loaded = true;
  }

  return LuminousGulp.ns(name);
};

module.exports = LuminousGulp;
