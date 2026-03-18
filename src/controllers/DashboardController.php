<?php

namespace convergine\craftbastion\controllers;

use convergine\craftbastion\BastionPlugin;
use craft\web\Controller;
use Craft;


class DashboardController extends Controller {

	private BastionPlugin $_plugin;

	public function init(): void {
		parent::init();
		$this->_plugin = BastionPlugin::getInstance();
	}

	public function actionGetSslData() {
		$clearCache = (bool) $this->request->get( 'clearCache', false );

		return $this->asJson(
			$this->_plugin->ssl->fetchSSLData( $clearCache )
		);
	}

	public function actionGetDomainData() {

		return $this->asJson(
			$this->_plugin->domain->getExpData(false)
		);
	}

	public function actionSecuritySettings() {
		$settings = BastionPlugin::getInstance()->settings_security_scanner;
		return $this->renderTemplate( 'craft-bastion/dashboard/_security_settings_popup', [
			'title' => 'Security Checks Settings',
			'settings'=>$settings
		] );
	}

	public function actionAuditSettings() {
		return $this->renderTemplate( 'craft-bastion/dashboard/_audit_settings_popup', [
			'title' => 'Audit Log Settings'
		] );
	}

	public function actionDomainSettings() {
		return $this->renderTemplate( 'craft-bastion/dashboard/_domain_settings_popup', [
			'settings' => $this->_plugin->settings_domain,
			'domainData'    => BastionPlugin::getInstance()->domain->getExpData(),
			'title' => Craft::t('craft-bastion','Domain Settings')
		] );
	}

	public function actionSslSettings() {
		return $this->renderTemplate( 'craft-bastion/dashboard/_ssl_settings_popup', [
			'settings' => $this->_plugin->settings_ssl,
			'title' => Craft::t('craft-bastion','SSL Settings')
		] );
	}

	public function actionDiskSpaceSettings() {
		return $this->renderTemplate( 'craft-bastion/dashboard/_disk_space_settings_popup', [
			'settings' => $this->_plugin->settings_disk_space,
			'title' => Craft::t('craft-bastion','Disk Space Settings')
		] );
	}

}
