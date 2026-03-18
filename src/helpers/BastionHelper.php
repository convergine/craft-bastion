<?php

namespace convergine\craftbastion\helpers;

use craft\helpers\DateTimeHelper;
use yii\validators\EmailValidator;

class BastionHelper {
	/**
	 * @param string $recipients string contains emails list separated by comma
	 *
	 * @return array emails array
	 */
	public static function getRecipientsArray( string $recipients ): array {

		return array_values( array_unique( array_filter( array_map( function ( $el ) {
			$validator = new EmailValidator( [ 'enableIDN' => true, 'allowName' => true ] );
			$s         = trim( $el );
			if ( $s === '' ) {
				return null;
			}
			if ( preg_match( '/<([^>]+)>$/', $s, $m ) ) {
				$s = trim( $m[1] );
			}

			return $validator->validate( $s ) ? strtolower( $s ) : null;
		}, explode( ';', (string) $recipients ) ) ) ) );

	}

	/**
	 * @param \DateTime $dateTime DateTime object
	 * @param bool $inDays if true return only days, else return full duration
	 *
	 * @return string
	 */
	public static function getIntervalToDate( \DateTime $dateTime, bool $inDays = false ): string {
		$now = DateTimeHelper::now();

		$interval = $dateTime->diff( $now );
		if ( $inDays ) {
			return $interval->days . ' ' . \Craft::t( 'craft-bastion', 'days' );
		}

		return DateTimeHelper::humanDuration( $interval, false );

	}

	public static function isLocalDomain(string $url):bool {

		// Normalize url
		$url = strtolower(trim($url));

		// Extract host only (in case user passes full URL)
		$host = parse_url($url, PHP_URL_HOST) ?: $url;

		// Direct IP check — 127.0.0.1, ::1, or private network range
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			// Local IPv4 (127.*) or IPv6 (::1)
			if ($host === '127.0.0.1' || $host === '::1') {
				return true;
			}

			// Private IP ranges (RFC1918)
			$privateRanges = [
				['10.0.0.0', '10.255.255.255'],
				['172.16.0.0', '172.31.255.255'],
				['192.168.0.0', '192.168.255.255'],
			];

			foreach ($privateRanges as [$start, $end]) {
				if (ip2long($host) >= ip2long($start) && ip2long($host) <= ip2long($end)) {
					return true;
				}
			}

			return false;
		}

		// Known local/dev TLDs and suffixes
		$localSuffixes = [
			'.ddev',
			'.local',
			'.localhost',
			'.test',
			'.ddev.site',
			'.dev',
			'.lndo.site',
			'.docker',
			'.docker.local',
			'.internal',
			'.lan',
		];

		foreach ($localSuffixes as $suffix) {
			if (str_ends_with($host, $suffix)) {
				return true;
			}
		}

		return false;
	}

	private static ?string $cachedServer = null;

	public static function detectServer(): string
	{
		// Return cached result if available
		if (self::$cachedServer !== null) {
			return self::$cachedServer;
		}

		$software = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');

		$result = match (true) {
			str_contains($software, 'nginx') => 'nginx',
			str_contains($software, 'openlitespeed') => 'openlitespeed',
			str_contains($software, 'litespeed'), str_contains($software, 'lsws') => 'litespeed',
			str_contains($software, 'apache') => 'apache',
			str_contains($software, 'development server') => 'php-dev-server',
			default => 'unknown'
		};

		// Cache the result
		self::$cachedServer = $result;

		return $result;
	}

	public static function isBehindCloudflare(): bool
	{
		$cfHeaders = [
			'HTTP_CF_VISITOR',
			'HTTP_CF_RAY',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CF_IPCOUNTRY',
			'HTTP_CF_CACHE_STATUS'
		];

		foreach ($cfHeaders as $header) {
			if (!empty($_SERVER[$header])) {
				return true;
			}
		}

		return false;
	}
}