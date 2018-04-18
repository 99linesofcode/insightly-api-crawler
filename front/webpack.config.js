const extractTextPlugin = require("extract-text-webpack-plugin");
const htmlWebpackPlugin = require("html-webpack-plugin");
const webpack = require('webpack');
const path = require('path');


module.exports = {
  context: path.resolve(__dirname, 'src'),
  entry: './app.jsx',
  output: {
    path: path.resolve(__dirname, 'public/'),
    filename: 'js/app.js'
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        loader: 'babel-loader',
        query: {
          babelrc: false,
          presets: ["env", "react"]
        },
        exclude: /node_modules/
      },
      {
        test: /\.sass|.css$/,
        loader: extractTextPlugin.extract({
          fallback: "style-loader",
          use: ["css-loader", "sass-loader"]
        })
      }
    ]
  },
  plugins: [
    new webpack.optimize.UglifyJsPlugin(),
    new extractTextPlugin({ filename: "css/app.css", allChunks: true }),
    new htmlWebpackPlugin({ template: "./index.html", inject: "body" })
  ]
};
