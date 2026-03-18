<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\models\SettingsIpRestrict;
use craft\base\Component;

use Craft;
use craft\base\Model;
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

class Restrict extends Component {
	private string $_userIp;
	private SettingsIpRestrict $_settings;
	public function init():void {
		parent::init();
		$this->_userIp  = !Craft::$app->getRequest()->isConsoleRequest?Craft::$app->getRequest()->getUserIP():'';
		$this->_settings = BastionPlugin::getInstance()->settings_restrict;
	}

	/**
	 * @throws InvalidConfigException
	 * @throws ExitException
	 * @throws HttpException
	 * @throws InvalidRouteException
	 * @throws Exception
	 */
	public function restrict(): void {

		if(Craft::$app->getRequest()->getIsSiteRequest()) {
			$this->_processFront();
		}
		if(Craft::$app->getRequest()->getIsCpRequest()) {
			$this->_processCP();
		}
	}

	/**
	 * @throws InvalidConfigException
	 * @throws ExitException
	 * @throws HttpException
	 * @throws InvalidRouteException
	 * @throws Exception
	 */
	private function _processCP(): void {

		
		if ( $this->_settings->getIpEnabledCP() ) {

			if ( false === $this->ipAllowedForCp() ) {
				if($this->_settings->ipRestrictionMethodCp == $this->_settings::IP_METHOD_REDIRECT) {
					$redirect = $this->_settings->getIpRedirectCP();
					if(!empty($redirect)) {

						$selfURL = UrlHelper::siteUrl(Craft::$app->request->getUrl());
						$redirect = rtrim(UrlHelper::siteUrl($redirect),'/');
						if($selfURL == $redirect || Craft::$app->request->getPathInfo() == 'craft-bastion/ip-restrictions'){
							return;
						}
						BastionPlugin::addInfoLog($this->_userIp .' does not match whitelist for control panel, redirecting to '.$redirect);
						Craft::$app->response->redirect($redirect);
						Craft::$app->end();
					} else {
						BastionPlugin::addErrorLog($this->_userIp .' does not match whitelist for control panel but no redirect found, throwing exception');
						throw new HttpException(403, Craft::t('craft-bastion', 'Access Denied'));

					}
				}else {
					if ( $this->_settings->ipRestrictionMethodCp == $this->_settings::IP_METHOD_TEMPLATE ) {
						Craft::$app->view->setTemplateMode( View::TEMPLATE_MODE_SITE );
						$template = $this->_settings->getTemplateCP();
						if ( ! empty( $template ) ) {
							try {
								echo Craft::$app->view->renderTemplate( $template );
								BastionPlugin::addInfoLog( $this->_userIp . ' does not match whitelist for control panel, rendering template ' . $template );
							} catch ( Throwable ) {
								BastionPlugin::addErrorLog( $this->_userIp . ' does not match whitelist for control panel but error rendering template ' . $template . ', throwing exception' );
								throw new HttpException( 403, Craft::t( 'craft-bastion', 'accessDenied' ) );
							}
							Craft::$app->end();
						} else {
							BastionPlugin::addErrorLog( $this->_userIp . ' does not match whitelist for control panel but no template found, throwing exception' );
							throw new HttpException( 403, Craft::t( 'craft-bastion', 'accessDenied' ) );
						}
					} else {
						BastionPlugin::addErrorLog( $this->_userIp . ' does not match whitelist for control panel and no restriction method found, throwing exception' );
						throw new HttpException( 403, Craft::t( 'craft-bastion', 'accessDenied' ) );
					}
				}
			}
		}
		
	}

	/**
	 * @throws InvalidConfigException
	 * @throws ExitException
	 * @throws HttpException
	 * @throws InvalidRouteException
	 * @throws Exception
	 */
	private function _processFront(): void {

		if ( $this->_settings->getIpEnabled() ) {
			if ( false === $this->ipAllowedForFront() ) {
				if($this->_settings->ipRestrictionMethod == $this->_settings::IP_METHOD_REDIRECT) {
					$redirect = $this->_settings->getIpRedirect();
					if(!empty($redirect)) {

						$selfURL = UrlHelper::siteUrl(Craft::$app->request->getUrl());
						$redirect = rtrim(UrlHelper::siteUrl($redirect),'/');
						if($selfURL == $redirect){
							return;
						}
						BastionPlugin::addInfoLog($this->_userIp .' does not match whitelist for frontend, redirecting to '.$redirect);
						Craft::$app->response->redirect($redirect);
						Craft::$app->end();
					} else {
						BastionPlugin::addErrorLog($this->_userIp .' does not match whitelist for frontend but no redirect found, throwing exception');
						throw new HttpException(403, Craft::t('craft-bastion', 'Access Denied'));
					}
				}else
					if($this->_settings->ipRestrictionMethod == $this->_settings::IP_METHOD_TEMPLATE){
						Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
						$template = $this->_settings->getTemplate();
						if(!empty($template)) {
							try {
								echo Craft::$app->view->renderTemplate($template);
								BastionPlugin::addInfoLog($this->_userIp .' does not match whitelist for frontend, rendering template '.$template);
							} catch ( Throwable ) {
								BastionPlugin::addErrorLog($this->_userIp .' does not match whitelist for frontend but error rendering template '.$template.', throwing exception');
								throw new HttpException(403, Craft::t('craft-bastion', 'accessDenied'));
							}
							Craft::$app->end();
						} else {
							BastionPlugin::addErrorLog($this->_userIp .' does not match whitelist for frontend but no template found, throwing exception');
							throw new HttpException(403, Craft::t('craft-bastion', 'accessDenied'));
						}
					} else {
						BastionPlugin::addErrorLog($this->_userIp .' does not match whitelist for frontend and no restriction method found, throwing exception');
						throw new HttpException(403, Craft::t('craft-bastion', 'accessDenied'));
					}
			}
		}

	}

   public function ipAllowedForCp():bool {
	   if ( false !== $this->_checkIp( $this->_settings->ipWhitelistCp, $this->_userIp ) ) {
			return true;
	   }
		return false;
   }

	public function ipAllowedForFront():bool {

		if ( false !== $this->_checkIp( $this->_settings->ipWhitelist, $this->_userIp ) ) {
			return true;
		}
		return false;
	}
	private function _checkIp( $whitelist, $userIp ): bool {

		foreach ( $whitelist as $ip ) {
			$entry     = $ip[0];
			$userIpObj = IP::parse( $userIp );

			try {
				if ( strpos( $entry, '-' )  ) {
					// For IP ranges, check if the IP is in range
					return Range::parse($entry)->contains(new IP($userIp));
				}elseif ( strpos( $entry, '/' )  ) {
					// For CIDR ranges, check if the IP is in range
					$network = Network::parse( $entry );
					$firstIp = $network->getFirstIP();
					$lastIp  = $network->getLastIP();

					// Compare the binary representation of the IPs
					$contains = strcmp( $userIpObj->inAddr(), $firstIp->inAddr() ) >= 0 && strcmp( $userIpObj->inAddr(), $lastIp->inAddr() ) <= 0;

					if ( $contains ) {
						return true;
					}
				} else {
					// For single IPs, do an exact match
					$entryIp = IP::parse( $entry );
					$matches = $entryIp->inAddr() === $userIpObj->inAddr();

					if ( $matches ) {
						return true;
					}
				}
			} catch ( \Exception $e ) {
				// Invalid format, skip this entry
				BastionPlugin::addErrorLog( $e->getMessage() );
				continue;
			}
		}

		return false;
	}

}
