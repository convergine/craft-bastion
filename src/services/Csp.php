<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\models\SettingsCsp;
use convergine\craftbastion\models\SettingsModel;
use craft\base\Component;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\View;
use IPTools\IP;
use IPTools\Network;
use IPTools\Range;
use Throwable;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\web\HttpException;

class Csp extends Component {
	private SettingsCsp $_settings;

	private array $_cspData = [];
	private array $_nonceData = [];

	public function init():void {
		parent::init();
		$this->_settings = BastionPlugin::getInstance()->settings_csp;
	}
	public function setDefaultCsp() {
		$settingsAttr                      = $this->_settings->getAttributes();
		$settingsAttr['cspEnabledOptions'] = [
			'defaultSrc' => [
				[ '\'self\'' ]
			],
			'scriptSrc'  => [
				[ '\'self\'' ]
			],
			'styleSrc'  => [
				[ '\'self\' \'unsafe-inline\'' ]
			],
			'imageSrc'  => [
				[ '\'self\' data:' ]
			],
			'fontSrc'  => [
				[ '\'self\' data:' ]
			],
			'objectSrc'  => [
				[ '\'none\'' ]
			],
			'frameAncestors'  => [
				[ '\'self\'' ]
			],
			'baseUri'  => [
				[ '\'self\'' ]
			],
			'formAction'  => [
				[ '\'self\'' ]
			],
			'upgradeInsecureRequests'  => [
				[ '' ]
			]
		];
		$settingsAttr['cspEnabled'] = true;
		$settingsAttr['cspMode'] = 'report';
		return BastionPlugin::getInstance()->settings_csp->saveSettings($settingsAttr);
	}

	public function setHeaders(): void {
		
		if ($this->_settings->cspHeaderProtectionEnabled){
			foreach($this->_settings->cspHeaderProtection as $header){
				if ((!empty($header[0])) && (!empty($header[1]))){
					Craft::$app->getResponse()->getHeaders()->remove(trim($header[0]));
					Craft::$app->getResponse()->getHeaders()->set(trim($header[0]), trim($header[1]));
				}
			}
		}
	}

	public function renderCsp(): void {

		foreach($this->_settings->cspEnabledOptions as $cspKey=>$option){
			if(
				$this->_settings->cspMode == 'tag' &&
				in_array($cspKey,['reportUri','frameAncestors','sandbox'])
			){
				continue;
			}


			if(isset($this->_settings->cspOptions[$cspKey])){
				$_cspKey = $this->_settings->cspOptions[$cspKey];
			}else{
				continue;
			}

			if($option){
				foreach($option as $row){
					$row = (array)$row;
						if (!empty(trim($row[0]))){
							$data = explode(' ', trim($row[0]));
							foreach($data as $csp){
								$this->_cspData[$_cspKey][] = $csp;
							}
						}
				}
			}

		}

		/* set nonces */
		foreach($this->_nonceData as $cspkey => $nonces){

			foreach($nonces as $nonce){
				$csp = "'nonce-".trim($nonce)."'";
				if (isset($this->_cspData[$cspkey]) && !in_array($csp, $this->_cspData[$cspkey])){
					$this->_cspData[$cspkey][] = $csp;
				}
			}
		}
		/* SEOMatic nonce  */
		if (Craft::$app->plugins->isPluginEnabled('seomatic')){
			$cspNonces = \nystudio107\seomatic\helpers\DynamicMeta::getCspNonces();
			foreach($cspNonces as $row){
				if (!empty(trim($row))){
					$data = explode(' ', trim($row));
					foreach($data as $csp){
						if (isset($this->_cspData['script-src'])){
							if (!in_array($csp, $this->_cspData['script-src'])){
								$this->_cspData['script-src'][] = $csp;
							}
						} else {
							$this->_cspData['script-src'][] = $csp;
						}
					}
				}
			}
		}
		$name = ($this->_settings->cspMode == 'report') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
		$value = '';
		foreach ($this->_cspData as $csp => $data){
			$value .= $csp.' '.implode(' ', $data).'; ';
		}

		if ($this->_settings->cspMode == 'tag'){
			Craft::$app->getView()->registerMetaTag([
				'content' => trim($value),
				'http-equiv' => $name
			]);
		} else {
			Craft::$app->getResponse()->getHeaders()->add($name, trim($value));
		}
	}

	public function registerNonce(string $type = 'script-src'): string {

		$nonces = [];
		foreach($this->_nonceData as $arg){
			$nonces = array_merge($nonces, $arg);
		}
		$nonce = base64_encode(StringHelper::randomString());
		while(in_array($nonce, $nonces)){
			$nonce = base64_encode(StringHelper::randomString());
		}
		if (in_array($type, $this->_settings->cspOptions)){
			$this->_nonceData[$type][] = $nonce;
		}

		return $nonce;
	}
}
