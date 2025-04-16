<?php
/**
 * FuelPHP Pagination Configuration
 *
 * @package    Fuel
 * @version    1.x
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

return array(
	// The default pagination template
	'default' => array(
		// Add default template settings if needed, or leave as is to inherit core defaults
	),

	// Twitter Bootstrap 3.x template
	'bootstrap3' => array(
		'wrapper'                 => "<ul class=\"pagination\">\n\t{pagination}\n</ul>\n",

		'first'                   => "<li>\n\t<a href=\"{uri}\">{page}</a>\n</li>\n",
		'first-marker'            => "",
		'first-link'              => "<a href=\"{uri}\">« 最初</a>",
		'first-inactive'          => "",
		'first-inactive-link'     => "",

		'previous'                => "<li>\n\t<a href=\"{uri}\">{page}</a>\n</li>\n",
		'previous-marker'         => "",
		'previous-link'           => "<a href=\"{uri}\" rel=\"prev\">« 前</a>",
		'previous-inactive'       => "<li class=\"disabled\">\n\t<span>{page}</span>\n</li>\n",
		'previous-inactive-link'  => "<span>« 前</span>",

		'regular'                 => "<li>\n\t<a href=\"{uri}\">{page}</a>\n</li>\n",
		'regular-link'            => "<a href=\"{uri}\">{page}</a>",

		'active'                  => "<li class=\"active\">\n\t<span>{page} <span class=\"sr-only\">(current)</span></span>\n</li>\n",
		'active-link'             => "<span>{page}</span>",

		'next'                    => "<li>\n\t<a href=\"{uri}\">{page}</a>\n</li>\n",
		'next-marker'             => "",
		'next-link'               => "<a href=\"{uri}\" rel=\"next\">次 »</a>",
		'next-inactive'           => "<li class=\"disabled\">\n\t<span>{page}</span>\n</li>\n",
		'next-inactive-link'      => "<span>次 »</span>",

		'last'                    => "<li>\n\t<a href=\"{uri}\">{page}</a>\n</li>\n",
		'last-marker'             => "",
		'last-link'               => "<a href=\"{uri}\">最後 »»</a>",
		'last-inactive'           => "",
		'last-inactive-link'      => "",
	),
); 