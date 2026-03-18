<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\helpers\BastionHelper;
use convergine\craftbastion\helpers\UpdatesReminderHelper;
use convergine\craftbastion\jobs\DailyReminderJob;
use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\web\View;
use yii\validators\EmailValidator;

class Reminder extends Component{
	private string $_siteHost = '';
	private string $_siteUrl = '';
	public function init():void {
		parent::init();
		$this->_siteUrl  = Craft::$app->getSites()->getPrimarySite()->getBaseUrl();
		$this->_siteHost = parse_url( $this->_siteUrl )['host'];
	}
	/**
	 * Main task logic (your custom code goes here)
	 */
	public function run8AmJob(): void {
		BastionPlugin::addInfoLog('[START] 8AM Job');

		$this->_sendSSLReminders();
		$this->_sendDomainReminders();
		$this->_sendDiskSpaceUsageReminder();
		$this->_sendUpdatesReminder();


		BastionPlugin::addInfoLog('[END] 8AM Job');
	}

	private function _sendSSLReminders() {
		$sslSettings = BastionPlugin::getInstance()->settings_ssl;

		// check if reminders enabled
		if ( $sslSettings->enableCertificateReminder ) {
			BastionPlugin::addInfoLog('Certificate reminders ENABLED');
			BastionPlugin::addInfoLog('[START] certificate reminders');
			$recipients = BastionHelper::getRecipientsArray($sslSettings->certificateReminderRecipients);

			$certificateSSLExpData = BastionPlugin::getInstance()->ssl->getCertificateExpData( true );
			if ( $certificateSSLExpData['res'] ) {
				$expiryDate = $certificateSSLExpData['data']['expDate'] ;
				$view    = Craft::$app->getView();
				$oldMode = $view->getTemplateMode();
				$view->setTemplateMode( View::TEMPLATE_MODE_CP );

				BastionPlugin::addInfoLog('Certificate expDate='.$expiryDate->format("Y-m-d"));

				$mailData = [
					'domain'=>$this->_siteHost,
					'expiryDate'=>$expiryDate,
					'hoursLeft'=>0
				];
				if (BastionPlugin::getInstance()->ssl->sslExpAfter7Days($expiryDate ) ) {

					BastionPlugin::addInfoLog('START sending email to:'.join(', ',$recipients));
					$mailSubject = Craft::t('craft-bastion','ssl7dayReminderEmailSubject',
						[
							'domain'=>$this->_siteHost,
						]
					);
					$mailBody    = Craft::$app->view->renderTemplate( 'craft-bastion/emails/ssl_7_day_reminder',$mailData );

					if(Craft::$app->getMailer()->compose()
					              ->setTo( $recipients )
					              ->setSubject( $mailSubject )
					              ->setHtmlBody( $mailBody )
					              ->send()){
						BastionPlugin::addInfoLog('Email send to:'.join(', ',$recipients));
					}

				}
				if ( false !== $hoursLeft = BastionPlugin::getInstance()->ssl->sslExpAfter24Hours( $expiryDate ) ) {

					BastionPlugin::addInfoLog("sslExpAfter $hoursLeft hours");
					BastionPlugin::addInfoLog('START sending email to:'.join(', ',$recipients));

					$mailData['hoursLeft']=$hoursLeft;
					$mailSubject = Craft::t('craft-bastion','ssl24HoursReminderEmailSubject',['domain'=>$this->_siteHost]);
					$mailBody    = Craft::$app->view->renderTemplate( 'craft-bastion/emails/ssl_24_hours_reminder', $mailData );

					if(Craft::$app->getMailer()->compose()
					              ->setTo( $recipients )
					              ->setSubject( $mailSubject )
					              ->setHtmlBody( $mailBody )
					              ->send()){
						BastionPlugin::addInfoLog('Email send to:'.join(', ',$recipients));
					}

				}
				$view->setTemplateMode( $oldMode );
			}else{
				BastionPlugin::addErrorLog('Can\'t get certificate info');
				BastionPlugin::addErrorLog($certificateSSLExpData);
			}

			BastionPlugin::addInfoLog('[END] certificate reminders');
		}else{
			BastionPlugin::addInfoLog('Certificate reminders DISABLED');
		}
	}

	private function _sendDomainReminders() {
		$domainSettings = BastionPlugin::getInstance()->settings_domain;

		// check if reminders enabled
		if ( $domainSettings->enableDomainReminder ) {
			BastionPlugin::addInfoLog('Domain reminders ENABLED');
			BastionPlugin::addInfoLog('[START] domain reminders');
			$recipients = BastionHelper::getRecipientsArray($domainSettings->domainReminderRecipients);

			$domainExpExpData = BastionPlugin::getInstance()->domain->getExpData(false);
			//BastionPlugin::addInfoLog($domainExpExpData);
			if (
				isset($domainExpExpData['remainData']['days'])
				&& $domainExpExpData['remainData']['days'] !='n/a'
			)
			{
				$remainDays = $domainExpExpData['remainData']['days'];
				$view    = Craft::$app->getView();
				$oldMode = $view->getTemplateMode();
				$view->setTemplateMode( View::TEMPLATE_MODE_CP );

				BastionPlugin::addInfoLog('Domain expDate='.$domainExpExpData['expDate']);
				BastionPlugin::addInfoLog("domainExpAfter $remainDays days");

				$mailData = [
					'domain'=>$this->_siteHost,
					'expiryDate'=>$domainExpExpData['expDate'],
					'daysLeft'=>0
				];
				if ($remainDays == 30 ) {

					BastionPlugin::addInfoLog('START sending email to:'.join(', ',$recipients));
					$mailSubject = Craft::t('craft-bastion','domain30dayReminderEmailSubject',
						[
							'domain'=>$this->_siteHost,
						]
					);
					$mailBody    = Craft::$app->view->renderTemplate( 'craft-bastion/emails/domain_30_day_reminder',$mailData );

					if(Craft::$app->getMailer()->compose()
					              ->setTo( $recipients )
					              ->setSubject( $mailSubject )
					              ->setHtmlBody( $mailBody )
					              ->send()){
						BastionPlugin::addInfoLog('Email send to:'.join(', ',$recipients));
					}

				}
				if ( $remainDays == 7 ) {

					BastionPlugin::addInfoLog('START sending email to:'.join(', ',$recipients));

					$mailData['daysLeft']=$remainDays;
					$mailSubject = Craft::t('craft-bastion','domain7dayReminderEmailSubject',['domain'=>$this->_siteHost]);
					$mailBody    = Craft::$app->view->renderTemplate( 'craft-bastion/emails/domain_7_days_reminder', $mailData );

					if(Craft::$app->getMailer()->compose()
					              ->setTo( $recipients )
					              ->setSubject( $mailSubject )
					              ->setHtmlBody( $mailBody )
					              ->send()){
						BastionPlugin::addInfoLog('Email send to:'.join(', ',$recipients));
					}

				}
				$view->setTemplateMode( $oldMode );
			}else{
				BastionPlugin::addErrorLog('Can\'t get domain info');
				BastionPlugin::addErrorLog($domainExpExpData);
			}

			BastionPlugin::addInfoLog('[END] domain reminders');
		}else{
			BastionPlugin::addInfoLog('Domain reminders DISABLED');
		}
	}

	private function _sendDiskSpaceUsageReminder()
	{
		$diskSpaceUsageSettings = BastionPlugin::getInstance()->settings_disk_space;

		// check if reminders enabled
		if ($diskSpaceUsageSettings->enableDiskSpaceReminder) {
			BastionPlugin::addInfoLog('Disk space usage reminders ENABLED');
			BastionPlugin::addInfoLog('[START] disk space usage reminders');
			$recipients = BastionHelper::getRecipientsArray($diskSpaceUsageSettings->diskSpaceReminderRecipients);
			$diskSpaceSettingsThreshold = $diskSpaceUsageSettings->diskSpaceThreshold;
			$diskSpaceUsageData = BastionPlugin::getInstance()->disk_space_usage->getDiskSpaceData();
			$diskSpaceUsedPercentage = $diskSpaceUsageData['percentage'];

			// check the current disk space usage threshold that exceeds the one set in the settings.
			if ($diskSpaceUsedPercentage > 0 && $diskSpaceUsedPercentage >= $diskSpaceSettingsThreshold) {
				BastionPlugin::addInfoLog("Disk space usage: {$diskSpaceUsedPercentage}% (threshold: {$diskSpaceSettingsThreshold}%)");
				BastionPlugin::addInfoLog('START sending email to:'.join(', ',$recipients));

				$view    = Craft::$app->getView();
				$oldMode = $view->getTemplateMode();
				$view->setTemplateMode( View::TEMPLATE_MODE_CP );

				$mailData = [
					'domain' => $this->_siteHost,
					'diskSpaceUsedPercentage' => $diskSpaceUsedPercentage,
					'threshold' => $diskSpaceSettingsThreshold
				];

				$mailSubject = Craft::t('craft-bastion','diskSpaceUsageEmailSubject',[
					'domain' => $this->_siteHost,
					'percentage' => $diskSpaceUsedPercentage
				]);
				$mailBody = Craft::$app->view->renderTemplate( 'craft-bastion/emails/disk_space_usage_reminder', $mailData );

				if(Craft::$app->getMailer()->compose()
					->setTo( $recipients )
					->setSubject( $mailSubject )
					->setHtmlBody( $mailBody )
					->send()){
					BastionPlugin::addInfoLog('Email send to:'.join(', ',$recipients));
				}

				$view->setTemplateMode( $oldMode );
			} else {
				BastionPlugin::addInfoLog("Disk space usage: {$diskSpaceUsedPercentage}% (below threshold: {$diskSpaceSettingsThreshold}%)");
			}

			BastionPlugin::addInfoLog('[END] disk space usage reminders');
		} else {
			BastionPlugin::addInfoLog('Disk space usage reminders DISABLED');
		}
	}

	/**
	 * Checks if DailyReminderJob exists in the queue.
	 * If it was manually deleted, automatically re-creates it.
	 * The check runs at most once every 10 minutes (cache-controlled).
	 */
	public function checkAndRestoreReminderJob(): void {
		$cache = Craft::$app->getCache();

		// Avoid checking too often — run once every 10 minutes
		if ( $cache->get( 'reminder_watchdog_checked' ) ) {
			return;
		}

		$cache->set( 'reminder_watchdog_checked', true, 600 ); // 600 sec = 10 min

		$queue = Craft::$app->getQueue();
		$tz    = new \DateTimeZone( Craft::$app->timeZone );
		$now   = new \DateTime( 'now', $tz );

		// Check if a DailyReminderJob already exists in queue
		$jobExists = ( new Query() )
			->from( '{{%queue}}' )
			->where( [ 'like', 'job', DailyReminderJob::class ] )
			->andWhere(['fail' => 0])
			->exists();

		if ( ! $jobExists ) {
			Craft::warning( '[Reminder Watchdog] No DailyReminderJob found. Rescheduling…', __METHOD__ );

			// Schedule new job: today at 08:01 if before 8 AM, otherwise tomorrow 08:01
			if ( (int) $now->format( 'H' ) < 8 ) {
				$nextRun = new \DateTime( 'today 08:01', $tz );
			} else {
				$nextRun = new \DateTime( 'tomorrow 08:01', $tz );
			}


			$delay = $nextRun->getTimestamp() - $now->getTimestamp();

			$queue->delay( $delay )->push( new DailyReminderJob( [ 'description' => 'Scheduled to: ' . $nextRun->format( 'Y-m-d H:i' ) ] ) );

			Craft::info(
				'[Reminder Watchdog] New DailyReminderJob scheduled for ' .
				$nextRun->format( 'Y-m-d H:i:s' ) .
				' (' . $tz->getName() . ')',
				__METHOD__
			);
		}
	}

	private function _sendUpdatesReminder() {
		$settings = BastionPlugin::getInstance()->settings_updates_reminder;
		$frequency        = $settings->frequency;
		$dayOfWeek        = $settings->notifyDayOfWeek;
		$lastReminderDate = $settings->lastReminderDate;

		if($settings->updatesEnabled) {
			BastionPlugin::addInfoLog('START _sendUpdatesReminder');
			BastionPlugin::addInfoLog( 'Frequency: ' . $frequency );
			BastionPlugin::addInfoLog( 'Day Of Week: ' . $dayOfWeek );
			if ( UpdatesReminderHelper::isScheduleDue( $frequency, $dayOfWeek, $lastReminderDate ) ) {

				if ( BastionPlugin::getInstance()->updates_reminder->sendMails() ) {
					BastionPlugin::addInfoLog( 'Reminder was send' );
				}
				$settings->updateSetting( 'lastReminderDate', DateTimeHelper::now()->format( "Y-m-d" ) );
			} elseif ( $lastReminderDate == '' ) {
				$settings->updateSetting( 'lastReminderDate', DateTimeHelper::now()->format( "Y-m-d" ) );
			}
			BastionPlugin::addInfoLog('END _sendUpdatesReminder');
		}else{
			BastionPlugin::addInfoLog('Updates Reminder disabled');
		}

	}
}