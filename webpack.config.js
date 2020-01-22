const path = require('path');
const webpack = require('webpack');
const minimatch = require('minimatch');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const StyleLintPlugin = require('stylelint-webpack-plugin');
const FriendlyErrorsPlugin = require('friendly-errors-webpack-plugin');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const TerserPlugin = require('terser-webpack-plugin');
const globImporter = require('node-sass-glob-importer');
const devMode = 'development' === process.env.NODE_ENV;

class MiniCssExtractPluginCleanup {
  apply(compiler) {
    compiler.hooks.emit.tapAsync(
      'MiniCssExtractPluginCleanup',
      (compilation, callback) => {
        Object.keys(compilation.assets)
          .filter(asset => {
            return [
              'admin/css/*.js',
              'admin/css/*.js.map',
              'public/css/*.js',
              'public/css/*.js.map',
            ].some(pattern => {
              return minimatch(asset, pattern);
            });
          })
          .forEach(asset => {
            delete compilation.assets[asset];
          });

        callback();
      },
    );
  }
}

module.exports = {
  mode: process.env.NODE_ENV,
  entry: {
    'public/js/wp_weatheron_plugin-public': './assets/public/js/wp_weatheron_plugin-public.js',
    'public/css/wp_weatheron_plugin-public': './assets/public/scss/wp_weatheron_plugin-public.scss',
    'admin/js/wp_weatheron_plugin-admin': './assets/admin/js/wp_weatheron_plugin-admin.js',
    'admin/css/wp_weatheron_plugin-admin': './assets/admin/scss/wp_weatheron_plugin-admin.scss'
  },
  output: {
    path: path.resolve(__dirname, ''),
    publicPath: '',
    filename: '[name].[hash].js',
  },
  devtool: devMode ? 'source-map' : 'cheap-eval-source-map',
  performance: {
    maxAssetSize: 1000000,
  },
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        sourceMap: true,
      }),
    ],
  },
  stats: {
    assets: !devMode,
    builtAt: !devMode,
    children: false,
    chunks: false,
    colors: true,
    entrypoints: !devMode,
    env: false,
    errors: !devMode,
    errorDetails: false,
    hash: false,
    maxModules: 20,
    modules: false,
    performance: !devMode,
    publicPath: false,
    reasons: false,
    source: false,
    timings: !devMode,
    version: false,
    warnings: !devMode,
  },
  module: {
    rules: [{
      enforce: 'pre',
      test: /\.js$/,
      exclude: /(node_modules|bower_components)/,
      use: [{
        loader: 'eslint-loader',
        options: {
          fix: true,
          emitWarning: true,
        },
      },],
    },
    {
      test: /\.js$/,
      exclude: /(node_modules|bower_components)/,
      use: [{
        loader: 'babel-loader',
      },],
    },
    {
      test: /\.s?css$/,
      use: [{
        loader: MiniCssExtractPlugin.loader,
        options: {
          publicPath: '',
        },
      },
      {
        loader: 'css-loader',
        options: {
          sourceMap: true,
        },
      },
      {
        loader: 'postcss-loader',
        options: {
          sourceMap: true,
        },
      },
      {
        loader: 'sass-loader',
        options: {
          sourceMap: true,
          importer: globImporter(),
        },
      },
      ],
    },
    {
      test: /\.(png|jpg|gif)$/,
      use: [{
        loader: 'file-loader',
        options: {
          outputPath: 'assets/images',
          name: '[name].[ext]',
        },
      },],
    },
    {
      test: /\.(woff(2)?|ttf|eot)$/,
      use: [{
        loader: 'file-loader',
        options: {
          outputPath: 'assets/fonts',
          name: '[name].[ext]',
        },
      },],
    },
    {
      test: /\.(svg)$/,
      use: [{
        loader: 'file-loader',
        options: {
          outputPath: '../../assets/svgs',
          name: '[name].[ext]',
        },
      },
      {
        loader: 'svgo-loader',
        options: {
          plugins: [{
            removeTitle: false
          },
          {
            convertColors: {
              shorthex: false
            }
          },
          {
            convertPathData: false
          },
          {
            removeViewBox: false
          },
          ],
        },
      },
      ],
    },
    ],
  },
  plugins: [
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      jquery: 'jquery',
      'window.jQuery': 'jquery'
    }),
    devMode &&
    new FriendlyErrorsPlugin({
      clearConsole: false,
    }),
    new CleanWebpackPlugin(['admin/css/*', 'public/css/*', 'admin/js/*', 'public/js/*'], {
      watch: true,
      verbose: true,
      cleanAfterEveryBuildPatterns: ['admin/css/wp_weatheron_plugin-admin.js', 'public/css/wp_weatheron_plugin-public.js', 'admin/js/wp_weatheron_plugin-admin.js.map', 'public/js/wp_weatheron_plugin-public.js.map'],
    }),
    new StyleLintPlugin({
      files: '**/*.scss',
      context: 'assets/**/scss/',
      failOnError: false,
      syntax: 'scss',
    }),
    new MiniCssExtractPlugin({
      filename: '[name].[hash].css',
    }),
    !devMode &&
    new ImageminPlugin({
      test: /\.(jpe?g|png|gif)$/i,
      cacheFolder: './imgcache',
    }),
    new MiniCssExtractPluginCleanup(),
  ].filter(Boolean),
};