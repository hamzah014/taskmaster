var fs = require("fs")

module.exports = (gulp, callback) => {
  const fileWriteTask = function (callback) {
    return fs.writeFile(
      config.source.template + "/pages/template.pug",
      "extends ../templates/" + myLayout,
      callback
    )
  }

  const fileSkWriteTask = function (callback) {
    return fs.writeFile(
      config.source.template + "/pages/starter-kit/template.pug",
      "extends ../../templates/starter-kit/" + myLayout,
      callback
    )
  }

  const sassVariableWriteTask = function (callback) {
    return fs.writeFile(
      "src/sass/pages/variables.scss",

      '@import "../components/color-variables"; \n@import "../themes/' +
      myThemeName +
      '/variables"; \n@import "../components/variables"; \n@import "../theme-components/theme-variables"; \n@import "../themes/' +
      myThemeName +
      '/theme-variables";',

      callback
    );
  }

  // ---------------------------------------------------------------------------
  // Exports

  return {
    file_write: fileWriteTask,
    sk_file_write: fileSkWriteTask,
    sass_variable_write: sassVariableWriteTask,
  }
}
