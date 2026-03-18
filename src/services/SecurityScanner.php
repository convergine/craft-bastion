<?php

namespace convergine\craftbastion\services;

use convergine\craftbastion\BastionPlugin;
use convergine\craftbastion\models\SecurityTestModel;
use convergine\craftbastion\records\ScannerRecord;
use craft\base\Component;

use Craft;
use craft\base\Plugin;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\Updates;
use GuzzleHttp\Client;
use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\TransferStats;
use yii\web\NotFoundHttpException;

class SecurityScanner extends Component {

	/**
	 * @var Client|null
	 */
	public ?Client $client = null;

	/**
	 * @var string|null
	 */
	public ?string $siteUrl = null;

	/**
	 * @var array
	 */
	public array $siteUrlResponse = [];

	/**
	 * @var Updates|null
	 */
	public ?Updates $updates = null;

	public function getTestNames() {

		$settings = BastionPlugin::getInstance()->settings_security_scanner;
		$mainTests = $settings->mainTests;

		$additionalTests = $settings->getSelectedAdditionalTests();

		return $mainTests + $additionalTests;

	}

	public function beforeScan(): void {
		$config = [ 'timeout' => 10 ];

		$this->client = Craft::createGuzzleClient( $config );

		// Get updates, forcing a refresh

		$this->updates = Craft::$app->getUpdates()->getUpdates( true );

		// Get the current site’s base URL
		$this->siteUrl = $this->siteUrl ?? Craft::$app->getSites()->getPrimarySite()->getBaseUrl();

		try {
			$response = $this->client->get($this->siteUrl);
			$this->siteUrlResponse['headers'] = $response->getHeaders();
			$this->siteUrlResponse['body'] = $response->getBody()->getContents();
		} catch (GuzzleException $exception) {
			$message = Craft::t('craft-bastion', 'Unable to connect to "{url}". Please ensure that the site is reachable and that the system is turned on.', ['url' => $this->siteUrl]);

			BastionPlugin::addErrorLog($message);
			BastionPlugin::addErrorLog($exception->getMessage());

			throw new NotFoundHttpException($message);
		}
	}

	public function processScan() {

		$this->beforeScan();
		$results   = [];
		$hasFailed = 0;
		$hasWarnings = 0;
		$testNames = $this->getTestNames();
		foreach ( $testNames as $test=>$testName ) {

			switch ( $test ) {
				case "criticalCraftUpdates":

					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','No critical Craft updates available'),
						'info'=>Craft::t('craft-bastion','Critical Craft updates should be given immediate attention as soon as they are released as they can contain important security fixes. Not doing so puts your site at serious risk.
					
            If, for whatever reason, it is not possible to update the site, then you should at least make yourself aware of the gravity of the vulnerability that the updates addressed and the potential consequences of not applying the fixes.'),
						'link'=>['label'=>Craft::t('craft-bastion','Changelog'),'url'=>'https://github.com/craftcms/cms/blob/5.x/CHANGELOG.md']
					]);

					if ( $this->updates->cms->getHasCritical() ) {
						$criticalCraftUpdates = [];
						foreach ( $this->updates->cms->releases as $release ) {
							if ( $release->critical ) {
								$criticalCraftUpdates[] = Html::a(
										$release->version,
										'https://github.com/craftcms/cms/blob/5.x/CHANGELOG.md#' . str_replace( '.', '-', $release->version )
									) . Html::tag(
										'span',
										Craft::t('craft-bastion','Version {version} is a critical update, released on {date}.',[
											'version'=>$release->version,
											'date'=>$this->formatDate( $release->date )
										]),
										[ 'class' => 'info' ]
									);
							}
						}

						$testModel->description = Craft::t('craft-bastion','Apply critical Craft updates: ') . implode( ' , ', $criticalCraftUpdates );
						$testModel->status = 'failed';
						$testModel->fixUrl = $this->_getCmsUpdatesLink();

						$hasFailed++;
					}
					$results[] = $testModel->toArray();
					break;

				case "criticalPluginUpdates":

					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','No critical plugin updates available'),
						'info'=>Craft::t('craft-bastion','Critical plugin updates should be given immediate attention as soon as they are released as they can contain important security fixes. Not doing so puts your site at serious risk.

            If, for whatever reason, it is not possible to update the site, then you should at least make yourself aware of the gravity of the vulnerability that the updates addressed and the potential consequences of not applying the fixes.'),
					]);

					$criticalPluginUpdates = [];

					if (!empty($this->updates->plugins)) {
						foreach ($this->updates->plugins as $handle => $update) {
							if ($update->getHasCritical()) {
								/** @var Plugin $plugin */
								$plugin = Craft::$app->getPlugins()->getPlugin($handle);

								foreach ($update->releases as $release) {
									if ($release->critical) {
										$criticalPluginUpdates[] = Html::a(
												$plugin->name,
												$plugin->changelogUrl,
												['target' => '_blank'],
											) . Html::tag(
												'span',
												Craft::t('craft-bastion','Version {version} is a critical plugin update, released on {date}.',[
													'version'=>$release->version,
													'date'=>$this->formatDate( $release->date )
												]),
												['class' => 'info']
											);
									}
								}
							}
						}

					}

					if (!empty($criticalPluginUpdates)) {


						$testModel->description = Craft::t('craft-bastion','Apply critical plugin updates: ') . implode( ' , ', $criticalPluginUpdates );
						$testModel->status = 'failed';
						$testModel->fixUrl = $this->_getCmsUpdatesLink();
						$hasFailed++;
					}
					$results[] = $testModel->toArray();
					break;

				case "craftUpdates":

					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','No Craft updates available'),
						'info'=>Craft::t('craft-bastion','Craft updates can contain security enhancements and bug fixes that are not deemed critical, so it is nevertheless recommended to apply them whenever possible.'),
						'link'=>['label'=>Craft::t('craft-bastion','Changelog'),'url'=>'https://github.com/craftcms/cms/blob/5.x/CHANGELOG.md']
					]);

					if ($this->updates->cms->getHasReleases()) {

						$testModel->description = Craft::t('craft-bastion','Your version of Craft is behind the latest version');
						$testModel->status = 'warning';
						$testModel->fixUrl = $this->_getCmsUpdatesLink();
						$hasWarnings++;
					}
					$results[] = $testModel->toArray();
					break;

				case 'pluginUpdates':

					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','No plugin updates available'),
						'info'=>Craft::t('craft-bastion','Plugin updates can contain security enhancements and bug fixes that are not deemed critical, so it is nevertheless recommended to apply them whenever possible.'),
					]);
					$pluginUpdates = [];

					if (!empty($this->updates->plugins)) {
						foreach ($this->updates->plugins as $handle => $update) {
							if (!empty($update->releases)) {
								$latestRelease = $update->getLatest();

								/** @var Plugin $plugin */
								$plugin = Craft::$app->getPlugins()->getPlugin($handle);

								if ($plugin !== null) {
									$pluginUpdates[] = Html::a(
											$plugin->name,
											$plugin->changelogUrl,
											['target' => '_blank'],
										) . Html::tag(
											'span',
											Craft::t('craft-bastion','Local version {localVersion} is {count} release{plural} behind latest version {latestVersion}, released on {date}.',[
												'localVersion'=>$plugin->version,
												'count'=>count($update->releases),
												'plural'=>(count($update->releases) != 1 ? 's' : ''),
												'latestVersion'=>$latestRelease->version,
												'date'=>$this->formatDate($latestRelease->date)
											]),
											['class' => 'info result']
										);
								}
							}
						}
					}

					if (!empty($pluginUpdates)) {

						$testModel->description = Craft::t('craft-bastion','Update plugins to latest versions: ') . implode(' , ', $pluginUpdates);
						$testModel->status = 'warning';
						$testModel->fixUrl= $this->_getCmsUpdatesLink();
						$hasWarnings++;
					}
					$results[] = $testModel->toArray();
					break;

				case 'httpsControlPanel':

					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Control panel is forcing an encrypted HTTPS connection'),
						'info'=>Craft::t('craft-bastion','Using an SSL certificate and forcing the control panel to use an encrypted HTTPS connection ensures secure authentication of users and protects site data. This is especially important since some of your users may be admins or have elevated privileges.

            SSL certificates have become very affordable and straightforward to install, so there is no excuse for not using one. A HTTPS connection will not only secure all communications between users and your site, it will also make your site appear more trustworthy to browsers and search engines.'),
					]);

					$url = trim($this->siteUrl, '/') . '/' . Craft::$app->getConfig()->getGeneral()->cpTrigger;
					
					if (!$this->redirectsToHttps($url)) {

						$testModel->description = Craft::t('craft-bastion','Force an encrypted HTTPS connection on front-end');
						$testModel->status = 'failed';
						$hasFailed++;
					}
					$results[] = $testModel->toArray();
					break;

				case 'httpsFrontEnd':

					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Front-end site is forcing an encrypted HTTPS connection'),
						'info'=>Craft::t('craft-bastion','Using an SSL certificate and forcing the front-end of your site to use an encrypted HTTPS connection protects the site and user data. This is especially important if public user registration is allowed, as you may be collecting and storing personal data.

            SSL certificates have become very affordable and straightforward to install, so there is no excuse for not using one. A HTTPS connection will not only secure all communications between users and your site, it will also make your site appear more trustworthy to browsers and search engines.'),
					]);
					if (!$this->redirectsToHttps($this->siteUrl)) {

						$testModel->description = Craft::t('craft-bastion','Force an encrypted HTTPS connection on front-end');
						$testModel->status = 'failed';
						$hasFailed++;
					}
					$results[] = $testModel->toArray();
					break;


				case "webAliasInVolumeBaseUrl":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Dynamic @web alias not used in volume base URL'),
						'info'=>Craft::t('craft-bastion','When using the @web alias in your asset volume\'s base URL, it should be explicitly defined rather than dynamically generated. Leaving it dynamic could introduce a cache poisoning vulnerability, and Craft won\'t be able to reliably determine which volume is being requested. This can result in issues when running console commands.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/configure.html']
					]);
					if (Craft::$app->getRequest()->isWebAliasSetDynamically) {
						$volumes = Craft::$app->getVolumes()->getAllVolumes();
						$volumesFailed = [];

						foreach ($volumes as $volume) {
							$filesystem = $volume->getFs();
							if ($filesystem->hasUrls && str_contains($filesystem->url, '@web')) {
								$volumesFailed[] = $volume->name;
							}
						}

						if (!empty($volumesFailed)) {

							$testModel->status = 'failed';
							$testModel->description = Craft::t('craft-bastion','Dynamic @web alias is used in volume base URL: {volumes}',['volumes'=>implode(' , ', $volumesFailed)]);
							$testModel->fixUrl= $this->_getCmsFilesystemLink();
							$hasFailed++;
						}
					}
					$results[] = $testModel->toArray();
					break;

				case "devMode":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Dev mode is disabled'),
						'info'=>Craft::t('craft-bastion','Dev mode is intended for testing and debugging your site and outputs performance related data on the front-end. It should never be enabled in production environments.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/configure.html#env']
					]);
					if (Craft::$app->getConfig()->getGeneral()->devMode) {
						$testModel->status = 'failed';
						$testModel->description = Craft::t('craft-bastion','Disable dev mode');
						$hasFailed++;
					}
					$results[] = $testModel->toArray();
					break;


				case "siteIndexing":
				
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Search Engine Visibility ENABLED'),
						'info'=>Craft::t('craft-bastion','Site indexing by Search Engine.'),
					]);
					$siteIndexing = BastionPlugin::getInstance()->checker->allowCraftIndexing();
					if($siteIndexing['result']=== false){
						$testModel->description = Craft::t('craft-bastion','Site Search Engine indexing DISABLED by&nbsp;<span class="warning">{message}</span>',['message'=>$siteIndexing['message']]);
						$testModel->status = 'failed';
						$testModel->fixText = Craft::t('craft-bastion','Please edit your .env file and set CRAFT_DISALLOW_ROBOTS to false');
						$hasFailed++;
					}
					$results[] = $testModel->toArray();
					break;

				case "frontendRestrictedByIp":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Frontend access not restricted (by IP)'),
						'info'=>Craft::t('craft-bastion','Restrict frontend access by IP address'),
					]);
					if(BastionPlugin::getInstance()->settings_restrict->getIpEnabled()){
						$testModel->description=Craft::t('craft-bastion','Frontend access restricted (by IP)');
						$testModel->status='warning';
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/ip-restrictions');
						$hasWarnings++;
					}
					$results[] = $testModel->toArray();
					break;

				case "botDefence":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Bot Defence ENABLED'),
						'info'=>Craft::t('craft-bastion','We recommend activating Bot Defence to block unwanted bots directly at the server level, reducing load and improving security.'),
					]);
					$serverInfo = BastionPlugin::getInstance()->bot_defence->getServerInfo();
					if($serverInfo['canProtect']){
							if(!BastionPlugin::getInstance()->bot_defence->hasBastionBotDefense()){
								$testModel->description = Craft::t('craft-bastion','Bot Defence DISABLED');
								$testModel->status = 'warning';
								$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/bot-defence');
								$hasWarnings++;
							}
						$results[] = $testModel->toArray();
					}

					break;

				case "contentSecurityPolicy":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Bot Defence ENABLED'),
						'info'=>Craft::t('craft-bastion','A Content Security Policy (CSP) is an added layer of security that helps to detect and mitigate certain types of attacks, including cross-site scripting (XSS) and data injection attacks. These attacks are used for everything from data theft to site defacement.

            By enforcing a Content Security Policy, you explicitly tell the browser which types of resources (images, scripts, styles, etc.) it is allowed to load, where it may load them from (your site, external sites, etc.) and whether inline scripts and styles may be processed. Setting it up takes some time and should be done with care, but results in a very effective method of mitigating and reporting attacks.

            CraftBastion can help you create a Content Security Policy by allowing you to set the directives, switch from reporting to enforcing when you are ready and choose to implement it using a HTTP header or meta tag.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP']
					]);

					$value = $this->getHeaderValue('Content-Security-Policy');
					$headerSet = !empty($value);

					if (!$headerSet) {
						// Look for meta tag
						preg_match('/<meta http-equiv="Content-Security-Policy" content="(.*?)"/si', $this->siteUrlResponse['body'], $matches);
						$value = $matches[1] ?? '';
					}

					if (empty($value)) {

						$testModel->description = Craft::t('craft-bastion','Neither {header} header nor meta tag are set',['header'=>'Content-Security-Policy']);
						$testModel->status = 'warning';
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp');
						$hasWarnings++;
					} else {
						$testModel->description = Craft::t('craft-bastion','{header} {type} ',['header'=>'Content-Security-Policy','type'=>($headerSet ? 'header' : 'meta tag')]);

						if (str_contains($value, 'unsafe-inline') || str_contains($value, 'unsafe-eval')) {
							$testModel->status = 'warning';
							$testModel->description .= Craft::t('craft-bastion','contains "unsafe" values');
							$hasWarnings++;
						} else {
							$testModel->description .= Craft::t('craft-bastion','is set');
						}
					}
					$results[] = $testModel->toArray();
					break;

				case "cors":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Access to other sites is not granted'),
						'info'=>Craft::t('craft-bastion','Cross-Origin Resource Sharing (CORS) is an HTTP-header based mechanism that allows a server to indicate any other origins (domain, scheme, or port) than its own from which a browser should permit loading of resources. CORS also relies on a mechanism by which browsers make a preflight request to the server hosting the cross-origin resource, in order to check that the server will permit the actual request. In that preflight, the browser sends headers that indicate the HTTP method and headers that will be used in the actual request.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS']
					]);
					$value = $this->getHeaderValue('Access-Control-Allow-Origin');

					if ($value) {
						if ($value == '*') {
							$testModel->status= 'failed';
							$hasFailed++;
						} else {
							$testModel->status= 'warning';
							$hasWarnings++;
						}

						$testModel->description = '"' . $value . '"';
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
					}

					$results[] = $testModel->toArray();
					break;


				case "referrerPolicy":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Referrer-Policy header is set to'),
						'info'=>Craft::t('craft-bastion','The Referrer-Policy header controls how much referrer information (sent via the Referer header) should be included with requests.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy']
					]);
					$value = $this->getHeaderValue('Referrer-Policy');

					if (empty($value)) {

						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->description = Craft::t('craft-bastion','Set the {header} header',['header'=>'Referrer-Policy']);
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
					} else {
						$testModel->description .= '"' . $value . '"';
					}
					$results[] = $testModel->toArray();
					break;

				case "strictTransportSecurity":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Strict-Transport-Security header is set to'),
						'info'=>Craft::t('craft-bastion','The Strict-Transport-Security header (often abbreviated as HSTS) lets a web site tell browsers that it should only be accessed using HTTPS, instead of using HTTP. Setting this to 15552000 or more will ensure that browsers remember this setting for at least 6 months.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security']
					]);
					$value = $this->getHeaderValue('Strict-Transport-Security');

					if (empty($value)) {
						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->description = Craft::t('craft-bastion','Set {header} header to "{value}"',['header'=>'Strict-Transport-Security', 'value'=>'max-age=15552000']);
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
					} else {
						$seconds = (int)explode('=', $value)[1] ?? 0;

						if ($seconds < 15552000) {// 6 months
							$testModel->description .= ' "max-age=' . $seconds . '"';
							$testModel->status = 'warning';
							$hasWarnings++;
							$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
						} else {
							$testModel->description .= '"' . $value . '"';
						}
					}
					$results[] = $testModel->toArray();
					break;

				case "xContentTypeOptions":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','X-Content-Type-Options header is set to'),
						'info'=>Craft::t('craft-bastion','The X-Content-Type-Options header is a marker used by the server to indicate that the MIME types advertised in the Content-Type headers should not be changed and be followed. This is a way to opt out of MIME type sniffing, or, in other words, to say that the MIME types are deliberately configured.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options']
					]);
					$value = $this->getHeaderValue('X-Content-Type-Options');

					if ($value != 'nosniff') {
						$testModel->description = Craft::t('craft-bastion','Set {header} header to "{value}"',['header'=>'X-Content-Type-Options', 'value'=>'nosniff']);
						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
					} else {
						$testModel->description .= ' "' . $value . '"';
					}
					$results[] = $testModel->toArray();
					break;

				case "xFrameOptions":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','X-Frame-Options header is set to'),
						'info'=>Craft::t('craft-bastion','The X-Frame-Options header can be used to indicate whether or not a browser should be allowed to render a page in a <frame>, <iframe>, <embed> or <object>. Sites can use this to avoid click-jacking attacks, by ensuring that their content is not embedded into other sites.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options']
					]);
					$value = $this->getHeaderValue('X-Frame-Options');

					if ($value != 'DENY' && $value != 'SAMEORIGIN') {
						$testModel->description = Craft::t('craft-bastion','Set {header} header to "{value1}" or "{value2}"',['header'=>'X-Frame-Options', 'value1'=>'DENY', 'value2'=>'SAMEORIGIN']);
						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
					} else {
						$testModel->description .= ' "' . $value . '"';
					}

					$results[] = $testModel->toArray();
					break;

				case "xXssProtection":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','X-XSS-Protection header is set to'),
						'info'=>Craft::t('craft-bastion','The X-XSS-Protection header is a feature of Internet Explorer, Chrome and Safari that stops pages from loading when they detect reflected cross-site scripting (XSS) attacks. Although these protections are largely unnecessary in modern browsers when sites implement a strong Content Security Policy that disables the use of inline JavaScript, they can still provide protections for users of older web browsers that don\'t yet support CSP.'),
						'link'=>['label'=>Craft::t('craft-bastion','MDN Article'),'url'=>'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection']
					]);
					$value = $this->getHeaderValue('X-Xss-Protection');

					// If not set then check alternative case
					$value = $value ?: $this->getHeaderValue('X-XSS-Protection');

					// Remove spaces and convert to lower case for comparison
					$compareValue = strtolower(str_replace(' ', '', $value));

					if ($compareValue != '1;mode=block') {
						$testModel->description = Craft::t('craft-bastion','Set {header} header to "{value}"',['header'=>'X-XSS-Protection', 'value'=>'1; mode=block']);
						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->fixUrl = UrlHelper::cpUrl('craft-bastion/csp#protection');
					} else {
						$testModel->description .= '"' . $value . '"';
					}
					$results[] = $testModel->toArray();
					break;

				case "preventUserEnumeration":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Prevent user enumeration is enabled'),
						'info'=>Craft::t('craft-bastion','When true, Craft will always return a successful response in the forgot password flow, making it difficult to enumerate users. When set to false and you go through the forgot password flow from the control panel login page, you\'ll get distinct messages indicating whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to log in but allow for username/email enumeration based on the response.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/reference/config/general.html#preventuserenumeration']
					]);
					if (!Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->description = Craft::t('craft-bastion','Set prevent user enumeration to true');
					}

					$results[] = $testModel->toArray();
					break;

				case "sendPoweredByHeader":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Powered By header is disabled'),
						'info'=>Craft::t('craft-bastion','Whether an `X-Powered-By` header should be sent, helping services like BuiltWith and Wappalyzer identify that the site is running on Craft, but which can also be abused by website scrapers looking to identify Craft sites.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/reference/config/general.html#sendpoweredbyheader']
					]);
					if (Craft::$app->getConfig()->getGeneral()->sendPoweredByHeader) {
						$testModel->status = 'warning';
						$hasWarnings++;
						$testModel->description = Craft::t('craft-bastion','Set the powered by header to false');
					}

					$results[] = $testModel->toArray();
					break;

				case "craftFilePermissions":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Craft file permissions are correctly set'),
						'info'=>Craft::t('craft-bastion','The file permissions of the files that Craft must be able to write to. Set these to at most 0664, which will grant read and write for the owner and group, read for everyone else.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/requirements.html#file-permissions']
					]);
					$paths = [
						'.env' => Craft::getAlias('@root/.env'),
						'.env.php' => Craft::getAlias('@root/.env.php'),
						'composer.json' => Craft::getAlias('@root/composer.json'),
						'composer.lock' => Craft::getAlias('@root/composer.lock'),
						'config/license.key' => Craft::getAlias('@config/license.key'),
					];

					$pathsFailed = $this->getPathsWritableByEveryone($paths);

					if (!empty($pathsFailed)) {
						$testModel->status = 'failed';
						$hasFailed++;
						$testModel->description = Craft::t('craft-bastion','Craft file permissions are not correctly set: {paths}',['paths'=>implode(', ', array_keys($pathsFailed))]);
					}
					$results[] = $testModel->toArray();
					break;

				case "craftFolderPermissions":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Craft folder permissions are correctly set'),
						'info'=>Craft::t('craft-bastion','The folder permissions of the folders that Craft must be able to write to. Set these to at most 0775, which will grant everything for the owner and group, read and execute for everyone else.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/requirements.html#file-permissions']
					]);
					$paths = [
						'config/project' => Craft::getAlias('@config/project'),
						'storage' => Craft::getAlias('@storage'),
						'vendor' => Craft::getAlias('@vendor'),
						'webroot/cpresources' => Craft::getAlias('@webroot/cpresources'),
					];

					$pathsFailed = $this->getPathsWritableByEveryone($paths);

					if (!empty($pathsFailed)) {
						$testModel->status = 'failed';
						$hasFailed++;
						$testModel->description = Craft::t('craft-bastion','Craft folder permissions are not correctly set: {paths}',['paths'=>implode(', ', array_keys($pathsFailed))]);
					}
					$results[] = $testModel->toArray();
					break;

				case "craftFoldersAboveWebRoot":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Craft folders are located above the web root'),
						'info'=>Craft::t('craft-bastion','Keeping the Craft folders above the web root ensures that no one can access any of their files directly.'),
						'link'=>['label'=>Craft::t('craft-bastion','Craft Docs'),'url'=>'https://craftcms.com/docs/5.x/system/directory-structure.html']
					]);
					$paths = [
						'root' => Craft::getAlias('@root'),
						'config' => Craft::getAlias('@config'),
						'storage' => Craft::getAlias('@storage'),
						'templates' => Craft::getAlias('@templates'),
					];
					$pathsFailed = [];

					$webroot = Craft::getAlias('@webroot');

					foreach ($paths as $key => $path) {
						// If the webroot is a substring of the path
						if (str_contains($path, $webroot)) {
							$pathsFailed[] = $key;
						}
					}

					if (!empty($pathsFailed)) {
						$testModel->status = 'failed';
						$hasFailed++;
						$testModel->description = Craft::t('craft-bastion','Craft folders not located above the web root: {paths}',['paths'=>implode(', ', $pathsFailed)]);
					}

					$results[] = $testModel->toArray();
					break;

				case "adminUsername":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','No admin account with username "admin" exists'),
						'info'=>Craft::t('craft-bastion','Since admin accounts have the highest privileges in Craft, it is better to avoid having any with the username "admin", which is easily guessable. This makes it harder for potential attackers to target admin user accounts.'),

					]);
					$user = Craft::$app->getUsers()->getUserByUsernameOrEmail('admin');

					if ($user && $user->admin) {
						$testModel->status='warning';
						$hasWarnings++;
						$testModel->description = Craft::t('craft-bastion','An admin account with username "admin" exists');

					}
					$results[] = $testModel->toArray();
					break;

				case "phpVersion":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Site is running on a supported PHP version'),
						'info'=>Craft::t('craft-bastion','Each release branch of PHP is fully supported for two years from its initial stable release, followed by two additional years for critical security issues only. After this time the branch reaches its end of life and is no longer supported.

            • PHP 8.2 receives security support until 31 December 2026.
            • PHP 8.3 receives security support until 31 December 2027.

            Running a version of PHP that is no longer receiving security support may put your site at serious risk.'),
						'link'=>['label'=>Craft::t('craft-bastion','Supported Versions'),'url'=>'https://www.php.net/supported-versions.php']
					]);
					$version = PHP_VERSION;
					$value = substr($version, 0, 3);
					$testModel->description = Craft::t('craft-bastion','Site is running on a supported PHP version {version}',['version'=>$version]);
					$versions = [
						'8.2' => '2027-01-01',
						'8.3' => '2028-01-01',
					];
					if (isset($versions[$value])) {
						if (strtotime($versions[$value]) < time()) {
							$testModel->status = 'warning';
							$hasWarnings++;
							$testModel->description = Craft::t('craft-bastion','Site is running on an unsupported PHP version {version}',['version'=>$version]);
						}
					}


					$results[] = $testModel->toArray();
					break;

				case "phpComposerVersion":
					$testModel = new SecurityTestModel([
						'name'=>$testName,
						'description'=>Craft::t('craft-bastion','Composer is using the correct PHP version'),
						'info'=>Craft::t('craft-bastion','Craft adds a PHP platform requirement (config → platform → php) to your project\'s composer.json file when it is installed. You should change this version to reflect that used on your production web server. Doing so will ensure that the latest versions of dependencies will be used, according to their PHP version requirements.'),
						'link'=>['label'=>Craft::t('craft-bastion','Composer Docs'),'url'=>'https://getcomposer.org/doc/06-config.md#platform']
					]);
					$version = PHP_VERSION;
					$json = json_decode(file_get_contents(Craft::getAlias('@root/composer.json')));
					$requiredVersion = $json->config->platform->php ?? null;

					if (empty($requiredVersion)) {
						break;
					}

					$versionParts = explode('.', $version);
					$versionMinor = $versionParts[0] . '.' . $versionParts[1];
					$requiredVersionParts = explode('.', $requiredVersion);
					$requiredVersionMinor = $requiredVersionParts[0] . '.' . $requiredVersionParts[1];

					// Only compare minor version
					if (version_compare($requiredVersionMinor, $versionMinor, '<')) {
						$testModel->status='warning';
						$hasWarnings++;
						$testModel->description = Craft::t('craft-bastion','Set the PHP version in composer.json to {version}',['version'=>PHP_VERSION]);
					}

					$results[] = $testModel->toArray();
					break;
			}

		}
		$scanRecord          = new ScannerRecord();
		$scanRecord->siteId  = Craft::$app->getSites()->getPrimarySite()->id;
		$scanRecord->pass    = $hasFailed == 0;
		$scanRecord->warning = $hasWarnings > 0;
		$scanRecord->results = json_encode( $results );
		$scanRecord->save();

		return true;
	}

	public function getLastScan(int|null $siteId = null) {
		$siteId = $siteId ?: Craft::$app->getSites()->getPrimarySite()->id;
		$scanRecord = ScannerRecord::find()
		                         ->where(['siteId' => $siteId])
		                         ->orderBy(['dateCreated' => SORT_DESC])
		                         ->one();
		return $scanRecord;
	}

	private function _getCmsUpdatesLink():string {
		return UrlHelper::cpUrl('utilities/updates');
	}

	private function _getCmsFilesystemLink():string {
		return UrlHelper::cpUrl('settings/filesystems');
	}
	private function formatDate( DateTime|int|string|null $date ): string {
		return Craft::$app->getFormatter()->asDate( $date, 'long' );
	}
	private function redirectsToHttps(string $url): bool
	{
		/** @noinspection HttpUrlsUsage */
		$url = str_replace('https://', 'http://', $url);
		$scheme = null;

		try {
			// Get redirect URL scheme of insecure URL
			$this->client->get($url, [
				'on_stats' => function(TransferStats $stats) use (&$scheme) {
					$scheme = $stats->getEffectiveUri()->getScheme();
				},
			]);
			if ($scheme != 'https') {
				return false;
			}
		} catch (GuzzleException) {
			// An error indicates that insecure requests are blocked, so allow to pass
		}

		return true;
	}

	/**
	 * Returns a header value.
	 */
	private function getHeaderValue(string $name): string
	{
		// Use lower-case name if it exists in the header
		if (!empty($this->siteUrlResponse['headers'][strtolower($name)])) {
			$name = strtolower($name);
		}

		$value = $this->siteUrlResponse['headers'][$name] ?? '';

		if (is_array($value)) {
			$value = $value[0] ?? '';
		}

		// URL decode and strip tags to make it safe to output raw
		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$value = strip_tags(urldecode($value));

		return $value;
	}

	/**
	 * Returns paths that are writable by everyone.
	 *
	 * @param array $paths
	 * @return array
	 */
	private function getPathsWritableByEveryone(array $paths): array
	{
		$writablePaths = [];

		foreach ($paths as $key => $path) {
			// If the path exists and is writable by everyone
			if ((is_file($path) || is_dir($path)) && substr(decoct(fileperms($path)), -1) >= 6) {
				$writablePaths[$key] = $path;
			}
		}

		return $writablePaths;
	}
}
