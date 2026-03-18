<?php

namespace convergine\craftbastion;

use convergine\craftbastion\jobs\DailyReminderJob;
use convergine\craftbastion\services\Restrict;
use convergine\craftbastion\services\ServicesTrait;
use convergine\craftbastion\variables\BastionVariable;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\Application;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use convergine\craftbastion\models\SettingsModel;
use craft\web\View;
use yii\base\Event;

class BastionPlugin extends Plugin {

	use ServicesTrait;


	public static string $plugin;
	public ?string $name = 'Craft Bastion';

	public function init(): void {
		$this->hasCpSection  = true;
		$this->hasCpSettings = true;

		parent::init();

		/** @var SettingsModel $settings */
		$settings = $this->getSettings();

		$this->_registerLogsTarget();
		$this->_registerEvents();
		$this->_setRoutes();
		$this->_setCspEvents();

		if ( $this->isInstalled ) {
			$this->restrict->restrict();
		}

		Craft::$app->view->registerTranslations('craft-bastion', [
				'Re-running security checks',
				'Failed to run scan. Please try again.',
				'Scan failed',
				'Processing...'
		]);

	}

	protected function _registerEvents() {
		Event::on(
			View::class,
			View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
			function ( RegisterTemplateRootsEvent $e ) {
				$e->roots['craft-bastion'] = $this->getBasePath() . '/templates';
			}
		);
		// Run watchdog after every web request (both CP and site)
		Event::on(
			Application::class,
			Application::EVENT_AFTER_REQUEST,
			function () {
				$this->reminder->checkAndRestoreReminderJob();
			}
		);
	}

	protected function _setRoutes(): void {
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function ( RegisterUrlRulesEvent $event ) {
				$event->rules['craft-bastion'] = 'craft-bastion/base/dashboard';

				$event->rules['craft-bastion/ip-restrictions']  = 'craft-bastion/settings/ip-restrictions';
				$event->rules['craft-bastion/csp']              = 'craft-bastion/settings/csp';
				$event->rules['craft-bastion/bot-defence']      = 'craft-bastion/settings/bot-defence';
				$event->rules['craft-bastion/updates_reminder'] = 'craft-bastion/settings/updates-reminder';
				$event->rules['craft-bastion/dependency-audit'] = 'craft-bastion/base/dependency-audit';


			}
		);
	}

	private function _setCspEvents(): void {

		Event::on( CraftVariable::class, CraftVariable::EVENT_INIT, function ( Event $e ) {
			$variable = $e->sender;
			$variable->set( 'bastion', BastionVariable::class );
		} );

		Event::on(
			Application::class,
			Application::EVENT_INIT,
			function ( Event $event ) {
				if ( Craft::$app->getRequest()->getIsConsoleRequest() ) {
					return;
				}
				$this->csp->setHeaders();
				if ( Craft::$app->getRequest()->getIsSiteRequest() ) {
					$this->_registerCSP();
				}
			}
		);

	}

	private function _registerCSP(): void {
		$user = Craft::$app->getUser()->getIdentity();

		if ( $this->settings_csp->cspEnabled ) {
			Event::on( View::class, View::EVENT_END_PAGE, function ( Event $e ) {
				if ( $this->settings_csp->cspEnabled ) {
					if ( $this->settings_csp->cspMode == 'tag' ) {
						$this->csp->renderCsp();
					}
				}
			} );
			Event::on( View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE, function ( Event $e ) {
				/* Remove all existing CSP headers */
				Craft::$app->getResponse()->getHeaders()->remove( 'Content-Security-Policy' );
				Craft::$app->getResponse()->getHeaders()->remove( 'X-Content-Security-Policy' );
				Craft::$app->getResponse()->getHeaders()->remove( 'X-Webkit-Csp' );
				if ( $this->settings_csp->cspEnabled ) {
					if ( $this->settings_csp->cspMode == 'header' || $this->settings_csp->cspMode == 'report' ) {
						$this->csp->renderCsp();
					}
				}
			} );
		}
	}

	public function getCpNavItem(): ?array {
		$nav = parent::getCpNavItem();

		$nav['label'] = Craft::t( 'craft-bastion', 'Bastion' );
		$nav['url']   = 'craft-bastion';

		if ( Craft::$app->getUser()->getIsAdmin() ) {
			$nav['subnav']['dashboard']       = [
				'label' => Craft::t( 'craft-bastion', 'Dashboard' ),
				'url'   => 'craft-bastion',
			];
			$nav['subnav']['ip-restrictions'] = [
				'label' => Craft::t( 'craft-bastion', 'IP Restrictions' ),
				'url'   => 'craft-bastion/ip-restrictions',
			];
			$nav['subnav']['csp']             = [
				'label' => Craft::t( 'craft-bastion', 'CSP' ),
				'url'   => 'craft-bastion/csp',
			];
			$nav['subnav']['bot-defence']     = [
				'label' => Craft::t( 'craft-bastion', 'Bot Defence' ),
				'url'   => 'craft-bastion/bot-defence',
			];
			$nav['subnav']['dependency-audit']     = [
				'label' => Craft::t( 'craft-bastion', 'Dependency Audit' ),
				'url'   => 'craft-bastion/dependency-audit',
			];
			$nav['subnav']['updates-reminder']     = [
				'label' => Craft::t( 'craft-bastion', 'Updates Reminder' ),
				'url'   => 'craft-bastion/updates_reminder',
			];

		}

		return $nav;
	}

	protected function createSettingsModel(): SettingsModel {
		/* plugin settings model */
		return new SettingsModel();
	}

	public function getSettingsResponse(): mixed {
		return Craft::$app->getResponse()->redirect( UrlHelper::cpUrl( 'craft-bastion' ) );
	}

	public static function addErrorLog( $message ): void {
		Craft::error( $message, 'craft-bastion' );
	}

	public static function addInfoLog( $message ): void {
		Craft::info( $message, 'craft-bastion' );
	}

	private function _registerLogsTarget() {
		$logService = Craft::$app->getLog();

		$logService->targets[] = Craft::createObject( [
			'class'        => \yii\log\FileTarget::class,
			'logFile'      => Craft::getAlias( '@storage/logs/craft-bastion-' . date( "Y-m-d" ) . '.log' ),
			'categories'   => [ 'craft-bastion' ],
			'logVars'      => [],
			'maxFileSize'  => 2048,         // in KB (2 MB)
			'maxLogFiles'  => 8,            // keep 5 rotated files
			'rotateByCopy' => false,       // optional, set true to copy instead of renaming
		] );
	}
}
