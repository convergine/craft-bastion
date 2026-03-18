<?php

namespace convergine\craftbastion\models;

use Craft;

class SettingsMain extends BaseSettingsModel {

	public string $settingsType = 'settings_main';
	public null|string $dailyReminderLastRun = null;

}