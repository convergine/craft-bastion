<?php

namespace convergine\craftbastion\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use IPTools\Range;

class SettingsCsp extends BaseSettingsModel {

	public string $settingsType = 'settings_csp';

	// CSP Settings
	public array $cspOptions = [
		'defaultSrc'              => 'default-src',
		'scriptSrc'               => 'script-src',
		'scriptSrcAttr'           => 'script-src-attr',
		'scriptSrcElem'           => 'script-src-elem',
		'styleSrc'                => 'style-src',
		'styleSrcAttr'            => 'style-src-attr',
		'styleSrcElem'            => 'style-src-elem',
		'imgSrc'                  => 'img-src',
		'connectSrc'              => 'connect-src',
		'fontSrc'                 => 'font-src',
		'objectSrc'               => 'object-src',
		'mediaSrc'                => 'media-src',
		'frameSrc'                => 'frame-src',
		'sandbox'                 => 'sandbox',
		'reportUri'               => 'report-uri',
		'childSrc'                => 'child-src',
		'formAction'              => 'form-action',
		'base-uri'                => 'frame-ancestors',
		'pluginTypes'             => 'plugin-types',
		'baseUri'                 => 'base-uri',
		'reportTo'                => 'report-to',
		'workerSrc'               => 'worker-src',
		'manifestSrc'             => 'manifest-src',
		'prefetchSrc'             => 'prefetch-src',
		'navigateTo'              => 'navigate-to',
		'upgradeInsecureRequests' => 'upgrade-insecure-requests'
	];

	public bool $cspEnabled = false;
	public string $cspMode = 'header';

	public array $cspEnabledOptions = [];

	public bool $cspHeaderProtectionEnabled = false;
	public array $cspHeaderProtection = [
		[ 'Referrer-Policy', 'strict-origin-when-cross-origin' ],
		[ 'Strict-Transport-Security', 'max-age=31536000;includeSubDomains;preload' ],
		[ 'X-Content-Type-Options', 'nosniff' ],
		[ 'X-Frame-Options', 'SAMEORIGIN' ],
		[ 'X-Xss-Protection', '1; mode=block' ],
	];

	public array $cspModeOptions = [
		[ 'label' => 'Response Headers', 'value' => 'header' ],
		[ 'label' => 'Meta Tags', 'value' => 'tag' ],
		[ 'label' => 'Report Only', 'value' => 'report' ]
	];

}