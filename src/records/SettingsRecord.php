<?php

namespace convergine\craftbastion\records;

/**
 * @property int $id
 * @property int $siteId
 * @property int $type
 * @property int $name
 * @property int $value
 *
 */
class SettingsRecord extends \craft\db\ActiveRecord {
	/**
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%bastion_settings}}';
	}
}