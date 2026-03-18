<?php

namespace convergine\craftbastion\helpers;

use DateTime;
use Craft;

class UpdatesReminderHelper {
	/**
	 * Check if schedule is due
	 *
	 * @param string $frequency ('Daily','Weekly','Bi-weekly','Monthly')
	 * @param string $dayOfWeek ('Monday','Tuesday', ...)
	 * @param string|DateTime|null $lastDateRun
	 *
	 * @return bool
	 */
	public static function isScheduleDue( string $frequency, string $dayOfWeek, $lastDateRun = null ): bool {
		$today     = new DateTime( 'today' );
		$todayName = $today->format( 'l' );

		// Normalize last run date
		if ( $lastDateRun instanceof DateTime ) {
			$last = clone $lastDateRun;
		} elseif ( is_string( $lastDateRun ) && ! empty( $lastDateRun ) ) {
			$last = new DateTime( $lastDateRun );
		} else {
			// If never run — allow run only on scheduled weekday (except Daily)
			if ( $frequency === 'Daily' ) {
				return true;
			}

			return $todayName === $dayOfWeek;
		}

		// DAILY — always true
		if ( $frequency === 'Daily' ) {
			return true;
		}

		// WEEKLY
		if ( $frequency === 'Weekly' ) {
			return $todayName === $dayOfWeek;
		}

		// BI-WEEKLY (14 days)
		if ( $frequency === 'Bi-weekly' ) {
			$diffDays = (int) $today->diff( $last )->days;

			return $diffDays >= 14 && $todayName === $dayOfWeek;
		}

		// MONTHLY (>=30 days)
		if ( $frequency === 'Monthly' ) {
			$diffDays = (int) $today->diff( $last )->days;

			return $diffDays >= 30 && $todayName === $dayOfWeek;
		}

		return false;
	}
}