<?php

namespace convergine\craftbastion\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $siteId
 * @property int $totalPackages
 * @property int $vulnerablePackages
 * @property int $totalAdvisories
 * @property int $criticalCount
 * @property int $highCount
 * @property int $mediumCount
 * @property int $lowCount
 * @property float $scanDuration
 * @property string $vulnerabilities
 * @property string $packages
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 */
class DependencyAuditRecord extends ActiveRecord
{
	/**
	 * @return string
	 */
	public static function tableName(): string
	{
		return '{{%bastion_dependency_audit}}';
	}

	/**
	 * Get vulnerabilities as array
	 * @return array
	 */
	public function getVulnerabilities(): array
	{
		return json_decode($this->vulnerabilities, true) ?? [];
	}

	/**
	 * Get packages as array
	 * @return array
	 */
	public function getPackages(): array
	{
		return json_decode($this->packages, true) ?? [];
	}
}
