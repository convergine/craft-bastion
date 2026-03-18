<?php
namespace convergine\craftbastion\jobs;

use convergine\craftbastion\BastionPlugin;
use Craft;
use craft\queue\BaseJob;

class DailyReminderJob extends BaseJob
{
	/**
	 * @var int Number of attempts (default = 1)
	 */
	public int $attempt = 1;

	public function execute($queue): void
	{
		$settings = BastionPlugin::getInstance()->settings_main;
		$tz = new \DateTimeZone(Craft::$app->timeZone);
		$now = new \DateTime('now', $tz);
		$today = $now->format('Y-m-d');
		$lastRun = $settings->dailyReminderLastRun;

		$maxAttempts = 3;

		try {
			// Run only after 8:00 AM and only once per day
			if ($now->format('H') >= 8 && $lastRun !== $today) {
				BastionPlugin::addInfoLog("Running DailyReminderJob (attempt {$this->attempt}) for {$today}");
				BastionPlugin::getInstance()->reminder->run8AmJob();

				$settings->updateSetting('dailyReminderLastRun', $today);
				BastionPlugin::addInfoLog('DailyReminderJob completed successfully.');


				// Schedule for tomorrow
				$this->scheduleNextRun($now, $tz, true);
			} else {
				BastionPlugin::addInfoLog('DailyReminderJob skipped (not yet 8:00 AM or already executed today).');

				$this->scheduleNextRun($now, $tz, true);
			}
		} catch (\Throwable $e) {

			BastionPlugin::addErrorLog("DailyReminderJob failed: " . $e->getMessage());

			if ($this->attempt < $maxAttempts) {
				// Retry after 10 minutes
				$delay = 10 * 60;
				$nextJob = new self(['attempt' => $this->attempt + 1]);
				Craft::$app->queue->delay($delay)->push($nextJob);

				BastionPlugin::addInfoLog("Retry scheduled in 10 minutes (attempt " . ($this->attempt + 1) . ")");
			} else {
				// After 3 failed attempts — reschedule for tomorrow
				BastionPlugin::addErrorLog("All {$maxAttempts} attempts failed. Rescheduling for tomorrow.");
				$this->scheduleNextRun($now, $tz, false);
			}
		}
	}



	/**
	 * Schedules the next execution — today at 08:01 or tomorrow
	 */
	private function scheduleNextRun(\DateTime $now, \DateTimeZone $tz, bool $forceTomorrow): void
	{
		if (!$forceTomorrow && (int)$now->format('H') < 8) {
			$nextRun = new \DateTime('today 08:01', $tz);
		} else {
			$nextRun = new \DateTime('tomorrow 08:01', $tz);
		}

		$delaySeconds = $nextRun->getTimestamp() - $now->getTimestamp();
		BastionPlugin::addInfoLog("Scheduling next DailyReminderJob at " . $nextRun->format('Y-m-d H:i:s'));
		Craft::$app->queue->delay($delaySeconds)->push(new self(['description'=>'Scheduled to: '.$nextRun->format('Y-m-d H:i')]));
	}

	protected function defaultDescription(): string
	{
		return Craft::t('app', 'Daily Reminder Job (Attempt ' . $this->attempt . ')');
	}
}
