<?php

namespace convergine\craftbastion\records;

/**
 * @property int $id
 * @property int $siteId
 * @property bool $pass
 * @property bool $warning
 * @property string $results
 */
class ScannerRecord extends \craft\db\ActiveRecord {
	/**
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%bastion_scanner}}';
	}

	public function getResults() {
		return json_decode($this->results, true);
	}
}