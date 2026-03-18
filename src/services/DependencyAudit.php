<?php

namespace convergine\craftbastion\services;

use Composer\Semver\Semver;
use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\records\DependencyAuditRecord;
use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;

class DependencyAudit extends Component
{
	private const API_URL = 'https://packagist.org/api/security-advisories/';
	private const BATCH_SIZE = 150;

	/**
	 * Get all vulnerabilities affecting currently installed packages
	 *
	 * @param bool $forceRefresh Force fresh scan (default: use database)
	 * @return array Array with 'packages', 'vulnerabilities', 'stats', 'scannedAt', 'scanDuration'
	 */
	public function getVulnerabilities(bool $forceRefresh = false): array
	{
		if (!$forceRefresh) {
			// Get from database
			$lastScan = $this->getLastScan();
			if ($lastScan) {
				return [
					'packages' => $lastScan->getPackages(),
					'vulnerabilities' => $lastScan->getVulnerabilities(),
					'stats' => [
						'total' => $lastScan->totalPackages,
						'vulnerable' => $lastScan->vulnerablePackages,
						'advisories' => $lastScan->totalAdvisories,
						'critical' => $lastScan->criticalCount,
						'high' => $lastScan->highCount,
						'medium' => $lastScan->mediumCount,
						'low' => $lastScan->lowCount,
					],
					'scannedAt' => DateTimeHelper::toDateTime($lastScan->dateCreated)->getTimestamp(),
					'scanDuration' => (float)$lastScan->scanDuration
				];
			}else{
				return [
					'packages' => [],
					'vulnerabilities' => [],
					'stats' => [
						'total' => 0,
						'vulnerable' => 0,
						'advisories' => 0,
						'critical' => 0,
						'high' => 0,
						'medium' => 0,
						'low' => 0,
					],
					'scannedAt' => '',
					'scanDuration' => 0
				];
			}
		}

		// Perform real scan
		$data = $this->_scanDependencies();

		// Save to database
		if (!isset($data['error'])) {
			$this->_saveToDatabase($data);
		}

		return $data;
	}

	/**
	 * Get last scan from database
	 *
	 * @param int|null $siteId
	 * @return DependencyAuditRecord|null
	 */
	public function getLastScan(?int $siteId = null): ?DependencyAuditRecord
	{
		$siteId = $siteId ?: Craft::$app->getSites()->getPrimarySite()->id;

		return DependencyAuditRecord::find()
			->where(['siteId' => $siteId])
			->orderBy(['dateCreated' => SORT_DESC])
			->one();
	}

	/**
	 * Main scanning logic
	 */
	private function _scanDependencies(): array
	{
		$startTime = microtime(true);

		$composerLockPath = Craft::getAlias('@root/composer.lock');

		if (!file_exists($composerLockPath)) {
			BastionPlugin::addErrorLog('composer.lock not found at: ' . $composerLockPath);
			return [
				'error' => 'composer.lock not found',
				'packages' => [],
				'vulnerabilities' => [],
				'stats' => ['total' => 0, 'vulnerable' => 0, 'advisories' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0],
				'scannedAt' => time(),
				'scanDuration' => 0
			];
		}

		// Parse composer.lock
		$composerData = json_decode(file_get_contents($composerLockPath), true);
		if (!$composerData) {
			BastionPlugin::addErrorLog('Failed to parse composer.lock');
			return [
				'error' => 'Failed to parse composer.lock',
				'packages' => [],
				'vulnerabilities' => [],
				'stats' => ['total' => 0, 'vulnerable' => 0, 'advisories' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0],
				'scannedAt' => time(),
				'scanDuration' => 0
			];
		}

		// Extract all packages (production + dev)
		$packages = $this->_extractPackages($composerData);
		BastionPlugin::addInfoLog('Found ' . count($packages) . ' packages to scan');

		// Query Packagist API in batches
		$advisories = $this->_fetchAdvisories(array_keys($packages));

		// Match vulnerabilities
		$vulnerabilities = $this->_matchVulnerabilities($packages, $advisories);

		// Calculate severity counts
		$severityCounts = $this->_calculateSeverityCounts($vulnerabilities);

		// Build stats
		$stats = [
			'total' => count($packages),
			'vulnerable' => count($vulnerabilities),
			'advisories' => array_sum(array_map(fn($v) => count($v['advisories'] ?? []), $vulnerabilities)),
			'critical' => $severityCounts['critical'],
			'high' => $severityCounts['high'],
			'medium' => $severityCounts['medium'],
			'low' => $severityCounts['low'],
		];

		$scanDuration = microtime(true) - $startTime;

		BastionPlugin::addInfoLog("Dependency scan complete: {$stats['vulnerable']} vulnerable packages, {$stats['advisories']} advisories in " . number_format($scanDuration, 3) . "s");

		return [
			'packages' => $packages,
			'vulnerabilities' => $vulnerabilities,
			'stats' => $stats,
			'scannedAt' => time(),
			'scanDuration' => $scanDuration
		];
	}

	/**
	 * Save scan results to database
	 *
	 * @param array $data
	 */
	private function _saveToDatabase(array $data): void
	{
		$record = new DependencyAuditRecord();
		$record->siteId = Craft::$app->getSites()->getPrimarySite()->id;
		$record->totalPackages = $data['stats']['total'];
		$record->vulnerablePackages = $data['stats']['vulnerable'];
		$record->totalAdvisories = $data['stats']['advisories'];
		$record->criticalCount = $data['stats']['critical'];
		$record->highCount = $data['stats']['high'];
		$record->mediumCount = $data['stats']['medium'];
		$record->lowCount = $data['stats']['low'];
		$record->scanDuration = $data['scanDuration'];
		$record->vulnerabilities = json_encode($data['vulnerabilities']);
		$record->packages = json_encode($data['packages']);
		$record->save();

		BastionPlugin::addInfoLog('Dependency audit results saved to database');
	}

	/**
	 * Calculate counts by severity
	 *
	 * @param array $vulnerabilities
	 * @return array
	 */
	private function _calculateSeverityCounts(array $vulnerabilities): array
	{
		$counts = [
			'critical' => 0,
			'high' => 0,
			'medium' => 0,
			'low' => 0,
		];

		foreach ($vulnerabilities as $packageVulns) {
			foreach ($packageVulns['advisories'] as $advisory) {
				$severity = $advisory['severity'] ?? 'unknown';
				if (isset($counts[$severity])) {
					$counts[$severity]++;
				}
			}
		}

		return $counts;
	}

	/**
	 * Extract package names and versions from composer.lock
	 *
	 * @param array $composerData Parsed composer.lock content
	 * @return array [package-name => ['version' => x, 'isDev' => bool]]
	 */
	private function _extractPackages(array $composerData): array
	{
		$packages = [];

		// Production packages
		if (isset($composerData['packages']) && is_array($composerData['packages'])) {
			foreach ($composerData['packages'] as $package) {
				$name = $package['name'] ?? null;
				$version = $package['version'] ?? null;

				if ($name && $version) {
					// Normalize version: strip 'v' prefix
					$version = ltrim($version, 'v');
					$packages[$name] = [
						'version' => $version,
						'isDev' => false
					];
				}
			}
		}

		// Dev packages
		if (isset($composerData['packages-dev']) && is_array($composerData['packages-dev'])) {
			foreach ($composerData['packages-dev'] as $package) {
				$name = $package['name'] ?? null;
				$version = $package['version'] ?? null;

				if ($name && $version) {
					// Normalize version: strip 'v' prefix
					$version = ltrim($version, 'v');
					$packages[$name] = [
						'version' => $version,
						'isDev' => true
					];
				}
			}
		}

		return $packages;
	}

	/**
	 * Fetch security advisories from Packagist API
	 *
	 * @param array $packageNames List of package names
	 * @return array [package-name => [advisories]]
	 */
	private function _fetchAdvisories(array $packageNames): array
	{
		$allAdvisories = [];

		// Split into batches to avoid URL length limits
		$batches = array_chunk($packageNames, self::BATCH_SIZE);

		foreach ($batches as $batchIndex => $batch) {
			BastionPlugin::addInfoLog('Fetching advisories batch ' . ($batchIndex + 1) . '/' . count($batches));

			$advisories = $this->_fetchAdvisoryBatch($batch);
			$allAdvisories = array_merge($allAdvisories, $advisories);
		}

		return $allAdvisories;
	}

	/**
	 * Fetch a single batch of advisories
	 *
	 * @param array $packageNames
	 * @return array
	 */
	private function _fetchAdvisoryBatch(array $packageNames): array
	{
		// Build query string: packages[]=vendor/package&packages[]=vendor/package2
		$queryParts = [];
		foreach ($packageNames as $name) {
			$queryParts[] = 'packages[]=' . urlencode($name);
		}
		$queryString = implode('&', $queryParts);
		$url = self::API_URL . '?' . $queryString;

		// Get admin email for User-Agent
		$adminEmail = $this->_getAdminEmail();
		$userAgent = 'CraftBastion/1.0 (mailto:' . $adminEmail . ')';

		try {
			$client = Craft::createGuzzleClient([
				'timeout' => 30,
				'headers' => [
					'User-Agent' => $userAgent,
					'Accept' => 'application/json'
				]
			]);

			$response = $client->get($url);
			$body = (string)$response->getBody();
			$data = json_decode($body, true);

			if (!$data || !isset($data['advisories'])) {
				BastionPlugin::addErrorLog('Invalid response from Packagist API');
				return [];
			}

			return $data['advisories'];

		} catch (\Exception $e) {
			BastionPlugin::addErrorLog('Failed to fetch advisories: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Match vulnerabilities against installed versions
	 *
	 * @param array $packages [package-name => ['version' => x, 'isDev' => bool]]
	 * @param array $advisories [package-name => [advisories]]
	 * @return array [package-name => [matched advisories with version info]]
	 */
	private function _matchVulnerabilities(array $packages, array $advisories): array
	{
		$vulnerabilities = [];

		foreach ($advisories as $packageName => $packageAdvisories) {
			if (!isset($packages[$packageName])) {
				continue; // Package not installed
			}

			$installedVersion = $packages[$packageName]['version'];
			$isDev = $packages[$packageName]['isDev'];

			$matchedAdvisories = [];

			foreach ($packageAdvisories as $advisory) {
				$affectedVersions = $advisory['affectedVersions'] ?? null;

				if (!$affectedVersions) {
					continue;
				}

				try {
					// Check if installed version satisfies the constraint
					if (Semver::satisfies($installedVersion, $affectedVersions)) {
						// Normalize sources to always be an array of strings
						$sources = $advisory['sources'] ?? [];
						if (!is_array($sources)) {
							$sources = [$sources];
						}
						// Ensure each source is a string
						$sources = array_filter(array_map(function($source) {
							return is_string($source) ? $source : (string)($source['url'] ?? '');
						}, $sources));

						$matchedAdvisories[] = [
							'advisoryId' => $advisory['advisoryId'] ?? null,
							'title' => $advisory['title'] ?? 'Unknown vulnerability',
							'cve' => $advisory['cve'] ?? null,
							'affectedVersions' => $affectedVersions,
							'severity' => $this->_parseSeverity($advisory),
							'link' => $advisory['link'] ?? null,
							'sources' => array_values($sources),
							'reportedAt' => $advisory['reportedAt'] ?? null
						];
					}
				} catch (\Exception $e) {
					BastionPlugin::addErrorLog("Failed to check version constraint for {$packageName}: " . $e->getMessage());
				}
			}

			if (!empty($matchedAdvisories)) {
				$vulnerabilities[$packageName] = [
					'installedVersion' => $installedVersion,
					'isDev' => $isDev,
					'advisories' => $matchedAdvisories
				];
			}
		}

		return $vulnerabilities;
	}

	/**
	 * Parse severity from advisory data
	 *
	 * @param array $advisory
	 * @return string|null
	 */
	private function _parseSeverity(array $advisory): ?string
	{
		// Check for CVSS data
		if (isset($advisory['cvss']['vector'])) {
			return $this->_cvssToSeverity($advisory['cvss']);
		}

		// Fallback to explicit severity field if present
		if (isset($advisory['severity'])) {
			return strtolower($advisory['severity']);
		}

		return null;
	}

	/**
	 * Convert CVSS score to severity level
	 *
	 * @param array $cvss
	 * @return string
	 */
	private function _cvssToSeverity(array $cvss): string
	{
		$score = $cvss['score'] ?? 0;

		if ($score >= 9.0) {
			return 'critical';
		} elseif ($score >= 7.0) {
			return 'high';
		} elseif ($score >= 4.0) {
			return 'medium';
		} elseif ($score > 0) {
			return 'low';
		}

		return 'unknown';
	}

	/**
	 * Get summary of vulnerabilities by severity
	 *
	 * @param bool $forceRefresh
	 * @return array
	 */
	public function getVulnerabilitySummary(bool $forceRefresh = false): array
	{
		$data = $this->getVulnerabilities($forceRefresh);
		$vulnerabilities = $data['vulnerabilities'] ?? [];

		$summary = [
			'critical' => [],
			'high' => [],
			'medium' => [],
			'low' => [],
			'unknown' => []
		];

		foreach ($vulnerabilities as $package => $packageVulns) {
			foreach ($packageVulns['advisories'] as $advisory) {
				$severity = $advisory['severity'] ?? 'unknown';
				if (isset($summary[$severity])) {
					$summary[$severity][] = [
						'isDev' => $packageVulns['isDev'],
						'installedVersion' => $packageVulns['installedVersion'],
						'package' => $package
					] + $advisory;
				}
			}
		}

		return $summary;
	}

	/**
	 * Get admin email for User-Agent
	 *
	 * @return string
	 */
	private function _getAdminEmail(): string
	{
		$admin = \craft\elements\User::find()
			->admin(true)
			->orderBy('id ASC')
			->one();
		return $admin ? $admin->email : "admin@example.com";
	}
}
