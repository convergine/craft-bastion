<?php

namespace convergine\craftbastion\models;

use Craft;

class SettingsSsl extends BaseSettingsModel {

	public string $settingsType = 'settings_ssl';
	public array $sslLabsData = [];

	public bool $enableCertificateReminder = false;
	public string $certificateReminderRecipients = '';

	public array $sslCertificateData = [];
	public array $sslCertificateDataCheck = [];

	public function rules(): array
	{
		return [
			// Basic type validation
			[['enableCertificateReminder'], 'boolean'],
			[['certificateReminderRecipients'], 'string'],
			[
				['certificateReminderRecipients'],
				'required',
				'when' => function ($model) {
					return (bool)$model->enableCertificateReminder === true;
				},
				'whenClient' => "function (attribute, value) {
                    return $('#certificateReminderRecipients').is(':checked');
                }",
				'message' => 'Email\'s is required when reminder enabled.',
			],
		];
	}

}