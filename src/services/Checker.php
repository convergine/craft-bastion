<?php

namespace convergine\craftbastion\services;

use craft\base\Component;
use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Checker extends Component {
	private $client;

	public function init(): void {
		parent::init();
		$this->client = new Client( [
			'timeout'         => 15,
			'allow_redirects' => true,
			'headers'         => [
				'User-Agent' => 'Mozilla/5.0 (compatible; IndexabilityChecker/1.0; +https://github.com)'
			]
		] );
	}

	public function isDev(): bool {
		return App::devMode();
	}

	public function allowCraftIndexing(): array {
		$disallowRobots = \Craft::$app->config->general->disallowRobots;
		$result         = [ 'result' => true, 'message' => '' ];

		if ( $disallowRobots === true ) {
			$result['result'] = false;

			$envDisallowRobots = App::env( 'CRAFT_DISALLOW_ROBOTS' );
			if ( $envDisallowRobots === true ) {
				$result['message'] .= "<code>CRAFT_DISALLOW_ROBOTS=true</code>";
				$result['message'] .= " in <code>.env</code> file.";
			} else {
				$result['message'] .= "<code>->disallowRobots(true)</code>";
				$result['message'] .= " in <code>config/general.php</code> file.";
			}
		}

		return $result;
	}

	public function allowLiveIndexing() {
		$result = ['res'=>true,'message'=>'','data'=>[]];
		$url        = Craft::$app->getSites()->getPrimarySite()->getBaseUrl();
		$checkIndex = $this->checkIndexability( $url );
		$result['data'] = $checkIndex;
		if ( ! isset( $checkIndex['error'] ) ) {
			if (
				$checkIndex['indexing_allowed'] === false ||
				$checkIndex['noindex'] === true
			) {
				$result['res'] = false;
				$messages =[];

				if($checkIndex['meta_robots']['noindex']){
					$messages[]= 'by meta tag `robots`';
				}
				if($checkIndex['x_robots_tag']['noindex']){
					$messages[]= 'by header `x-robots-tag`';
				}
				if(!$checkIndex['robots_txt']['allowed']){
					$messages[]= 'in robots.txt';
				}
				$result['message'] = "<ul class='warning'><li>".join('</li><li>',$messages)."</li></ul>";
			}
		}
		return $result ;
	}

	public function checkIndexability( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return [ 'error' => 'Invalid URL provided' ];
		}

		try {
			// Fetch the page content
			$response   = $this->client->get( $url );
			$html       = (string) $response->getBody();
			$statusCode = $response->getStatusCode();

			// Check if page returns 200 OK
			if ( $statusCode !== 200 ) {
				return $this->formatResult( false, "Page returns HTTP status: $statusCode" );
			}

			// Parse HTML for indexability signals
			$dom = new \DOMDocument();
			@$dom->loadHTML( $html );

			$results = [
				'robots_txt'       => $this->checkRobotsTxt( $url ),
				'meta_robots'      => $this->checkMetaRobots( $dom ),
				'x_robots_tag'     => $this->checkXRobsTag( $response->getHeaders() ),
				'canonical'        => $this->checkCanonical( $dom ),
				'noindex'          => false,
				'indexing_allowed' => true,
				'details'          => []
			];

			// Determine if indexing is allowed based on all checks
			if ( $results['meta_robots']['noindex'] ||
			     $results['x_robots_tag']['noindex'] ||
			     ! $results['robots_txt']['allowed'] ) {
				$results['noindex']          = true;
				$results['indexing_allowed'] = false;
			}

			// Add status code to results
			$results['status_code'] = $statusCode;

			return $results;

		} catch ( RequestException $e ) {
			Craft::error( 'Error checking indexability: ' . $e->getMessage(), __METHOD__ );

			return [ 'error' => 'Failed to retrieve URL: ' . $e->getMessage() ];
		}
	}

	/**
	 * Check robots.txt for blocking rules
	 */
	private function checkRobotsTxt( $url ) {
		$parsedUrl = parse_url( $url );
		$robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';

		try {
			$response      = $this->client->get( $robotsUrl );
			$robotsContent = (string) $response->getBody();

			// Check if all user-agents are disallowed
			if ( strpos( $robotsContent, 'User-agent: *\nDisallow: /' ) !== false ) {
				return [ 'allowed' => false, 'message' => 'Robots.txt blocks all crawlers' ];
			}

			// Check if there's a disallow rule for our path
			$path = $parsedUrl['path'] ?? '/';
			if ( ! empty( $path ) && $path !== '/' ) {
				$pattern = '/Disallow:\s*' . preg_quote( $path, '/' ) . '/';
				if ( preg_match( $pattern, $robotsContent ) ) {
					return [ 'allowed' => false, 'message' => 'Robots.txt disallows this path' ];
				}
			}

			return [ 'allowed' => true, 'message' => 'No blocking rules in robots.txt' ];

		} catch ( RequestException $e ) {
			// If robots.txt doesn't exist, that's fine - no restrictions
			if ( $e->getCode() === 404 ) {
				return [ 'allowed' => true, 'message' => 'No robots.txt found' ];
			}

			return [ 'allowed' => true, 'message' => 'Error fetching robots.txt: ' . $e->getMessage() ];
		}
	}

	/**
	 * Check meta robots tags
	 */
	private function checkMetaRobots( $dom ) {
		$metaTags = $dom->getElementsByTagName( 'meta' );
		$result   = [ 'noindex' => false, 'nofollow' => false ];

		foreach ( $metaTags as $tag ) {
			if ( strtolower( $tag->getAttribute( 'name' ) ) === 'robots' ) {
				$content = strtolower( $tag->getAttribute( 'content' ) );
				if ( strpos( $content, 'noindex' ) !== false ) {
					$result['noindex'] = true;
				}
				if ( strpos( $content, 'nofollow' ) !== false ) {
					$result['nofollow'] = true;
				}
				if ( strpos( $content, 'none' ) !== false ) {
					$result['nofollow'] = true;
					$result['noindex'] = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Check X-Robots-Tag HTTP headers
	 */
	private function checkXRobsTag( $headers ) {
		$result = [ 'noindex' => false, 'nofollow' => false ];

		foreach ( $headers as $name => $values ) {
			if ( strtolower( $name ) === 'x-robots-tag' ) {
				foreach ( $values as $value ) {
					$value = strtolower( $value );
					if ( strpos( $value, 'noindex' ) !== false ) {
						$result['noindex'] = true;
					}
					if ( strpos( $value, 'nofollow' ) !== false ) {
						$result['nofollow'] = true;
					}
					if ( strpos( $value, 'none' ) !== false ) {
						$result['noindex'] = true;
						$result['nofollow'] = true;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Check for canonical URL issues
	 */
	private function checkCanonical( $dom ) {
		$links      = $dom->getElementsByTagName( 'link' );
		$canonicals = [];

		foreach ( $links as $link ) {
			if ( strtolower( $link->getAttribute( 'rel' ) ) === 'canonical' ) {
				$canonicals[] = $link->getAttribute( 'href' );
			}
		}

		return [
			'count'         => count( $canonicals ),
			'urls'          => $canonicals,
			'has_canonical' => count( $canonicals ) > 0
		];
	}

	/**
	 * Format the result in a consistent way
	 */
	private function formatResult( $indexable, $message ) {
		return [
			'indexable' => $indexable,
			'message'   => $message,
			'timestamp' => time()
		];
	}

}
