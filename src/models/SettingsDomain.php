<?php

namespace convergine\craftbastion\models;

use Craft;

class SettingsDomain extends BaseSettingsModel {

	public string $settingsType = 'settings_domain';

	public bool $enableDomainReminder = false;
	public string $domainReminderRecipients = '';

	public array $domainExpData = [];
	public array $domainExpDataLastCheck = [];

	public function rules(): array
	{
		return [
			// Basic type validation
			[['enableDomainReminder'], 'boolean'],
			[['domainReminderRecipients'], 'string'],
			[
				['domainReminderRecipients'],
				'required',
				'when' => function ($model) {
					return (bool)$model->enableDomainReminder === true;
				},
				'whenClient' => "function (attribute, value) {
                    return $('#domainReminderRecipients').is(':checked');
                }",
				'message' => 'Email\'s is required when reminder enabled.',
			],
		];
	}

}