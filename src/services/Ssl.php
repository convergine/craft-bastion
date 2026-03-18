<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\models\SettingsSsl;
use craft\base\Component;
use Craft;
use craft\helpers\DateTimeHelper;
use craft\i18n\Locale;
use GuzzleHttp\Client;
use PleskX\Api\Exception;


class Ssl extends Component {
	private string $_endpoint = 'https://api.ssllabs.com/api/v3';
	private SettingsSsl $_settings;

	private string $_siteHost = '';
	private string $_siteUrl = '';

	private array $_responseData = [ 'action' => '', 'data' => '', 'errors' => [] ];

	public function init(): void {
		parent::init();
		$this->_settings = BastionPlugin::getInstance()->settings_ssl;
		$this->_siteUrl  = Craft::$app->getSites()->getPrimarySite()->getBaseUrl();
		$this->_siteHost = parse_url( $this->_siteUrl )['host'];
	}

	public function getSSLData() {

		return $this->_settings->sslLabsData;

	}

	public function fetchSSLData( $cache ) {

		$client = new Client( [
			'timeout' => 10, // seconds
		] );

		$url = $this->_endpoint . "/analyze?host={$this->_siteUrl}";
		if ( $cache ) {
			$url .= '&startNew=on';
		}

		try {
			$response = $client->get( $url );

			// Get response body as string
			$body = (string) $response->getBody();

			// If JSON:
			$data            = json_decode( $body, true );
			$data['summary'] = [
				'ssl_status' => 'active',// completed - scan completed; error - scan error
				'message'    => '<a href="#">What does my score mean?</a>',
				'grade'      => [ 'title' => 'grade', 'status' => 'process', 'details' => 'N/A' ],
				'info'       => [
					[ 'title' => 'tls', 'status' => 'process', 'details' => 'Protocol support...' ],
					[ 'title' => 'certificate', 'status' => 'process', 'details' => 'Certificate...' ],
					[ 'title' => 'hsts', 'status' => 'process', 'details' => 'HSTS...' ],
					[ 'title' => 'cipher', 'status' => 'process', 'details' => 'Cipher strength 0%' ],
					[ 'title' => 'expDate', 'status' => 'process', 'details' => 'Expired at...' ],
				],
				'lastCheck'  => 0,
				'progress'   => 0
			];


			if ( $data['status'] !== 'ERROR' ) {
				// get last endpoint
				if ( $data['status'] === 'READY' ) {
					$data['summary']['ssl_status'] = 'completed';
					$data['summary']['lastCheck']  = $data['startTime'];
					$data['summary']['progress']   = 100;
					if ( isset( $data['endpoints'] ) && is_array( $data['endpoints'] ) ) {
						$lastEndpointIpAddress = null;
						foreach ( $data['endpoints'] as $key => $endpoint ) {
							if ( $endpoint['statusMessage'] === 'Ready' ) {
								$lastEndpointIpAddress = $endpoint['ipAddress'];
							} elseif ( ! isset( $endpoint['grade'] ) ) {
								$data['summary']['message'] = $endpoint['statusMessage'];
								foreach ( $data['summary']['info'] as $k => $info ) {
									$data['summary']['info'][ $k ]['status'] = 'inactive';
								}
							}
						}

						if ( $lastEndpointIpAddress ) {
							$endpointData = $this->_fetchSSLEndpointData( $lastEndpointIpAddress );
							if (
								$endpointData
								&& isset( $endpointData['ipAddress'] )
								&& $endpointData['ipAddress'] === $lastEndpointIpAddress
								&& $endpointData['statusMessage'] === 'Ready'
							) {
								$data['endpointData'][ $lastEndpointIpAddress ] = $endpointData;
								$data['summary']['info']                        = $this->_processSSLResponse( $endpointData );
								$data['summary']['grade']                       = $this->_processGrade( $endpointData );
							}
						}
					}
				} else {
					$data['summary']['lastCheck'] = $data['startTime'];
					if ( isset( $data['endpoints'] ) && is_array( $data['endpoints'] ) ) {
						$completedEndpointsLength = 0;
						$activeEndpointProgress   = 0;
						foreach ( $data['endpoints'] as $key => $endpoint ) {
							$index = $key + 1;
							if ( ! isset( $endpoint['statusMessage'] ) && isset( $endpoint['statusDetailsMessage'] ) ) {
								$data['summary']['message'] = $endpoint['statusDetailsMessage'];
							} elseif ( $endpoint['statusMessage'] === 'In progress' && isset( $endpoint['statusDetailsMessage'] ) ) {
								$data['summary']['message'] = $endpoint['statusDetailsMessage'];
								$activeEndpointProgress     = $endpoint['progress'] ?? 0;
							} elseif ( $endpoint['statusMessage'] === 'Ready' ) {
								$completedEndpointsLength ++;
							}
						}
						$data['summary']['progress'] = ( $completedEndpointsLength * 100 + $activeEndpointProgress ) / count( $data['endpoints'] );
					}
				}

			} elseif ( $data['status'] === 'ERROR' ) {
				$errorMessage = $data['statusMessage'];
			}

			$this->_settings->updateSetting( 'sslLabsData', $data );

			$this->_responseData['data'] = $data;

			return $this->_responseData;
		} catch ( \GuzzleHttp\Exception\RequestException $e ) {
			Craft::error( 'HTTP request failed: ' . $e->getMessage(), __METHOD__ );
			$this->_responseData['errors'] = [ $e->getMessage() ];

			return $this->_responseData;
		}catch ( \GuzzleHttp\Exception\ConnectException $e ) {
			Craft::error( 'HTTP request failed: ' . $e->getMessage(), __METHOD__ );
			$this->_responseData['errors'] = [ $e->getMessage() ];

			return $this->_responseData;
		}
	}

	private function _fetchSSLEndpointData( string $endpointIpAddress ): array {

		$client = new Client( [
			'timeout' => 10, // seconds
		] );

		$url = $this->_endpoint . "/getEndpointData?host={$this->_siteHost}&s={$endpointIpAddress}";
		try {
			$response = $client->get( $url );

			// Get response body as string
			$body = (string) $response->getBody();

			// If JSON:
			$data = json_decode( $body, true );

			return $data;
		} catch ( \GuzzleHttp\Exception\RequestException $e ) {
			Craft::error( 'HTTP request failed: ' . $e->getMessage(), __METHOD__ );
			$this->_responseData['errors'] = [ $e->getMessage() ];
		}

		return [];
	}

	private function _processGrade( $endpointData ): array {
		$data = [ 'title' => 'grade', 'status' => 'process', 'details' => 'N/A' ];
		if ( isset( $endpointData['grade'] ) ) {
			$data['status']  = str_contains( $endpointData['grade'], 'A' ) ? 'success' : 'warning';
			$data['details'] = $endpointData['grade'];
		}

		return $data;
	}

	private function _processSSLResponse( $endpointData ): array {
		return [
			$this->_checkTlS11( $endpointData ),
			$this->_hasHSTS( $endpointData ),
			$this->_checkCertificate( $endpointData ),
			$this->_checkCipher( $endpointData ),
			$this->_getCertExpDate()
		];
	}
	public function _getCertExpDate(  ): array {
		$data = [ 'title' => 'expDate', 'status' => 'warning', 'details' => 'N/A' ];

		$expData = $this->getCertificateExpData(true);
		if($expData['res']){
			if($expData['data']['expDate'] > DateTimeHelper::now()){
				$interval = $expData['data']['expDate']->diff(DateTimeHelper::now());
				if($interval->days <30){
					$data['status']  = 'warning';
					$data['details'] = "Valid until: ".Craft::$app->getFormatter()->asDate($expData['data']['expDate'])." ($interval->days left)";
				}else{
					$data['status']  = 'success';
					$data['details'] = "Valid until: ".Craft::$app->getFormatter()->asDate($expData['data']['expDate']);
				}
			}else{
				$data['status']  = 'warning';
				$data['details'] = "Expired ". Craft::$app->getFormatter()->asDate($expData['data']['expDate']);
			}

		}

		return $data;
	}
	private function _checkTlS11( $endpointData ): array {
		$data = [ 'title' => 'tls', 'status' => 'success', 'details' => 'No TLS 1.1' ];

		foreach ( $endpointData['details']['protocols'] as $protocol ) {
			if ( $protocol['version'] === '1.1' ) {
				$data['status']  = 'warning';
				$data['details'] = 'Supports TLS 1.1';
			};
		}

		return $data;
	}

	private function _hasHSTS( $endpointData ): array {
		$data = [ 'title' => 'hsts', 'status' => 'success', 'details' => 'HSTS header detected' ];

		if ( isset( $endpointData['details']['hstsPolicy'] ) && $endpointData['details']['hstsPolicy']['status'] !== 'present' ) {
			$data['status']  = 'warning';
			$data['details'] = 'No HSTS header';
		}

		return $data;
	}

	private function _checkCertificate( $endpointData ): array {
		$data = [ 'title' => 'certificate', 'status' => 'success', 'details' => 'Valid certificate' ];

		if ( isset( $endpointData['details']['grade'] ) && str_contains( $endpointData['details']['grade'], 'A' ) ) {
			$data['status']  = 'warning';
			$data['details'] = 'Certificate issue';
		}

		return $data;
	}

	private function _checkCipher( $endpointData ): array {
		$data = [ 'title' => 'tls', 'status' => 'success', 'details' => 'Cipher strength 0%' ];

		$weakest   = 256;
		$strongest = 128;

		if ( ! empty( $endpoint['details']['suites'] ) ) {
			foreach ( $endpoint['details']['suites'] as $suite ) {
				if ( ! empty( $suite['list'] ) ) {
					foreach ( $suite['list'] as $cipher ) {
						$cipherStrength = $cipher['cipherStrength'] ?? 0;

						$weakest   = ( $cipherStrength < $weakest ) ? $cipherStrength : $weakest;
						$strongest = ( $cipherStrength > $strongest ) ? $cipherStrength : $strongest;
					}
				}
			}
		}


		$rating          = ( $this->_getCypherRating( $weakest ) + $this->_getCypherRating( $strongest ) ) / 2;
		$rating          = round( $rating );
		$data['status']  = ( $rating > 70 ) ? 'success' : 'warning';
		$data['details'] = "Cipher strength {$rating}%";

		return $data;
	}

	private function _getCypherRating( int $strength ): int {
		if ( $strength === 0 ) {
			return 0;
		} elseif ( $strength < 128 ) {
			return 20;
		} elseif ( $strength < 256 ) {
			return 80;
		} else {
			return 100;
		}
	}

	/**
	 * @param bool $force if true, skipping getting data from cache and get remote data
	 *
	 *@return array [ 'res' => false, 'data' => ['subject','issuer','expDate'], 'errors' => []]
	 */
	public function getCertificateExpData( bool $force = false ): array {
		$response = [ 'res' => false, 'data' => [], 'errors' => [] ];
		if (
			false !== ( $lastCheck = DateTimeHelper::toDateTime( $this->_settings->sslCertificateDataCheck ) )
			&& $this->_settings->sslCertificateData
			&& $force === false
		) {

			if ( $lastCheck->format( 'Y-m-d' ) === DateTimeHelper::now()->format( 'Y-m-d' ) ) {
				$response['res']  = true;
				$response['data'] = $this->_settings->sslCertificateData;
				if ( is_array( $response['data']['expDate'] ) ) {
					$response['data']['expDate'] = DateTimeHelper::toDateTime( $response['data']['expDate'] );
				}

				return $response;
			}
		}

		$ch       = curl_init( $this->_siteUrl );

		curl_setopt_array( $ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CERTINFO       => true,
			CURLOPT_NOBODY         => true,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true
		] );

		curl_exec( $ch );

		$certInfo  = curl_getinfo( $ch, CURLINFO_CERTINFO );
		$curlError = curl_error( $ch );

		unset( $ch );

		if ( $curlError ) {

			$response['errors'] = [ 'CURL error: ' . $curlError ];
		}

		if ( ! empty( $certInfo ) && is_array( $certInfo ) && isset( $certInfo[0] ) ) {

			$expireDate = \DateTime::createFromFormat( 'M d H:i:s Y T', $certInfo[0]['Expire date'], new \DateTimeZone( 'GMT' ) );
			if ( $expireDate ) {
				$localTz = new \DateTimeZone( Craft::$app->getTimeZone() );

				$response['data']['subject'] = $certInfo[0]['Subject'];
				$response['data']['issuer']  = $certInfo[0]['Issuer'];
				$response['data']['expDate'] = $expireDate->setTimezone( $localTz );
				$response['res']             = true;

				$this->_settings->updateSetting( 'sslCertificateDataCheck', DateTimeHelper::now() );
				$this->_settings->updateSetting( 'sslCertificateData', $response['data'] );
			} else {
				$response['errors'] = [ Craft::t( 'craft-bastion', 'Couldn\'t parse expiration date' ) ];
			}


		} else {

			$response['errors'] = [ Craft::t( 'craft-bastion', 'Couldn\'t get information about the certificate' ) ];
		}

		return $response;
	}


	public function sslExpAfter7Days( \DateTime|null $expDate = null ): bool {

		if ( ! $expDate ) {
			$expData = $this->getCertificateExpData( true );
			if ( $expData['res'] ) {
				$expDate = $expData['data']['expDate'];
			} else {
				return false;
			}
		}
		$diff = $expDate->diff( DateTimeHelper::now() );
		BastionPlugin::addInfoLog("Certificate expired after {$diff->days} days");
		if ( $diff->days == 7 ) {
			return true;
		}

		return false;
	}

	public function sslExpAfter24Hours( \DateTime|null $expDate = null ): bool|int {
		if ( ! $expDate ) {
			$expData = $this->getCertificateExpData( true );
			if ( $expData['res'] ) {
				$expDate = $expData['data']['expDate'];
			} else {
				return false;
			}
		}

		$hoursLeft = round( ( $expDate->getTimestamp() - DateTimeHelper::now()->getTimestamp() ) / 3600 );
		BastionPlugin::addInfoLog("Certificate expired after {$hoursLeft} hours");
		if ( $hoursLeft <= 24 ) {
			return $hoursLeft;
		}

		return false;
	}

}
