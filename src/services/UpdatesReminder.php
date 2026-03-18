<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\helpers\BastionHelper;
use craft\base\Component;
use Craft;
use craft\web\View;


class UpdatesReminder extends Component {

	public function getUpdates() {
		$updates = Craft::$app->getUpdates()->getUpdates( false );
		$cmsUpdates     = [];
		$pluginsUpdates = [];

		if ( $updates->cms->releases ) {
			foreach ( $updates->cms->releases as $release ) {
				$cmsUpdates[] = [
					'coreUpdateVersion' => $release->version,
					'coreReleaseDate'   => $release->date,
					'critical'          => $release->critical
				];
			}
		}

		foreach ( $updates->plugins as $handle => $plugin ) {
			$pluginData = Craft::$app->plugins->getPlugin( $handle );

			if ( $pluginData ) {
				if ( $plugin->releases ) {
					$latestRelease = $plugin->releases[0]->version;

					$pluginsUpdates[] = [
						'name'           => $pluginData->name,
						'currentVersion' => $pluginData->getVersion(),
						'latestVersion'  => $latestRelease
					];
				}
			}
		}

		return [ "cms" => $cmsUpdates, "plugins" => $pluginsUpdates ];
	}

	public function sendMails() {
		$updates = $this->getUpdates();
		if ( $updates['cms'] || $updates['plugins'] ) {

			$recipients = BastionHelper::getRecipientsArray( BastionPlugin::getInstance()->settings_updates_reminder->emailAddresses );

			$view    = Craft::$app->getView();
			$oldMode = $view->getTemplateMode();
			$view->setTemplateMode( View::TEMPLATE_MODE_CP );

			$mailData    = [
				'updates'=>$updates,
				'siteName'=>Craft::$app->getSites()->getPrimarySite()->getBaseUrl()
			];
			$mailSubject = "Your CraftCMS site updates";
			$mailBody    = Craft::$app->view->renderTemplate( 'craft-bastion/emails/updates_reminder', $mailData );
			if ( Craft::$app->getMailer()->compose()
			                ->setTo( $recipients )
			                ->setSubject( $mailSubject )
			                ->setHtmlBody( $mailBody )
			                ->send() ) {
				BastionPlugin::addInfoLog( 'Email send to:' . join( ', ', $recipients ) );
				return true;
			}
			$view->setTemplateMode( $oldMode );
		}

		return false;
	}
}