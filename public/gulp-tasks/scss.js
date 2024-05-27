var sass = require("gulp-sass")
var merge = require("merge-stream")
var autoprefixer = require("gulp-autoprefixer")
var rtlcss = require('gulp-rtlcss')
module.exports = (gulp, callback) => {
  const scssCustomTask = function () {
    return gulp
      .src(
        ["custom.scss"],
        { cwd: config.source.sass + "/custom/" },
        {
          read: false,
          allowEmpty: true
        }
      )
      .pipe(sass({ outputStyle: "expanded" }).on("error", sass.logError))
      .pipe(
        autoprefixer({
          browsers: config.autoprefixerBrowsers,
          cascade: false
        })
      )
      .pipe(gulp.dest(config.destination.css + "/custom/"))
  }

  const scssPagesTask = function () {
    return gulp
      .src(config.source.sass + "/pages/**/*.scss")
      .pipe(sass({ outputStyle: "expanded" }).on("error", sass.logError))
      .pipe(
        autoprefixer({
          browsers: config.autoprefixerBrowsers,
          cascade: false
        })
      )
      .pipe(gulp.dest(config.destination.css + "/pages/"))
  }

  const scssLayoutsTask = function () {
    return gulp
      .src(
        ["style-fullscreen.scss", "style-horizontal.scss", "page-center.scss"],
        { cwd: config.source.sass + "/layouts/", allowEmpty: true }
      )
      .pipe(sass({ outputStyle: "expanded" }).on("error", sass.logError))
      .pipe(
        autoprefixer({
          browsers: config.autoprefixerBrowsers,
          cascade: false
        })
      )
      .pipe(gulp.dest(config.destination.css + "/layouts/"))
  }

  const scssThemesTask = function () {
    return gulp.src(config.source.sass + "/themes/" + myThemeName + "/**/*.scss")
      .pipe(sass({ outputStyle: "expanded" }).on('error', sass.logError))
      .pipe(autoprefixer({
        browsers: config.autoprefixerBrowsers,
        cascade: false
      }))
      .pipe(gulp.dest(config.destination.css + '/themes/' + myThemeName + '/'));
  }

  const scssRtlTask = function () {
    var custom = gulp
      .src(config.source.sass + "/style-rtl.scss")
      .pipe(rtlcss())
      .pipe(sass({ outputStyle: "expanded" }).on("error", sass.logError))
      .pipe(gulp.dest(config.destination.css_rtl))    

    return custom
  }

  const scssWatchTask = function () {
    return gulp.watch(
      config.source.sass + "/**/*.scss",
      gulp.parallel(
        scssCustomTask,
        scssPagesTask,
        scssLayoutsTask,
        scssThemesTask
      )
    )
  }

  // ---------------------------------------------------------------------------
  // Exports

  return {
    custom: scssCustomTask,
    pages: scssPagesTask,
    layouts: scssLayoutsTask,
    themes: scssThemesTask,
    rtl: scssRtlTask,
    watch: scssWatchTask
  }
}
