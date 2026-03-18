<?php

namespace convergine\craftbastion\assets;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class DAAssets extends AssetBundle{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->sourcePath = __DIR__;

		$this->depends = [CpAsset::class];
		$this->js = [
			'vendors/jquery-validate/jquery.validate.min.js',
			'dist/plugin.js',
		];

		$this->css = [
			'dist/plugin.css',
		];

		$this->publishOptions = [
			'only' => [
				'dist/*',
				'css/*',
				'js/*',
				'dist/*.map',          // ensure .map is copied as well
				'vendors/jquery-validate/*'
			],
		];

		parent::init();
	}
}
