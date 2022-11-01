const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');

gulp.task ('sass', async function () {
  gulp.src('./web/sites/all/themes/contrib/emergya/sass/*.sass')
  .pipe(sourcemaps.init())
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./web/sites/all/themes/contrib/emergya/css'));
});

gulp.task('watch', async function(){
  gulp.watch('./web/sites/all/themes/contrib/emergya/sass/*.sass', gulp.series('sass'));
});