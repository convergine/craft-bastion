<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\helpers\BastionHelper;
use convergine\craftbastion\models\SettingsDomain;
use convergine\craftbastion\models\SettingsSsl;
use craft\base\Component;
use Craft;
use craft\helpers\DateTimeHelper;
use craft\i18n\Locale;
use GuzzleHttp\Client;
//use PleskX\Api\Exception;


class Domain extends Component {
	private string $_endpoint = 'https://rdap.org/domain';
	private SettingsDomain $_settings;

	private string $_siteHost = '';
	private string $_siteUrl = '';

	public function init(): void {
		parent::init();
		$this->_settings = BastionPlugin::getInstance()->settings_domain;

		$this->_siteUrl  = Craft::$app->getSites()->getPrimarySite()->getBaseUrl();
		$this->_siteHost = BastionHelper::isLocalDomain($this->_siteUrl)?parse_url($this->_siteUrl)['host']:$this->_getDomain($this->_siteUrl);
	}

	/**
	 * Return registrable domain (eTLD+1) from a URL/host string.
	 * - Strips subdomains (foo.bar.example.co.uk -> example.co.uk)
	 * - Skips IPs and localhost-like hosts (returns null)
	 * - Handles IDN (punycode) using intl if available
	 *
	 * @param string $input URL or host
	 * @param bool   $decodeIdn Return UTF-8 if true, punycode if false
	 * @return string|null
	 */
	public function _getDomain(string $input, bool $decodeIdn = true): ?string
	{
		$s = trim($input);

		// If it's a URL without scheme, prepend '//' so parse_url detects host
		if (!preg_match('~^[a-z][a-z0-9+\-.]*://~i', $s) && !str_starts_with($s, '//')) {
			$s = '//' . $s;
		}
		$parts = parse_url($s);
		$host  = $parts['host'] ?? '';

		if ($host === '' ) return null;

		// IPv6 in brackets?
		if ($host[0] === '[') return null;

		// Normalize case and strip trailing dot
		$host = rtrim(strtolower($host), '.');

		// Filter out IPv4
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return null;
		}

		// Convert to punycode ASCII for consistent splitting
		if (function_exists('idn_to_ascii')) {
			$ascii = idn_to_ascii($host, 0, INTL_IDNA_VARIANT_UTS46);
			if ($ascii !== false) {
				$host = $ascii;
			}
		}

		// Split labels
		$labels = explode('.', $host);
		if (count($labels) < 2) {
			return null; // not a FQDN
		}

		// Heuristic for common multi-level ccTLDs: *.co.uk, *.com.au, *.com.br, etc.
		// If TLD is 2 letters AND the previous label is in this list, treat TLD as two-part.
		$secondLevelCc = [
			'co','com','net','org','gov','edu','ac','mil','nom','ne','or','id','adm','asso'
		];

		$last = count($labels) - 1;
		$tld  = $labels[$last];

		// If last label looks like a 2-letter ccTLD
		if (strlen($tld) === 2 && $last >= 2 && in_array($labels[$last - 1], $secondLevelCc, true)) {
			// e.g. a.b.example.co.uk -> example.co.uk
			$result = $labels[$last - 2] . '.' . $labels[$last - 1] . '.' . $labels[$last];
		} else {
			// default: SLD + TLD
			$result = $labels[$last - 1] . '.' . $labels[$last];
		}

		// Optionally decode back to UTF-8 for display
		if ($decodeIdn && function_exists('idn_to_utf8')) {
			$decoded = idn_to_utf8($result, 0, INTL_IDNA_VARIANT_UTS46);
			if ($decoded !== false) {
				$result = $decoded;
			}
		}

		return $result;
	}

	public function getExpData(bool $force = true): array {
		$result = [
			'isNeverRun' => false,
			'isLocal'    => false,
			'remainData' => $this->getRemainData(),
			'domain'     => $this->_siteHost
		];
		if ( BastionHelper::isLocalDomain( $this->_siteUrl ) ) {
			$result['isLocal'] = true;

			return $result;
		}
		$dataInSettings = $force?$this->_settings->domainExpData:$this->fetchDomainData();
		if ( ! $dataInSettings ) {
			$result['isNeverRun'] = true;

			return $result;
		}
		$result['remainData'] = $this->getRemainData($dataInSettings['expDate']);
		$dataInSettings['expDate'] = $dataInSettings['expDate'] !== null?
			Craft::$app->getFormatter()->asDate(DateTimeHelper::toDateTime($dataInSettings['expDate'])):null;

		return array_merge( $dataInSettings, $result );
	}

	public function fetchDomainData() {
		BastionPlugin::addInfoLog( '[START] Fetching domain data' );
		$result = [
			'res'            => false,
			'domain'         => $this->_siteHost,
			'expDate'        => null,
			'registrant'     => '',
			'registrantLink' => '',
			'data'           => [],
			'errors'         => []
		];
		if ( BastionHelper::isLocalDomain( $this->_siteUrl) ) {
			$result['errors'][] = Craft::t( 'craft-bastion', "Local development domain detected - can't run check" );
			BastionPlugin::addErrorLog( "Local development domain detected - can't run check" );
		} else {
			$client = new Client( [
				'timeout' => 10, // seconds
			] );


			$url = $this->_endpoint . "/{$this->_siteHost}";
			try {
				$response = $client->get( $url );

				// Get response body as string
				$body = (string) $response->getBody();

				// If JSON:
				$data           = json_decode( $body, true );
				$result['data'] = $data;

				$expDate = null;
				BastionPlugin::addInfoLog( 'Fetching domain data successfully' );
				if ( isset( $data['events'] ) ) {
					foreach ( $data['events'] as $event ) {
						if ( $event['eventAction'] == 'expiration' ) {
							$expDate           = (array) new \DateTime( $event['eventDate'] );
							$result['expDate'] = $expDate;
							$result['res']     = true;
						}
					}
					if ( isset( $data['entities'][0] ) ) {
						$result['registrantLink'] = '';
						if ( isset( $data['entities'][0]['links'] ) ) {
							foreach ($data['entities'][0]['links'] as $registrar){
								if(isset($registrar['rel']) && $registrar['rel'] =='about'){
									$result['registrantLink'] = $registrar['href'];
									break;
								}
							}
						}
						if ( isset( $data['entities'][0]['vcardArray'] ) ) {
							$vcard                = $data['entities'][0]['vcardArray'];
							$result['registrant'] = $vcard[1][1][3] ?? '';
						}
					}
				}

			} catch ( \GuzzleHttp\Exception\RequestException $e ) {
				BastionPlugin::addErrorLog( 'HTTP request failed: ' . $e->getMessage() );
				if ( $e->getCode() == '404' ) {
					if ( false !== $responseData = json_decode( $e->getResponse()->getBody()->getContents() ) ) {
						$result['data'] = $responseData;
						BastionPlugin::addErrorLog( $responseData );
						$result['errors'][] = $responseData->title ?? 'Data not found';
					} else {
						$result['data']     = $e->getMessage();
						$result['errors'][] = 'Data not found';
					}

				}
			}
		}
		BastionPlugin::addInfoLog( '[END] Fetching domain data' );
		$this->_settings->updateSetting( 'domainExpData', $result );
		$this->_settings->updateSetting( 'domainExpDataLastCheck', DateTimeHelper::now() );

		return $result;
	}

	public function getRemainData( null|array|\DateTime $expDate = null ): array {
		$result  = [ 'days' => 'n/a', 'status' => '' ];
		$expDate = is_array( $expDate ) ? DateTimeHelper::toDateTime( $expDate ) : $expDate;
		if ( ! $expDate instanceof \DateTime ) {
			return $result;
		}
		$diff   = DateTimeHelper::now()->diff( $expDate );
		$days   = $diff->days;
		$result = [ 'days' => $days, 'status' => 'success' ];
		if ( $days < 30 ) {
			$result['status'] = 'error';
		} elseif ( $days < 90 ) {
			$result['status'] = 'warning';
		}

		return $result;
	}
}
