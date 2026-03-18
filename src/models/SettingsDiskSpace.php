<?php

namespace convergine\craftbastion\models;

use Craft;
use yii\validators\EmailValidator;

class SettingsDiskSpace extends BaseSettingsModel {

	public string $settingsType = 'settings_disk_space';

	public bool $enableDiskSpaceReminder = false;
	public int $diskSpaceThreshold = 80;
	public string $diskSpaceReminderRecipients = '';

	public function rules(): array
	{
		return [
			// Basic type validation
			[['enableDiskSpaceReminder'], 'boolean'],
			[['diskSpaceThreshold'], 'integer'],
			[['diskSpaceReminderRecipients'], 'string'],
			[['diskSpaceReminderRecipients'], 'validateEmailList'],
			[
				['diskSpaceThreshold'],
				'required',
				'when' => function ($model) {
					return (bool)$model->enableDiskSpaceReminder === true;
				},
				'message' => 'Disk space threshold is required when reminder enabled.',
			],
			[
				['diskSpaceReminderRecipients'],
				'required',
				'when' => function ($model) {
					return (bool)$model->enableDiskSpaceReminder === true;
				},
				'whenClient' => "function (attribute, value) {
                    return $('#diskSpaceReminderRecipients').is(':checked');
                }",
				'message' => 'Email\'s is required when reminder enabled.',
			],
			[
				['diskSpaceThreshold'],
				'integer',
				'min' => 1,
				'max' => 100,
				'message' => 'Disk space threshold must be between 1 and 100.',
			],
		];
	}

	public function validateEmailList()
	{
		if ($this->diskSpaceReminderRecipients === '') {
			return;
		}

		$validator = new EmailValidator([
			'enableIDN' => true,
			'allowName' => true,
		]);

		$emails = array_filter(array_map('trim', explode(';', $this->diskSpaceReminderRecipients)));

		foreach ($emails as $email) {
			// extract email from "Name <email@x>" format
			if (preg_match('/<([^>]+)>$/', $email, $m)) {
				$email = $m[1];
			}

			if (!$validator->validate($email)) {
				$this->addError(
					'diskSpaceReminderRecipients',
					Craft::t('app', "Invalid email: {email}", ['email' => $email])
				);
			}
		}
	}

}
