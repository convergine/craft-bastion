<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\helpers\BastionHelper;
use convergine\craftbastion\models\SettingsDomain;
use craft\base\Component;
use Craft;



class BotDefence extends Component {

	private SettingsDomain $_settings;

	private string $_htaccessPath;

	private ?string $_lastError = null;

	private const SERVER_NAMES = [
		'apache' => 'Apache',
		'openlitespeed' => 'OpenLiteSpeed',
		'litespeed' => 'LiteSpeed',
		'nginx' => 'Nginx',
	];

	public function init(): void {
		parent::init();
		$this->_settings = BastionPlugin::getInstance()->settings_domain;

		$this->_htaccessPath = Craft::getAlias('@webroot/.htaccess');
	}

	/**
	 * Get last error message
	 * @return string|null
	 */
	public function getLastError(): ?string {
		return $this->_lastError;
	}

	public function getServerInfo():array {
		$detectedServer = BastionHelper::detectServer();
		$serverName = self::SERVER_NAMES[$detectedServer] ?? 'Unknown';
		$canProtect = $detectedServer !== 'nginx' && $detectedServer !== 'unknown';

		if ($canProtect) {
			$key = BastionHelper::isBehindCloudflare()
				? 'botDefenceCanBeEnabledIsCloudflare'
				: 'botDefenceCanBeEnabledNoCloudflare';
			$text = Craft::t('craft-bastion', $key, ['server' => $serverName]);
		} else {
			$text = Craft::t('craft-bastion', 'botDefenceCanNOTBeEnabled');
		}

		return ['text' => $text, 'canProtect' => $canProtect];
	}

	public function addBastionBotDefense():bool {
		$this->_lastError = null; // Reset error
		$detectedServer = BastionHelper::detectServer();

		$result = match($detectedServer) {
			'apache' => $this->_addBastionApacheBotDefense(),
			'openlitespeed', 'litespeed' => $this->_addBastionOpenLiteSpeedBotDefense(),
			default => false // nginx and other unsupported servers
		};

		if (!$result && $this->_lastError === null) {
			$this->_lastError = Craft::t('craft-bastion', 'Unsupported server type');
		}

		return $result;
	}

	/**
	 * Creates a backup of .htaccess file
	 * @return string|false Backup file path on success, false on failure
	 */
	private function _createBackup(): string|false {
		if (!file_exists($this->_htaccessPath)) {
			return false;
		}

		$backupPath = $this->_htaccessPath . '.backup.' . time();

		if (@copy($this->_htaccessPath, $backupPath)) {
			BastionPlugin::addInfoLog("Created .htaccess backup: {$backupPath}");
			$this->_cleanupOldBackups();
			return $backupPath;
		}

		BastionPlugin::addErrorLog("Failed to create .htaccess backup");
		return false;
	}

	/**
	 * Removes old backup files, keeping only the 5 most recent
	 */
	private function _cleanupOldBackups(): void {
		$backupPattern = $this->_htaccessPath . '.backup.*';
		$backups = glob($backupPattern);

		if ($backups === false || count($backups) <= 5) {
			return;
		}

		// Sort by modification time (oldest first)
		usort($backups, fn($a, $b) => filemtime($a) <=> filemtime($b));

		// Remove oldest backups, keep 5 most recent
		$toDelete = array_slice($backups, 0, count($backups) - 5);
		foreach ($toDelete as $file) {
			@unlink($file);
		}
	}

	/**
	 * Safely reads .htaccess content with error handling
	 * @return string|false Content on success, false on failure
	 */
	private function _readHtaccess(): string|false {
		if (!file_exists($this->_htaccessPath)) {
			$this->_lastError = Craft::t('craft-bastion', '.htaccess file does not exist');
			BastionPlugin::addErrorLog(".htaccess file does not exist: {$this->_htaccessPath}");
			return false;
		}

		if (!is_readable($this->_htaccessPath)) {
			$this->_lastError = Craft::t('craft-bastion', '.htaccess is not readable');
			BastionPlugin::addErrorLog(".htaccess file is not readable: {$this->_htaccessPath}");
			return false;
		}

		$content = @file_get_contents($this->_htaccessPath);

		if ($content === false) {
			$error = error_get_last();
			$this->_lastError = Craft::t('craft-bastion', 'Failed to read .htaccess');
			BastionPlugin::addErrorLog("Failed to read .htaccess: " . ($error['message'] ?? 'Unknown error'));
			return false;
		}

		return $content;
	}

	/**
	 * Safely writes content to .htaccess with file locking
	 * @param string $content Content to write
	 * @return bool Success status
	 */
	private function _writeHtaccess(string $content): bool {
		$fp = @fopen($this->_htaccessPath, 'c+');

		if ($fp === false) {
			$error = error_get_last();
			$this->_lastError = Craft::t('craft-bastion', '.htaccess is not writable');
			BastionPlugin::addErrorLog("Failed to open .htaccess for writing: " . ($error['message'] ?? 'Unknown error'));
			return false;
		}

		// Acquire exclusive lock
		if (!flock($fp, LOCK_EX)) {
			$this->_lastError = Craft::t('craft-bastion', 'Failed to acquire lock on .htaccess');
			BastionPlugin::addErrorLog("Failed to acquire lock on .htaccess");
			fclose($fp);
			return false;
		}

		// Truncate and write
		ftruncate($fp, 0);
		rewind($fp);
		$result = fwrite($fp, $content);

		// Release lock
		flock($fp, LOCK_UN);
		fclose($fp);

		if ($result === false) {
			$this->_lastError = Craft::t('craft-bastion', 'Failed to write to .htaccess');
			BastionPlugin::addErrorLog("Failed to write to .htaccess");
			return false;
		}

		BastionPlugin::addInfoLog("Successfully wrote to .htaccess");
		return true;
	}

	public function removeBastionBotDefense(): bool
	{
		$this->_lastError = null; // Reset error

		if (!is_writable($this->_htaccessPath)) {
			$this->_lastError = Craft::t('craft-bastion', '.htaccess is not writable');
			BastionPlugin::addErrorLog(".htaccess is not writable");
			return false;
		}

		// Create backup before modifying
		$this->_createBackup();

		$content = $this->_readHtaccess();
		if ($content === false) {
			return false;
		}

		// Regex removes everything between BEGIN and END markers
		$pattern = '/# BEGIN BastionBotDefense.*?# END BastionBotDefense\s*/s';

		$newContent = preg_replace($pattern, '', $content);

		if ($newContent === null) {
			$this->_lastError = Craft::t('craft-bastion', 'Regex error while removing bot defense rules');
			BastionPlugin::addErrorLog("Regex error while removing bot defense rules");
			return false;
		}

		$result = $this->_writeHtaccess($newContent);

		if ($result) {
			BastionPlugin::addInfoLog("Bot defense rules removed from .htaccess");
		}

		return $result;
	}
	private function _addBastionApacheBotDefense(): bool
	{
		if (!is_writable($this->_htaccessPath)) {
			$this->_lastError = Craft::t('craft-bastion', '.htaccess is not writable');
			BastionPlugin::addErrorLog(".htaccess is not writable");
			return false;
		}

		// Create backup before modifying
		$this->_createBackup();

		$content = $this->_readHtaccess();
		if ($content === false) {
			return false;
		}

		// Already added?
		if (str_contains($content, '# BEGIN BastionBotDefense')) {
			BastionPlugin::addInfoLog("Bot defense rules already present in .htaccess");
			return true;
		}

		// Block to prepend
		$block = <<<HTA
# BEGIN BastionBotDefense
# =========================================
# Bastion Security Plugin - Bot Defense
# Server: Apache
# Blocks malicious bots, scrapers, scanners,
# empty user-agents, and common attack vectors.
# =========================================

<IfModule mod_rewrite.c>
RewriteEngine On

# Block empty or blank User-Agent
RewriteCond %{HTTP_USER_AGENT} ^\s*$ [OR]
RewriteCond %{HTTP_USER_AGENT} "^-?$"
RewriteRule ^.* - [F,L]

# Block known bad bots (modern curated list)
RewriteCond %{HTTP_USER_AGENT} AhrefsBot [OR]
RewriteCond %{HTTP_USER_AGENT} SemrushBot [OR]
RewriteCond %{HTTP_USER_AGENT} MJ12bot [OR]
RewriteCond %{HTTP_USER_AGENT} DotBot [OR]
RewriteCond %{HTTP_USER_AGENT} Baiduspider [OR]
RewriteCond %{HTTP_USER_AGENT} YandexBot [OR]
RewriteCond %{HTTP_USER_AGENT} crawler [NC,OR]
RewriteCond %{HTTP_USER_AGENT} scrapy [NC,OR]
RewriteCond %{HTTP_USER_AGENT} python-requests [NC,OR]
RewriteCond %{HTTP_USER_AGENT} libwww-perl [NC]
RewriteRule ^.* - [F,L]

# Block common attack query strings
RewriteCond %{QUERY_STRING} base64_encode [NC,OR]
RewriteCond %{QUERY_STRING} \.\./ [NC,OR]
RewriteCond %{QUERY_STRING} union.*select.*from [NC]
RewriteRule ^.* - [F,L]

</IfModule>

# Prevent directory browsing
Options -Indexes

# END BastionBotDefense

HTA;

		// Prepend block + existing content
		$newContent = $block . "\n\n" . $content;

		$result = $this->_writeHtaccess($newContent);

		if ($result) {
			BastionPlugin::addInfoLog("Bot defense rules added to .htaccess");
		}

		return $result;
	}

	private function _addBastionOpenLiteSpeedBotDefense(): bool
	{
		if (!is_writable($this->_htaccessPath)) {
			BastionPlugin::addErrorLog(".htaccess is not writable");
			return false;
		}

		// Create backup before modifying
		$this->_createBackup();

		$content = $this->_readHtaccess();
		if ($content === false) {
			return false;
		}

		// Already added?
		if (str_contains($content, '# BEGIN BastionBotDefense')) {
			BastionPlugin::addInfoLog("Bot defense rules already present in .htaccess");
			return true;
		}

		// Block to prepend
		$block = <<<HTA
# BEGIN BastionBotDefense
# =========================================
# Bastion Security Plugin - Bot Defense
# Server: OpenLiteSpeed
# Blocks malicious bots, scrapers, scanners,
# empty user-agents, and common attack vectors.
# =========================================

# Required for OpenLiteSpeed to process rewrite rules
RewriteControl on

<IfModule mod_rewrite.c>
RewriteEngine On

# Block empty or blank User-Agent
RewriteCond %{HTTP_USER_AGENT} ^\s*$ [OR]
RewriteCond %{HTTP_USER_AGENT} "^-?$"
RewriteRule ^.* - [F,L]

# Block known malicious bot user-agents (curated list)
RewriteCond %{HTTP_USER_AGENT} AhrefsBot [OR]
RewriteCond %{HTTP_USER_AGENT} SemrushBot [OR]
RewriteCond %{HTTP_USER_AGENT} MJ12bot [OR]
RewriteCond %{HTTP_USER_AGENT} DotBot [OR]
RewriteCond %{HTTP_USER_AGENT} Baiduspider [OR]
RewriteCond %{HTTP_USER_AGENT} YandexBot [OR]
RewriteCond %{HTTP_USER_AGENT} crawler [NC,OR]
RewriteCond %{HTTP_USER_AGENT} scrapy [NC,OR]
RewriteCond %{HTTP_USER_AGENT} python-requests [NC,OR]
RewriteCond %{HTTP_USER_AGENT} libwww-perl [NC]
RewriteRule ^.* - [F,L]

# Block common attack patterns in query strings
RewriteCond %{QUERY_STRING} base64_encode [NC,OR]
RewriteCond %{QUERY_STRING} \.\./ [NC,OR]
RewriteCond %{QUERY_STRING} union.*select.*from [NC]
RewriteRule ^.* - [F,L]

</IfModule>

# Prevent directory browsing
Options -Indexes

# END BastionBotDefense

HTA;

		// Prepend block + existing content
		$newContent = $block . "\n\n" . $content;

		$result = $this->_writeHtaccess($newContent);

		if ($result) {
			BastionPlugin::addInfoLog("Bot defense rules added to .htaccess");
		}

		return $result;
	}

	public function hasBastionBotDefense(): bool
	{
		if (!file_exists($this->_htaccessPath)) {
			return false;
		}

		$content = file_get_contents($this->_htaccessPath);

		// Check for both markers
		return str_contains($content, '# BEGIN BastionBotDefense')
		       && str_contains($content, '# END BastionBotDefense');
	}

}
