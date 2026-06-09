const { src, dest, watch, parallel, series } = require("gulp");

const scss = require("gulp-sass");
const concat = require("gulp-concat");
const autoprefixer = require("gulp-autoprefixer");
const uglify = require("gulp-uglify");
const imagemin = require("gulp-imagemin");
const rename = require("gulp-rename");
const del = require("del");
const browserSync = require("browser-sync").create();
const svgSprite = require("gulp-svg-sprite");
const sourcemaps = require("gulp-sourcemaps");

const pugRender = require("gulp-pug");

function browsersync() {
  browserSync.init({
    server: {
      baseDir: "app/",
    },
    notify: false,
  });
}

function pug() {
  return src("app/pug/*.pug")
    .pipe(
      pugRender({
        pretty: true,
      })
    )
    .pipe(dest("app"))
    .pipe(browserSync.stream());
}

function styles() {
  return (
    src("app/scss/**/*.scss")
      .pipe(sourcemaps.init())
      .pipe(
        scss({
          outputStyle: "compressed",
        })
      )
      .pipe(
        autoprefixer({
          overrideBrowserslist: ["last 10 versions"],
          add: true,
          grid: false,
        })
      )
      .pipe(
        rename({
          suffix: ".min",
        })
      )
      // .pipe(concat('style.min.css'))
      .pipe(sourcemaps.write())
      .pipe(dest("app/css"))
      .pipe(browserSync.stream())
  );
}

function scripts() {
  return (
    src([
    //   "node_modules/jquery/dist/jquery.js",
    //   "node_modules/swiper/swiper-bundle.min.js",
      "app/js/main.js",
    ])
      .pipe(concat("main.min.js"))
      // .pipe(uglify())
      .pipe(dest("app/js"))
  );
}

function images() {
  return src("app/images/**/*.*")
    .pipe(
      imagemin([
        imagemin.gifsicle({
          interlaced: true,
        }),
        imagemin.mozjpeg({
          quality: 75,
          progressive: true,
        }),
        imagemin.optipng({
          optimizationLevel: 5,
        }),
        // imagemin.svgo({
        //     plugins: [
        //         {removeViewBox: true},
        //         {cleanupIDs: false}
        //     ]
        // })
      ])
    )
    .pipe(dest("dist/images"));
}

function build() {
  return src(
    [
      "app/*.html",
      // 'app/css/style.min.css',
      "app/css/**/*.min.css",
      "app/js/main.min.js",
    ],
    {
      base: "app",
    }
  ).pipe(dest("dist"));
}

function buildTest() {
  return src(["app/*.html", "app/css/style.min.css", "app/js/*.js"], {
    base: "app",
  }).pipe(dest("dist"));
}

function cleanDist() {
  return del("dist");
}

function watching() {
  watch(["app/scss/**/*.scss"], styles);
  watch(["app/js/**/*.js", "!app/js/main.min.js"], scripts);
  watch(["app/pug/**/*.pug"], pug);
  watch(["app/**/*.html"]).on("change", browserSync.reload);
}

exports.styles = styles;
exports.scripts = scripts;
exports.browsersync = browsersync;
exports.watching = watching;
exports.images = images;
exports.pug = pug;
exports.cleanDist = cleanDist;
// exports.sourcemaps = sourcemaps;

exports.build = series(cleanDist, images, build);

exports.buildTest = series(cleanDist, buildTest);

exports.default = parallel(pug, styles, scripts, browsersync, watching);
