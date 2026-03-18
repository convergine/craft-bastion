<?php

namespace convergine\craftbastion\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use IPTools\Range;

class SettingsIpRestrict extends BaseSettingsModel {

	public string $settingsType='settings_restrict';
		public const  IP_METHOD_REDIRECT = 'redirect';
	public const  IP_METHOD_TEMPLATE = 'template';
	public bool $ipEnabled = false;
	public array $ipWhitelist = [
		[ '::1', 'IPv6 localhost' ],
		[ '127.0.0.1', 'IPv4 localhost' ]
	];
	public string $ipRestrictionMethod = self::IP_METHOD_REDIRECT;
	public string $ipRedirect = '';
	public string $ipTemplate = '';

	public bool $ipEnabledCp = false;
	public array $ipWhitelistCp = [
		[ '::1', 'IPv6 localhost' ],
		[ '127.0.0.1', 'IPv4 localhost' ]
	];
	public string $ipRestrictionMethodCp = self::IP_METHOD_REDIRECT;
	public string $ipRedirectCp = '';
	public string $ipTemplateCp = '';


	public function getIpRestrictionMethods(): array {
		return [
			self::IP_METHOD_REDIRECT,
			self::IP_METHOD_TEMPLATE
		];
	}

	public function getIpRestrictionMethodsOptions() {
		return [
			[
				'label' => Craft::t( 'craft-bastion', 'Redirect URL' ),
				'value' => self::IP_METHOD_REDIRECT
			],
			[
				'label' => Craft::t( 'craft-bastion', 'Template' ),
				'value' => self::IP_METHOD_TEMPLATE
			]
		];
	}

	public function getIpEnabledCP(): bool {
		return App::parseEnv( $this->ipEnabledCp );
	}

	public function getIpRedirectCP(): string {
		return App::parseEnv( $this->ipRedirectCp );
	}

	public function getTemplateCP(): string {
		return App::parseEnv( $this->ipTemplateCp );
	}

	public function getIpEnabled(): bool {
		return (bool) App::parseEnv( $this->ipEnabled );
	}

	public function getIpRedirect(): string {
		return App::parseEnv( $this->ipRedirect );
	}

	public function getTemplate(): string {
		return App::parseEnv( $this->ipTemplate );
	}

	public function behaviors(): array {
		return [
			'parser' => [
				'class'      => EnvAttributeParserBehavior::class,
				'attributes' => [
					'ipEnabledCp',
					'ipRestrictionMethodCp',
					'ipRedirectCp',
					'ipTemplateCp',
					'ipEnabled',
					'ipRestrictionMethod',
					'ipRedirect',
					'ipTemplate'
				],
			],
		];
	}

	/**
	 * @return array
	 */
	public function rules(): array {
		$rules = parent::rules();

		$rules[] = [ [ 'ipEnabledCp', 'ipEnabled' ], 'boolean' ];
		$rules[] = [ [ 'ipEnabledCp', 'ipEnabled' ], 'default', 'value' => false ];
		$rules[] = [ [ 'ipWhitelistCp', 'ipWhitelist' ], 'validateIpCidr' ];
		$rules[] = [
			[ 'ipWhitelistCp', 'ipWhitelist' ],
			'default',
			'value' => [
				[ '::1', 'IPv6 localhost' ],
				[ '127.0.0.1', 'IPv4 localhost' ]
			]
		];
		$rules[] = [ [ 'ipRestrictionMethodCp', 'ipRedirectCp', 'ipTemplateCp', 'ipRestrictionMethod', 'ipRedirect', 'ipTemplate' ], 'string' ];
		$rules[] = [ [ 'ipRestrictionMethodCp', 'ipRedirectCp', 'ipTemplateCp', 'ipRestrictionMethod', 'ipRedirect', 'ipTemplate' ], 'validateMethods' ];

		return $rules;
	}

	/**
	 * Validate IP/CIDR on save
	 */
	public function validateIpCidr( $attribute ) {
		foreach ( $this->$attribute as &$row ) {
			try {
				Range::parse( $row[0] );
			} catch ( \Exception $e ) {
				$row[0] = [ 'value' => $row[0], 'hasErrors' => true ];
				$this->addError( $attribute, Craft::t( 'craft-bastion', 'pleaseProvideValidIpCidr' ) );
			}
		}
	}

	/**
	 * Require Redirect URL if method is redirect
	 */
	public function validateMethods( $attribute ) {
		// Control panel
		if ( $this->ipEnabledCp ) {
			if ( ! in_array( $this->ipRestrictionMethodCp, $this->getIpRestrictionMethods() ) ) {
				$this->addError( 'ipRestrictionMethodCp', Craft::t( 'craft-bastion', 'pleaseProvideValidRestrictionMethod' ) );
			} else if ( $this->ipRestrictionMethodCp == self::IP_METHOD_REDIRECT && empty( $this->ipRedirectCp ) ) {
				$this->addError( 'ipRedirectCp', Craft::t( 'craft-bastion', 'pleaseProvideValidUrl' ) );
			} else if ( $this->ipRestrictionMethodCp == self::IP_METHOD_TEMPLATE && empty( $this->ipTemplateCp ) ) {
				$this->addError( 'ipTemplateCp', Craft::t( 'craft-bastion', 'pleaseProvideValidTemplate' ) );
			}
		}

		// Front-End
		if ( $this->ipEnabled ) {
			if ( ! in_array( $this->ipRestrictionMethod, $this->getIpRestrictionMethods() ) ) {
				$this->addError( 'ipRestrictionMethod', Craft::t( 'craft-bastion', 'pleaseProvideValidRestrictionMethod' ) );
			} else if ( $this->ipRestrictionMethod == self::IP_METHOD_REDIRECT && empty( $this->ipRedirect ) ) {
				$this->addError( 'ipRedirect', Craft::t( 'craft-bastion', 'pleaseProvideValidUrl' ) );
			} else if ( $this->ipRestrictionMethod == self::IP_METHOD_TEMPLATE && empty( $this->ipTemplate ) ) {
				$this->addError( 'ipTemplate', Craft::t( 'craft-bastion', 'pleaseProvideValidTemplate' ) );
			}
		}
	}
}