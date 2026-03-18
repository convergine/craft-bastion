<?php

namespace convergine\craftbastion\controllers;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\models\BaseSettingsModel;
use craft\web\Controller;
use Craft;
use craft\web\twig\nodes\DumpNode;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends Controller {
	
	private BastionPlugin $_plugin;
	public function init():void {
		parent::init();
		$this->_plugin = BastionPlugin::getInstance();
	}
	public function actionIpRestrictions() {
		$settings = $this->_plugin->settings_restrict;

		return $this->renderTemplate( 'craft-bastion/settings/_ip_restrictions', [
			'settings'           => $settings,
			'restrictionMethods' => $settings->getIpRestrictionMethods(),
			'currentIp'          => Craft::$app->getRequest()->getUserIP(),
			'restrict'           => $this->_plugin->restrict
		] );


	}

	public function actionCsp() {
		$settings = $this->_plugin->settings_csp;

		return $this->renderTemplate( 'craft-bastion/settings/_csp', [
			'settings' => $settings
		] );

	}

	public function actionUpdatesReminder() {
		$settings = $this->_plugin->settings_updates_reminder;

		return $this->renderTemplate( 'craft-bastion/settings/_updates_reminder', [
			'settings' => $settings
		] );
	}

	public function actionBotDefence() {
		$settings = $this->_plugin->settings_csp;

		return $this->renderTemplate( 'craft-bastion/settings/_bot_defence', [
			'settings' => $settings,
			'serverInfo' => BastionPlugin::getInstance()->bot_defence->getServerInfo(),
			'defence_enabled' => BastionPlugin::getInstance()->bot_defence->hasBastionBotDefense()
		] );

	}

	public function actionSetDefaultCsp() {

		return $this->_plugin->csp->setDefaultCsp() ?
			$this->asSuccess(Craft::t('craft-bastion', 'The recommended basic policy has been saved.')) :
			$this->asFailure(
				Craft::t('craft-bastion', 'Could’t set recommended basic policy.')
			);
	}

	public function actionSaveSettings() {
		$settings = $this->request->getBodyParam('settings');
		$settingsType = $this->request->getBodyParam('settingsType');
		$success = false;
		if(array_key_exists($settingsType,$this->_plugin->getComponents())){
			/**
			 * @var BaseSettingsModel $settingsModel
			 */
			$settingsModel = $this->_plugin->{$settingsType};
			$success = $settingsModel->saveSettings($settings);
		}else{
			throw new HttpException(500,'Settings type: "'.$settingsType . '" not found');
		}
		return $success ?
			$this->asSuccess(Craft::t('craft-bastion', 'Settings saved.')) :
			$this->asFailure(
				Craft::t('craft-bastion', 'Couldn’t save settings.'),
				routeParams: ['errors' => $settingsModel??$settingsModel->getErrors()]
			);
	}
}
