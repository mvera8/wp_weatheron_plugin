module.exports = function(api) {
	api.cache(true);

	const presets = [['@babel/preset-env', { modules: false }]];
	const plugins = ['lodash'];

	return {
		presets,
		plugins,
	};
};
