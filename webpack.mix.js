const mix = require('laravel-mix')
const s3Plugin = require('webpack-s3-plugin')

// vars
const enableBrowserSync = false

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

let webpackPlugins = [];
if (mix.inProduction() && process.env.UPLOAD_S3) {
  webpackPlugins = [
    new s3Plugin({
      include: /.*\.(css|js)$/,
      s3Options: {
        accessKeyId: process.env.AWS_KEY,
        secretAccessKey: process.env.AWS_SECRET,
        region: process.env.AWS_DEFAULT_REGION,
      },
      s3UploadOptions: {
        Bucket: process.env.ASSETS_S3_BUCKET,
        CacheControl: 'public, max-age=31536000'
      },
      basePath: 'app',
      directory: 'public'
    })
  ]
}

if (enableBrowserSync) {
  let url = process.env.APP_URL

  url = url.replace('http://', '')
  url = url.replace('https://', '')

  mix.browserSync(url)
}

mix.js('resources/js/app.js', 'public/js')
  .sass('resources/sass/app.scss', 'public/css')

mix.webpackConfig({
  plugins: webpackPlugins
})

mix.vue({ version: 2 })
mix.options({
  terser: { extractComments: false } // Stop Mix from generating license file
})

if (mix.inProduction()) {
  mix.version()
} else {
  mix.sourceMaps()
}
