<?php

namespace convergine\craftbastion\models;

use Craft;
use craft\base\Model;

class SecurityTestModel extends Model {

	public string $name = '';
	public string $description = '';
	public string $info = '';
	public string $status = 'passed';
	public array $link = [];

	public string $fixUrl = '';
	public string $fixText = '';
}