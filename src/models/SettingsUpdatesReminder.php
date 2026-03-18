<?php

namespace convergine\craftbastion\models;

use convergine\craftbastion\BastionPlugin;
use Craft;
use yii\validators\EmailValidator;

class SettingsUpdatesReminder extends BaseSettingsModel {

	public string $settingsType = 'settings_updates_reminder';

	public bool $updatesEnabled = false;

	public string $emailAddresses = '';

	public string $notifyDayOfWeek = 'Monday';

	public string $frequency = 'Weekly';

	public string $lastReminderDate = '';

	public function rules(): array
	{
		return [
			// Basic type validation
			[['updatesEnabled'], 'boolean'],
			[['emailAddresses'], 'string'],
			[['emailAddresses'], 'validateEmailList'],
			[
				['emailAddresses'],
				'required',
				'when' => function ($model) {
					return (bool)$model->updatesEnabled === true;
				},
				'whenClient' => "function (attribute, value) {
                    return $('#updatesEnabled').is(':checked');
                }",
				'message' => 'Email\'s is required when reminder enabled.',
			],
		];
	}

	public function validateEmailList()
	{
		if ($this->emailAddresses === '') {
			return;
		}

		$validator = new EmailValidator([
			'enableIDN' => true,
			'allowName' => true,
		]);

		$emails = array_filter(array_map('trim', explode(';', $this->emailAddresses)));

		foreach ($emails as $email) {
			// extract email from "Name <email@x>" format
			if (preg_match('/<([^>]+)>$/', $email, $m)) {
				$email = $m[1];
			}

			if (!$validator->validate($email)) {
				$this->addError(
					'emailAddresses',
					Craft::t('app', "Invalid email: {email}", ['email' => $email])
				);
			}
		}
	}
	public function getDays() {
		return [
			'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
		];
	}

	public function getFrequency() {
		return [
			'Daily', 'Weekly', 'Bi-weekly', 'Monthly'
		];
	}

}