// Webpack config for Karma (SUITE-46). Module federation breaks Karma, so the spec build uses this plain config.
// Copy of core/app/core/webpack.config.spec.js with uniqueName 'tkc'.
module.exports = {
  output: {
    uniqueName: "tkc"
  },
  optimization: {
    // Only needed to bypass a temporary bug
    runtimeChunk: false
  },
  module: {
    rules: [
      {
        test: /\.m?js/,
        resolve: {
          fullySpecified: false
        }
      },
    ]
  },
  plugins: [],
};
