<?php

Log::debug('DOCROOT value in asset.php: ' . (defined('DOCROOT') ? DOCROOT : 'DOCROOT NOT DEFINED'));

/**
 * Asset specific configuration
 */

return [

	/**
	 * An array of paths that will be searched for assets.
	 * Add paths here to be searched before the default asset path.
	 */
	'paths' => [
		// 'assets/', // Try using DOCROOT instead
		DOCROOT.'assets/', // Use absolute path based on Document Root
	],

	/**
	 * Asset Sub-folders
	 *
	 * Names for the img, js and css folders (inside the asset paths)
	 */
	'css_dir' => 'css/',
	'js_dir'  => 'js/',
	'img_dir' => 'img/',

	/**
	 * URL that should correspond to the public('assets') directory
	 *
	 * If you are using a CDN, this should be the CDN url.
	 */
	'url' => '/assets/',

	/**
	 * Whether to append the file's last modified time to the url.
	 * This is useful for cache busting.
	 */
	'add_mtime' => true,

	/**
	 * Whether to search recursively in asset paths.
	 */
	'recursive' => false,

	/**
	 * If true, Asset will automatically render the main group
	 * ('global' if not specified in config) in the template
	 */
	'auto_render' => true,

	/**
	 * The group to auto-render if 'auto_render' is true
	 */
	'global_group' => 'global',

	/**
	 * Whether to fail silently if a requested asset cannot be found.
	 * If false, an exception will be thrown.
	 */
	'fail_silently' => false,
]; 