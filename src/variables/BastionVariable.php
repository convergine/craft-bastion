<?php

namespace convergine\craftbastion\variables;

use convergine\craftbastion\BastionPlugin;

class BastionVariable {

	public function getNonce(string $type = 'script-src') :string {
		return BastionPlugin::getInstance()->csp->registerNonce($type);
	}
}