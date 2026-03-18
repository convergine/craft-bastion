<?php

namespace convergine\craftbastion\models;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\records\SettingsRecord;
use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use IPTools\Range;
use yii\db\Exception;

abstract class BaseSettingsModel extends Model {

	public string $settingsType = '';

	public function init(): void {
		parent::init();
		$this->_getSettingsFromDb();
	}

	public function saveSettings( array $settings ): bool {
		$this->setAttributes( $settings );
		if ( ! $this->validate() ) {
			return false;
		}
		BastionPlugin::addInfoLog($settings);
		foreach ( $settings as $name => $value ) {
			
			if(!$this->updateSetting($name, $value, false)){
				return false;
			}

		}
		$this->_afterSaveSettings( $settings );
		
		return true;
	}

	public function updateSetting( $name, $value, $updateSettingFromDb = true ):bool {
		$record = SettingsRecord::find()->where( [ 'name' => $name, 'type' => $this->settingsType ] )->one();
		if ( ! $record ) {
			$record = new SettingsRecord();
			$record->type = $this->settingsType;
		}


		if ( ! $this->hasProperty( $name ) ) {
			return false;
		}

		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			$value = json_encode( $value );
		}
		$record->name  = $name;
		$record->value = $value;
		try {
			$record->save();
			BastionPlugin::addInfoLog('save settings');
			if($updateSettingFromDb){
				$this->_getSettingsFromDb($name);
			}

		} catch ( Exception $e ) {
			$this->errors[]= $e->getMessage();
			BastionPlugin::addInfoLog($e);
			return false;
		}

		return true;
	}

	protected function _afterSaveSettings( $settings ) {
		$this->_getSettingsFromDb();
	}

	protected function _getSettingsFromDb($settingName = null): void {
		$settings = [];
		$where = ['type' => $this->settingsType];
		if($settingName){
			$where['name'] = $settingName;
		}
		foreach ( SettingsRecord::find()->where($where)->all() as $row ) {
			$value = $row->value;
			if ( null !== $encodedValue = json_decode( $row->value, true ) ) {
				$value = $encodedValue;
			}
			$settings[ $row->name ] = $value;
		}
		$this->setAttributes( $settings, false );
	}
}