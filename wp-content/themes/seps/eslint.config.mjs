import js from '@eslint/js';
import globals from 'globals';

export default [
	{
		ignores: [ 'assets/dist/**', 'node_modules/**' ],
	},
	js.configs.recommended,
	{
		files: [ 'assets/src/js/**/*.js' ],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: globals.browser,
		},
		rules: {
			'no-var': 'error',
			'prefer-const': 'error',
			eqeqeq: [ 'error', 'always' ],
		},
	},
];
