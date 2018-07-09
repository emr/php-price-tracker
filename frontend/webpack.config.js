const Encore = require('@symfony/webpack-encore');

Encore
  .setOutputPath('../public/build/')
  .setPublicPath('/build')
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .addEntry('app', './src/index.js')
  .enableSassLoader()
  .enableReactPreset()
;

module.exports = Encore.getWebpackConfig();
