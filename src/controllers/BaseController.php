<?php

namespace convergine\craftbastion\controllers;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\models\BaseSettingsModel;
use convergine\craftbastion\services\SecurityScanner;
use craft\web\Controller;

use Craft;
use yii\rbac\Item;

class BaseController extends Controller {

	public function init(): void {
		parent::init();

	}

	public function actionDashboard() {

		return $this->renderTemplate( 'craft-bastion/_dashboard', [
			'craftIndexing' => BastionPlugin::getInstance()->checker->allowCraftIndexing(),
			'isDev'         => BastionPlugin::getInstance()->checker->isDev(),
			'liveIndexing'  => BastionPlugin::getInstance()->checker->allowLiveIndexing(),
			'sslData'       => BastionPlugin::getInstance()->ssl->getSSLData(),
			'domainData'    => BastionPlugin::getInstance()->domain->getExpData(),
			'lastSecurityScan' => BastionPlugin::getInstance()->security_scanner->getLastScan(),
			'diskSpaceData' => BastionPlugin::getInstance()->disk_space_usage->getDiskSpaceData()
		] );

	}

	public function actionDependencyAudit() {
		$dependencyAudit = BastionPlugin::getInstance()->dependency_audit;
		$data = $dependencyAudit->getVulnerabilities(false); // Get from DB
		$summary = $dependencyAudit->getVulnerabilitySummary(false);
		$lastScan = $dependencyAudit->getLastScan();

		return $this->renderTemplate( 'craft-bastion/_dependency_audit', [
			'summary' => $summary,
			'stats' => $data['stats'] ?? null,
			'lastScan' => $lastScan,
			'scannedAt' => $data['scannedAt'] ?? null,
			'scanDuration' => $data['scanDuration'] ?? null,
		] );
	}

	public function actionDependencyAuditScan() {
		$this->requirePostRequest();
		$data = BastionPlugin::getInstance()->dependency_audit->getVulnerabilities(true);
		return $this->asJson(['success' => !isset($data['error']), 'data' => $data]);
	}

	public function actionSaveSettings() {
		$settingType  = $this->request->getRequiredBodyParam( 'settingsType' );
		$settingsData = $this->request->post( 'settings' );

		// Security: Validate that settingsType is a settings_* component
		$components = array_keys(BastionPlugin::getInstance()->getComponents());
		$allowedTypes = array_filter($components, function($component) {
			return str_starts_with($component, 'settings_');
		});

		if ( !in_array($settingType, $allowedTypes, true) ) {
			return $this->asJson( [
				'res'     => false,
				'message' => Craft::t('craft-bastion', 'Invalid settings type'),
				'errors'  => []
			] );
		}

		/**
		 * @var BaseSettingsModel $settings
		 */
		$settings = BastionPlugin::getInstance()->{$settingType};
		if ( ! $settings->saveSettings( $settingsData ) ) {

			return $this->asJson( [
				'res'     => false,
				'message' => Craft::t('craft-bastion', 'Settings NOT saved.'),
				'errors'  => $settings->errors
			] );
		}

		return $this->asJson( [
			'res'     => true,
			'message' => Craft::t('craft-bastion', 'Settings saved')
		] );

	}

	public function actionBotDefence() {
		$bot_defence = $this->request->getBodyParam('bot_defence', false);
		$botDefenceService = BastionPlugin::getInstance()->bot_defence;

		if ($bot_defence) {
			$result = $botDefenceService->addBastionBotDefense();
			$successMessage = Craft::t('craft-bastion', 'Rules added.');
		} else {
			$result = $botDefenceService->removeBastionBotDefense();
			$successMessage = Craft::t('craft-bastion', 'Rules removed.');
		}

		if ($result) {
			return $this->asSuccess($successMessage);
		}

		// Get specific error message
		$errorMessage = $botDefenceService->getLastError()
			?? Craft::t('craft-bastion', 'Failed to modify .htaccess');

		return $this->asFailure($errorMessage);
	}

	public function actionSecurityScan() {
		$response  = ['res'=>true,'message'=>''];
		$siteId = $this->request->getParam('siteId');
		$site = $this->request->getParam('site');
		$scanService = BastionPlugin::getInstance()->security_scanner;

		try{
			$result = $scanService->processScan();
		}catch (\Throwable $e){
			$response['res']  = false;
			$response['message']  = $e->getMessage();
		}
		return $this->asJson($response);
	}

	public function actionSaveSecuritySettings() {
		$post = $this->request->post();
		$enabledTests = $post['enabledTests']??[];
		$values = [];
		foreach ($enabledTests as $item=>$value){
			if($value){
				$values[] = $item;
			}
		}
		BastionPlugin::getInstance()->settings_security_scanner->updateSetting('enabledTests',$values);
		return $this->asJson(['res'=>true,'message'=>Craft::t('craft-bastion', 'Settings saved')]);
	}

}
