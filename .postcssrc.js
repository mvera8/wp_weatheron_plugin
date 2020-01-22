module.exports = {
  plugins: [
    require('pixrem')({
      atrules: true,
    }),
    require('cssnano'),
  ],
};
