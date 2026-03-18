<?php
/**
 * Craft Bastion config.php
 *
 * This file exists only as a template for the Craft Bastion settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'craft-bastion.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
	/*  IP RESTRICTIONS  */
	/*******************************************************************************
	 *   CONTROL PANEL
	 ******************************************************************************/
	// Enable IP restrictions for the control panel
	'ipEnabledCp'           => false,

	// IP/CIDR whitelist for the control panel
	'ipWhitelistCp'         => [
		[ '::1', 'IPv6 localhost' ],
		[ '127.0.0.1', 'IPv4 localhost' ],
	],

	// Restriction method for the control panel
	// 'redirect' or 'template'
	'ipRestrictionMethodCp' => 'template',

	// Url to redirect control panel if restriction method is 'redirect'
	'ipRedirectCp'          => '$PRIMARY_SITE_URL',

	// Template to render for control panel if restriction method is 'template'
	'ipTemplateCp'          => '',

	/*******************************************************************************
	 *   FRONT-END
	 ******************************************************************************/
	// Enable IP restrictions for the frontend
	'ipEnabled'             => false,

	// IP/CIDR whitelist for the frontend
	'ipWhitelist'           => [
		[ '::1', 'IPv6 localhost' ],
		[ '127.0.0.1', 'IPv4 localhost' ],
	],

	// Restriction method for the frontend
	// 'redirect' or 'template'
	'ipRestrictionMethod'   => 'template',

	// Url to redirect frontend if restriction method is 'redirect'
	'ipRedirect'            => 'https://craftcms.com',

	// Template to render for frontend if restriction method is 'template'
	'ipTemplate'            => '',
];
