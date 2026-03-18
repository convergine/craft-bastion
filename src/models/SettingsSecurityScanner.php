<?php

namespace convergine\craftbastion\models;

use convergine\craftbastion\BastionPlugin;
use Craft;
use yii\validators\EmailValidator;

class SettingsSecurityScanner extends BaseSettingsModel {

	public string $settingsType = 'settings_security_scanner';

	public array $mainTests = [
		// Updates
		'criticalCraftUpdates'=>'Critical Craft Updates',
		'criticalPluginUpdates'=>'Critical Plugin Updates',
		'craftUpdates'=>'Craft Updates',
		'pluginUpdates'=>'Plugin Updates',
		'webAliasInVolumeBaseUrl'=>'Web Alias In Volume Base URL',
		'contentSecurityPolicy'=>'Content-Security-Policy Header',
		'httpsControlPanel'=>'HTTPS In Control Panel',
		'httpsFrontEnd'=>'HTTPS On Front-End',
		'siteIndexing'=>'Site Indexing'
	];

	public array $additionalTests = [
		// HTTPS
		'frontendRestrictedByIp'=>'Frontend access (by IP)',
		'botDefence'=>'Bot Defence',

		// Setup
		'preventUserEnumeration'=>'Prevent User Enumeration',
		'sendPoweredByHeader'=>'Send Powered By Header',
		'adminUsername'=>'Admin Username',

		// General config settings
		'devMode'=>'Dev Mode',

		// Headers
		'cors'=>'Cross-Origin Resource Sharing (CORS)',
		'referrerPolicy'=>'Referrer-Policy Header',
		'strictTransportSecurity'=>'Strict-Transport-Security Header (HSTS)',
		'xContentTypeOptions'=>'X-Content-Type-Options Header',
		'xFrameOptions'=>'X-Frame-Options Header',

		// System
		'craftFilePermissions'=>'File/Folder System',
		'craftFolderPermissions'=>'Craft Folder Permissions',
		'craftFoldersAboveWebRoot'=>'Craft Folders Above Web Root',
		'phpVersion'=>'PHP Version',
	];

	public array $enabledTests = [
		'frontendRestrictedByIp',
		'botDefence',
		'preventUserEnumeration',
		'sendPoweredByHeader',
		'adminUsername',
		'devMode',
		'cors',
		'referrerPolicy',
		'strictTransportSecurity',
		'xContentTypeOptions',
		'xFrameOptions',
		'craftFilePermissions',
		'craftFolderPermissions',
		'craftFoldersAboveWebRoot',
		'phpVersion',
	];

	public function getSelectedAdditionalTests() {
		$tests = [];
		foreach ($this->additionalTests as $key=>$name){
			if(in_array($key, $this->enabledTests)){
				$tests[$key] = $name;
			}
		}
		return $tests;
	}
	public function rules(): array
	{
		return [

		];
	}



}