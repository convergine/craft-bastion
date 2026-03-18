<?php

namespace convergine\craftbastion\assets;
use craft\web\AssetBundle;

class CpAssets extends AssetBundle{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->sourcePath = __DIR__;


		$this->css = [
			'css/style.css',
		];

		parent::init();
	}
}
