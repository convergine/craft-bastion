<?php


namespace convergine\craftbastion\services;

use convergine\craftbastion\models\SettingsCsp;
use convergine\craftbastion\models\SettingsDiskSpace;
use convergine\craftbastion\models\SettingsDomain;
use convergine\craftbastion\models\SettingsIpRestrict;
use convergine\craftbastion\models\SettingsMain;
use convergine\craftbastion\models\SettingsSecurityScanner;
use convergine\craftbastion\models\SettingsSsl;
use convergine\craftbastion\models\SettingsUpdatesReminder;
use yii\base\InvalidConfigException;

/**
 * @author    convergine
 * @package   SCraft Bastion
 * @since     1.0.1
 *
 * @property Restrict $restrict
 * @property Csp $csp
 * @property Checker $checker
 * @property Reminder $reminder
 * @property SettingsCsp $settings_csp
 * @property SettingsIpRestrict $settings_restrict
 * @property SettingsSsl $settings_ssl
 * @property SettingsDomain $settings_domain
 * @property SettingsDiskSpace $settings_disk_space
 * @property SettingsMain $settings_main
 * @property SettingsUpdatesReminder $settings_updates_reminder
 * @property SettingsSecurityScanner $settings_security_scanner
 * @property Ssl $ssl
 * @property Domain $domain
 * @property BotDefence $bot_defence
 * @property SecurityScanner $security_scanner
 * @property UpdatesReminder $updates_reminder
 * @property DiskSpaceUsage $disk_space_usage
 * @property DependencyAudit $dependency_audit
 */
trait ServicesTrait {
	// Static Properties
	// =========================================================================

	public static ?string $majorVersion = '';

	// Static Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function config(): array {
		return [
			'components' => [
				'restrict'                  => Restrict::class,
				'csp'                       => Csp::class,
				'checker'                   => Checker::class,
				'reminder'                  => Reminder::class,
				'settings_csp'              => SettingsCsp::class,
				'settings_restrict'         => SettingsIpRestrict::class,
				'settings_ssl'              => SettingsSsl::class,
				'settings_domain'           => SettingsDomain::class,
				'settings_disk_space'       => SettingsDiskSpace::class,
				'settings_main'             => SettingsMain::class,
				'settings_updates_reminder' => SettingsUpdatesReminder::class,
				'settings_security_scanner'=> SettingsSecurityScanner::class,
				'ssl'                       => Ssl::class,
				'domain'                    => Domain::class,
				'bot_defence'               => BotDefence::class,
				'security_scanner'          => SecurityScanner::class,
				'updates_reminder'          => UpdatesReminder::class,
				'disk_space_usage'          => DiskSpaceUsage::class,
				'dependency_audit'          => DependencyAudit::class
			],
		];
	}

	// Public Methods
	// =========================================================================

	/**
	 * Returns the restrict service
	 *
	 * @return Restrict The restrict service
	 * @throws InvalidConfigException
	 */
	public function getRestrict(): Restrict {
		return $this->get( 'restrict' );
	}

	/**
	 * Returns the restrict service
	 *
	 * @return Checker The checker service
	 * @throws InvalidConfigException
	 */
	public function getChecker(): Checker {
		return $this->get( 'checker' );
	}

}
