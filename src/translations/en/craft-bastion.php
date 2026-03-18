<?php
/**
 * Craft Bastion English Translation
 *
 * Returns an array with the string to be translated (as passed to `Craft::t('craft-bastion', '...')`) as
 * the key, and the translation as the value.
 *
 * http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html
 *
 */
return [


	// Messages
	'Invalid settings type'=>'Invalid settings type',
	'Settings saved'=>'Settings saved',
	'Settings NOT saved.'=>'Settings NOT saved.',
	'Rules added.'=>'Rules added.',
	'Rules removed.'=>'Rules removed.',
	'Failed to modify .htaccess'=>'Failed to modify .htaccess',

	// js messages
	'Re-running security checks'=>'Re-running security checks',
	'Failed to run scan. Please try again.'=>'Failed to run scan. Please try again.',
	'Scan failed'=>'Scan failed',
	'Processing...'=>'Processing...',


	// CSP
	'Content Security Policy (CSP) Settings'=>'Content Security Policy (CSP) Settings',
	'cspDescription'=>'Content Security Policy (CSP) adds an extra layer of security to your website by controlling which scripts, styles, and other content the browser is allowed to load. This helps prevent malicious code injection (like cross-site scripting).<br>',
	'CSP Rules'=>'CSP Rules',
	'Add a policy'=>'Add a policy',
	'Additional Security Headers'=>'Additional Security Headers',
	'Enable Content Security Policy (CSP)'=>'Enable Content Security Policy (CSP)',
	'enableCSPDescription'=>'When enabled, your website will include a security header that tells browsers which sources of content are trusted. This helps prevent unauthorized scripts or code from running on your site.',
	'cspModeDescription'=>'<div>Choose how the Content Security Policy (CSP) is applied to your site.<ul>
        <li><b>Response Headers</b>: Adds CSP directly to HTTP response headers — the most secure and recommended option.</li>
        <li><b>Meta Tags</b>: Adds CSP using <meta> tags inside your site’s HTML. Useful if you can’t modify server headers.</li>
        <li><b>Report Only</b>: Sends violation reports without blocking content, allowing you to test your CSP safely before enforcing it.</li></ul></div>',
	'Enable Additional Header Protections'=>'Enable Additional Header Protections',
	'enableAdditionalHeaderProtectionsDescription'=>'Adds extra HTTP security headers (like X-Frame-Options and X-Content-Type-Options) to protect against common web attacks. Recommended for all production websites.',
	'Apply Recommended Baseline Policy'=>'Apply Recommended Baseline Policy',
	'applyRecommendedBaselinePolicyDescription'=>'Quickly set up a default, safe-to-use CSP that allows typical site assets (images, CSS, and scripts from your domain). You can fine-tune this later under the “CSP Rules” tab.',
	'Are you sure to apply Recommended Baseline Policy?'=>'Are you sure to apply Recommended Baseline Policy?',
	'The recommended basic policy has been saved.'=>'The recommended basic policy has been saved.',
	'Could’t set recommended basic policy.'=>'Could’t set recommended basic policy.',
	'policySettingsDescription'=>'<p><b>Define which sources of content are trusted on your website.</b></p>
        <p>Content Security Policy (CSP) rules tell browsers where scripts, styles, images, and other assets are allowed to load from.
            Use this section to fine-tune your site’s CSP configuration. Each directive (like default-src or script-src) controls a specific type of content. For example:</p>

    <p><code>default-src</code> defines the default allowed sources for all content types.</p>
    <p><code>script-src</code> controls where JavaScript can be loaded from.</p>
    <p><code>style-src</code> controls allowed sources for CSS.</p>
    <p>Click <b>+ Add a policy</b> to include additional domains (e.g. a CDN or analytics provider).</p>',
	'extraHeadersSettingsDescription'=>'<p><b>Add or customize extra HTTP response headers that strengthen your site’s security.</b></p>
    <p>These headers help browsers block risky behaviors, enforce HTTPS connections, and prevent data leaks.</p>
    <p>Here’s what each common header does:</p>
    <p><code>Referrer-Policy</code>: Controls how much referrer information is shared when a visitor clicks a link.</p>
    <p><code>Strict-Transport-Security (HSTS)</code>: Forces browsers to use HTTPS for all future visits.</p>
    <p><code>X-Content-Type-Options</code>: Prevents browsers from interpreting files as a different type (helps stop attacks).</p>
    <p><code>X-Frame-Options</code>: Stops your site from being embedded in other pages (protects against clickjacking).</p>
    <p><code>X-XSS-Protection</code>: Enables built-in browser protection against cross-site scripting.</p>
    <p>Click <b>+ Add a header</b> to define additional response headers or modify existing ones.</p>',

	//Bot Defence
	'Bot Defence'=>'Bot Defence',

	'botDefenceCanBeEnabledNoCloudflare'=>'<p>Your server is running <b>{server}</b>, and Bot Defence can be fully enabled.</p>
 <p>We recommend activating Bot Defence to block unwanted bots directly at the server level, reducing load and improving security.</p>
 <p>For even stronger protection, we also suggest using Cloudflare as your DNS provider — the free plan already adds valuable filtering and edge-layer protection.</p>',

	'botDefenceCanBeEnabledIsCloudflare'=>'<p>We’ve detected that your site is using <b>Cloudflare</b> and your server is running Apache.</p>
 <p>Even with Cloudflare enabled, many automated bots still reach your origin server.</p>
 <p>We recommend enabling Bot Defence, which will add optimized security rules to your <code>.htaccess</code> file to block common scrapers, scanners, and malicious user-agents before they reach CraftCMS.</p>',

	'botDefenceCanNOTBeEnabled'=>'<p>Your server is not running Apache or LiteSpeed servers, so <code>.htaccess</code> - based Bot Defence is not supported for your environment at this time.</p>
 <p>Support for additional server types is planned for a future version of Bastion.</p>',

	'botDefenceNotDetected'=>'<p>We were unable to identify your server type, so Bastion cannot safely apply <code>.htaccess</code> - based Bot Defence.</p>
 <p>To avoid incorrect configuration or potential downtime, this feature is disabled until the server environment can be confirmed.</p>
 <p>If your server uses Apache, please verify your setup and try again.</p>',


	// Updates Reminder
	'Updates Reminder'=>'Updates Reminder',
	'Notify About Available Updates'=>'Notify About Available Updates',
	'updatesReminderDescription'=>'Stay ahead of security fixes and new features with automated update alerts. Bastion emails you a clean summary of all CraftCMS and plugin updates—delivered on your schedule to as many emails as you need.',
	'Email Addresses'=>'Email Addresses',
	'updatesReminderEmails'=>'Enter one or more email addresses to receive updates reminders. Separate multiple addresses with a semicolon (;)',
	'Notification Day'=>'Notification Day',
	'updatesReminderNotificationDayDescription'=>'Choose which day you would like to receive update reports.',
	'Notification Frequency'=>'Notification Frequency',
	'updatesReminderNotificationFrequency'=>'Select how often you want to receive notifications.',


	// IP Restrictions
	'ipRestrictionsDescription'=>'Control which IP addresses are allowed to access your site or control panel.',
	'enableForCpDescription'=>'When enabled, only the allowed IPs can access the Craft CMS control panel (admin area).',
	'enableForFrontDescription'=>'When enabled, only the allowed IPs can access the public website.',
	'Your current IP'         => 'Your current IP',
	'ipRestrictorDescription' => '<p>You can define allowed IPs in the following formats:</p>
    <p><strong>Single IP:</strong> Use a single address like <code>192.168.0.1</code> (IPv4) or <code>::1</code> (IPv6) to allow access from just one device.</p>
    <p><strong>IP Range:</strong> Use a range like <code>192.168.0.0-192.168.0.5</code> to allow multiple consecutive addresses. This includes all IPs from the start to the end of the range (inclusive).</p>
    <p><strong>CIDR Notation:</strong> Use CIDR format like <code>192.168.0.0/24</code> to define a block of IPs. For example, <code>/24</code> allows all IPs from <code>192.168.0.0</code> to <code>192.168.0.255</code>.</p>
    <p><strong>IPv6 Support:</strong> You can also use IPv6 addresses and CIDR blocks like <code>2001:db8::/32</code> for modern networks.</p> <p><strong>Tip:</strong> Use CIDR for large ranges, IP ranges for short spans, and single IPs for precise control.</p>',
	'Enabled for the control panel'=>'Enabled for the control panel',
	'Whitelist for the control panel'=>'Whitelist for the control panel',
	'IP/CIDR/Range Address'=>'IP/CIDR/Range Address',
	'Notes'=>'Notes',
	'Restriction method for the control panel'=>'Restriction method for the control panel',
	'Redirect URL'=>'Redirect URL',
	'Template'=>'Template',
	'Enabled for the frontend'=>'Enabled for the frontend',
	'Whitelist for the frontend'=>'Whitelist for the frontend',
	'Restriction method for the frontend'=>'Restriction method for the frontend',
	'pleaseProvideValidIpCidr'                          => 'Please provide a valid IPv4 or IPv6 address or range',
	'pleaseProvideValidRestrictionMethod'               => 'Please provide a valid Restriction Method',
	'pleaseProvideValidUrl'                             => 'Please provide a valid URL',
	'pleaseProvideValidTemplate'                        => 'Please provide a valid Template',

	//SSL Reminder
	'Send SSL expiry reminders?'=>'Send SSL expiry reminders?',
	'Notification Emails'=>'Notification Emails',
	'sslExpiryDateDescription'=>'Enter one or more email addresses to receive SSL expiry reminders. Separate multiple addresses with a semicolon (;).<br>SSL expiry reminder email will be sent 7 days and 24 hours before SSL expiry date',
	'SSL Settings'=>'SSL Settings',
	'ssl7dayReminderEmailSubject'=>'SSL Certificate Expiry Notice — 7 Days Remaining for {domain}',
	'ssl24HoursReminderEmailSubject'=>'Urgent: SSL Certificate for {domain} Expires in 24 Hours',
	'settingsNeedLaunch'=>'Launch the scanner at least once to enable configuration of its settings.',
	'SSL Status'=>'SSL Status',
	'Last run'=>'Last run',
	'never'=>'never',
	'What does my score mean?'=>'What does my score mean?',
	'View detailed report on Qualys SSL Labs'=>'View detailed report on Qualys SSL Labs',
	'Protocol support'=>'Protocol support',
	'Certificate'=>'Certificate',
	'Cipher strength'=>'Cipher strength',
	'Expired at'=>'Expired at',
	'Check SSL health'=>'Check SSL health',
	'Fix'=>'Fix',
	


	// dashboard
	'tooltipEnvironment'=>'The environment setting controls whether your site is in <b>development</b> or <b>live</b> mode.<br><b>Development mode</b> shows detailed error messages and expand logging.',
	'tooltipIndexing'=>"If <span class='warning'><code>DISABLED</code></span>, CraftCMS adds Disallow rules to block Google and other crawlers from indexing your pages. Bastion checks site's meta tags, headers and robots.txt, as well as environment variable <code>DISALLOW_ROBOTS</code>.",
	'Quick Overview'=>'Quick Overview',
	'Environment Check'=>'Environment Check',
	'Environment set'=>'Environment set',
	'Development mode'=>'Development mode',
	'ENABLED'=>'ENABLED',
	'DISABLED'=>'DISABLED',
	'BLOCKED'=>'BLOCKED',
	'Site Indexing'=>'Site Indexing',
	'Search Engine Visibility'=>'Search Engine Visibility',
	'Indexing allowed — no restrictions detected.'=>'Indexing allowed — no restrictions detected.',
	'Baseline Security Controls'=>'Baseline Security Controls',
	'baselineSecurityControlsDescription'=>'The following security checks are enabled automatically to protect your site. Advanced users may disable specific checks if required.',

	// dashboard Security Checks
	'No critical Craft updates available'=>'No critical Craft updates available',
	'Critical Craft updates should be given immediate attention as soon as they are released as they can contain important security fixes. Not doing so puts your site at serious risk.
					
            If, for whatever reason, it is not possible to update the site, then you should at least make yourself aware of the gravity of the vulnerability that the updates addressed and the potential consequences of not applying the fixes.'=>'Critical Craft updates should be given immediate attention as soon as they are released as they can contain important security fixes. Not doing so puts your site at serious risk.
					
            If, for whatever reason, it is not possible to update the site, then you should at least make yourself aware of the gravity of the vulnerability that the updates addressed and the potential consequences of not applying the fixes.',
	'Changelog'=>'Changelog',
	'Version {version} is a critical update, released on {date}.'=>'Version {version} is a critical update, released on {date}.',
	'Apply critical Craft updates: '=>'Apply critical Craft updates: ',
	'No critical plugin updates available'=>'No critical plugin updates available',
	'Critical plugin updates should be given immediate attention as soon as they are released as they can contain important security fixes. Not doing so puts your site at serious risk.

            If, for whatever reason, it is not possible to update the site, then you should at least make yourself aware of the gravity of the vulnerability that the updates addressed and the potential consequences of not applying the fixes.'=>'Critical plugin updates should be given immediate attention as soon as they are released as they can contain important security fixes. Not doing so puts your site at serious risk.

            If, for whatever reason, it is not possible to update the site, then you should at least make yourself aware of the gravity of the vulnerability that the updates addressed and the potential consequences of not applying the fixes.',
	'Version {version} is a critical plugin update, released on {date}.'=>'Version {version} is a critical plugin update, released on {date}.',
	'Apply critical plugin updates: '=>'Apply critical plugin updates: ',
	'No Craft updates available'=>'No Craft updates available',
	'Craft updates can contain security enhancements and bug fixes that are not deemed critical, so it is nevertheless recommended to apply them whenever possible.'=>'Craft updates can contain security enhancements and bug fixes that are not deemed critical, so it is nevertheless recommended to apply them whenever possible.',
	'Your version of Craft is behind the latest version'=>'Your version of Craft is behind the latest version',
	'No plugin updates available'=>'No plugin updates available',
	'Plugin updates can contain security enhancements and bug fixes that are not deemed critical, so it is nevertheless recommended to apply them whenever possible.'=>'Plugin updates can contain security enhancements and bug fixes that are not deemed critical, so it is nevertheless recommended to apply them whenever possible.',
	'Local version {localVersion} is {count} release{plural} behind latest version {latestVersion}, released on {date}.'=>'Local version {localVersion} is {count} release{plural} behind latest version {latestVersion}, released on {date}.',
	'Update plugins to latest versions: '=>'Update plugins to latest versions: ',
	'Control panel is forcing an encrypted HTTPS connection'=>'Control panel is forcing an encrypted HTTPS connection',
	'Using an SSL certificate and forcing the control panel to use an encrypted HTTPS connection ensures secure authentication of users and protects site data. This is especially important since some of your users may be admins or have elevated privileges.

            SSL certificates have become very affordable and straightforward to install, so there is no excuse for not using one. A HTTPS connection will not only secure all communications between users and your site, it will also make your site appear more trustworthy to browsers and search engines.'=>'Using an SSL certificate and forcing the control panel to use an encrypted HTTPS connection ensures secure authentication of users and protects site data. This is especially important since some of your users may be admins or have elevated privileges.

            SSL certificates have become very affordable and straightforward to install, so there is no excuse for not using one. A HTTPS connection will not only secure all communications between users and your site, it will also make your site appear more trustworthy to browsers and search engines.',
	'Force an encrypted HTTPS connection on front-end'=>'Force an encrypted HTTPS connection on front-end',
	'Force an encrypted HTTPS connection on control panel'=>'Force an encrypted HTTPS connection on control panel',
	'Front-end site is forcing an encrypted HTTPS connection'=>'Front-end site is forcing an encrypted HTTPS connection',
	'Using an SSL certificate and forcing the front-end of your site to use an encrypted HTTPS connection protects the site and user data. This is especially important if public user registration is allowed, as you may be collecting and storing personal data.

            SSL certificates have become very affordable and straightforward to install, so there is no excuse for not using one. A HTTPS connection will not only secure all communications between users and your site, it will also make your site appear more trustworthy to browsers and search engines.'=>'Using an SSL certificate and forcing the front-end of your site to use an encrypted HTTPS connection protects the site and user data. This is especially important if public user registration is allowed, as you may be collecting and storing personal data.

            SSL certificates have become very affordable and straightforward to install, so there is no excuse for not using one. A HTTPS connection will not only secure all communications between users and your site, it will also make your site appear more trustworthy to browsers and search engines.',
	'Dynamic @web alias not used in volume base URL'=>'Dynamic @web alias not used in volume base URL',
	'When using the @web alias in your asset volume\'s base URL, it should be explicitly defined rather than dynamically generated. Leaving it dynamic could introduce a cache poisoning vulnerability, and Craft won\'t be able to reliably determine which volume is being requested. This can result in issues when running console commands.'=>'When using the @web alias in your asset volume\'s base URL, it should be explicitly defined rather than dynamically generated. Leaving it dynamic could introduce a cache poisoning vulnerability, and Craft won\'t be able to reliably determine which volume is being requested. This can result in issues when running console commands.',
	'Dynamic @web alias is used in volume base URL: {volumes}'=>'Dynamic @web alias is used in volume base URL: {volumes}',
	'Craft Docs'=>'Craft Docs',
	'Dev mode is disabled'=>'Dev mode is disabled',
	'Dev mode is intended for testing and debugging your site and outputs performance related data on the front-end. It should never be enabled in production environments.'=>'Dev mode is intended for testing and debugging your site and outputs performance related data on the front-end. It should never be enabled in production environments.',
	'Disable dev mode'=>'Disable dev mode',
	'Search Engine Visibility ENABLED'=>'Search Engine Visibility ENABLED',
	'Site indexing by Search Engine.'=>'Site indexing by Search Engine.',
	'Site Search Engine indexing DISABLED by&nbsp;<span class="warning">{message}</span>'=>'Site Search Engine indexing DISABLED by&nbsp;<span class="warning">{message}</span>',
	'Please edit your .env file and set CRAFT_DISALLOW_ROBOTS to false'=>'Please edit your .env file and set CRAFT_DISALLOW_ROBOTS to false',
	'Frontend access not restricted (by IP)'=>'Frontend access not restricted (by IP)',
	'Restrict frontend access by IP address'=>'Restrict frontend access by IP address',
	'Frontend access restricted (by IP)'=>'Frontend access restricted (by IP)',
	'Bot Defence ENABLED'=>'Bot Defence ENABLED',
	'We recommend activating Bot Defence to block unwanted bots directly at the server level, reducing load and improving security.'=>'We recommend activating Bot Defence to block unwanted bots directly at the server level, reducing load and improving security.',
	'Bot Defence DISABLED'=>'Bot Defence DISABLED',
	'A Content Security Policy (CSP) is an added layer of security that helps to detect and mitigate certain types of attacks, including cross-site scripting (XSS) and data injection attacks. These attacks are used for everything from data theft to site defacement.

            By enforcing a Content Security Policy, you explicitly tell the browser which types of resources (images, scripts, styles, etc.) it is allowed to load, where it may load them from (your site, external sites, etc.) and whether inline scripts and styles may be processed. Setting it up takes some time and should be done with care, but results in a very effective method of mitigating and reporting attacks.

            CraftBastion can help you create a Content Security Policy by allowing you to set the directives, switch from reporting to enforcing when you are ready and choose to implement it using a HTTP header or meta tag.'=>'A Content Security Policy (CSP) is an added layer of security that helps to detect and mitigate certain types of attacks, including cross-site scripting (XSS) and data injection attacks. These attacks are used for everything from data theft to site defacement.

            By enforcing a Content Security Policy, you explicitly tell the browser which types of resources (images, scripts, styles, etc.) it is allowed to load, where it may load them from (your site, external sites, etc.) and whether inline scripts and styles may be processed. Setting it up takes some time and should be done with care, but results in a very effective method of mitigating and reporting attacks.

            CraftBastion can help you create a Content Security Policy by allowing you to set the directives, switch from reporting to enforcing when you are ready and choose to implement it using a HTTP header or meta tag.',
	'MDN Article'=>'MDN Article',
	'Neither {header} header nor meta tag are set'=>'Neither {header} header nor meta tag are set',
	'{header} {type} '=>'{header} {type} ',
	'contains "unsafe" values'=>'contains "unsafe" values',
	'is set'=>'is set',
	'Craft KB'=>'Craft KB',
	'Access to other sites is not granted'=>'Access to other sites is not granted',
	'Cross-Origin Resource Sharing (CORS) is an HTTP-header based mechanism that allows a server to indicate any other origins (domain, scheme, or port) than its own from which a browser should permit loading of resources. CORS also relies on a mechanism by which browsers make a preflight request to the server hosting the cross-origin resource, in order to check that the server will permit the actual request. In that preflight, the browser sends headers that indicate the HTTP method and headers that will be used in the actual request.'=>'Cross-Origin Resource Sharing (CORS) is an HTTP-header based mechanism that allows a server to indicate any other origins (domain, scheme, or port) than its own from which a browser should permit loading of resources. CORS also relies on a mechanism by which browsers make a preflight request to the server hosting the cross-origin resource, in order to check that the server will permit the actual request. In that preflight, the browser sends headers that indicate the HTTP method and headers that will be used in the actual request.',
	'Referrer-Policy header is set to'=>'Referrer-Policy header is set to',
	'The Referrer-Policy header controls how much referrer information (sent via the Referer header) should be included with requests.'=>'The Referrer-Policy header controls how much referrer information (sent via the Referer header) should be included with requests.',
	'Set the {header} header'=>'Set the {header} header',
	'Strict-Transport-Security header is set to'=>'Strict-Transport-Security header is set to',
	'The Strict-Transport-Security header (often abbreviated as HSTS) lets a web site tell browsers that it should only be accessed using HTTPS, instead of using HTTP. Setting this to 15552000 or more will ensure that browsers remember this setting for at least 6 months.'=>'The Strict-Transport-Security header (often abbreviated as HSTS) lets a web site tell browsers that it should only be accessed using HTTPS, instead of using HTTP. Setting this to 15552000 or more will ensure that browsers remember this setting for at least 6 months.',
	'Set {header} header to "{value}"'=>'Set {header} header to "{value}"',
	'X-Content-Type-Options header is set to'=>'X-Content-Type-Options header is set to',
	'The X-Content-Type-Options header is a marker used by the server to indicate that the MIME types advertised in the Content-Type headers should not be changed and be followed. This is a way to opt out of MIME type sniffing, or, in other words, to say that the MIME types are deliberately configured.'=>'The X-Content-Type-Options header is a marker used by the server to indicate that the MIME types advertised in the Content-Type headers should not be changed and be followed. This is a way to opt out of MIME type sniffing, or, in other words, to say that the MIME types are deliberately configured.',
	'X-Frame-Options header is set to'=>'X-Frame-Options header is set to',
	'The X-Frame-Options header can be used to indicate whether or not a browser should be allowed to render a page in a <frame>, <iframe>, <embed> or <object>. Sites can use this to avoid click-jacking attacks, by ensuring that their content is not embedded into other sites.'=>'The X-Frame-Options header can be used to indicate whether or not a browser should be allowed to render a page in a <frame>, <iframe>, <embed> or <object>. Sites can use this to avoid click-jacking attacks, by ensuring that their content is not embedded into other sites.',
	'Set {header} header to "{value1}" or "{value2}"'=>'Set {header} header to "{value1}" or "{value2}"',
	'X-XSS-Protection header is set to'=>'X-XSS-Protection header is set to',
	'The X-XSS-Protection header is a feature of Internet Explorer, Chrome and Safari that stops pages from loading when they detect reflected cross-site scripting (XSS) attacks. Although these protections are largely unnecessary in modern browsers when sites implement a strong Content Security Policy that disables the use of inline JavaScript, they can still provide protections for users of older web browsers that don\'t yet support CSP.'=>'The X-XSS-Protection header is a feature of Internet Explorer, Chrome and Safari that stops pages from loading when they detect reflected cross-site scripting (XSS) attacks. Although these protections are largely unnecessary in modern browsers when sites implement a strong Content Security Policy that disables the use of inline JavaScript, they can still provide protections for users of older web browsers that don\'t yet support CSP.',
	'Prevent user enumeration is enabled'=>'Prevent user enumeration is enabled',
	'When true, Craft will always return a successful response in the forgot password flow, making it difficult to enumerate users. When set to false and you go through the forgot password flow from the control panel login page, you\'ll get distinct messages indicating whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to log in but allow for username/email enumeration based on the response.'=>'When true, Craft will always return a successful response in the forgot password flow, making it difficult to enumerate users. When set to false and you go through the forgot password flow from the control panel login page, you\'ll get distinct messages indicating whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to log in but allow for username/email enumeration based on the response.',
	'Set prevent user enumeration to true'=>'Set prevent user enumeration to true',
	'Powered By header is disabled'=>'Powered By header is disabled',
	'Whether an `X-Powered-By` header should be sent, helping services like BuiltWith and Wappalyzer identify that the site is running on Craft, but which can also be abused by website scrapers looking to identify Craft sites.'=>'Whether an `X-Powered-By` header should be sent, helping services like BuiltWith and Wappalyzer identify that the site is running on Craft, but which can also be abused by website scrapers looking to identify Craft sites.',
	'Set the powered by header to false'=>'Set the powered by header to false',
	'Craft file permissions are correctly set'=>'Craft file permissions are correctly set',
	'The file permissions of the files that Craft must be able to write to. Set these to at most 0664, which will grant read and write for the owner and group, read for everyone else.'=>'The file permissions of the files that Craft must be able to write to. Set these to at most 0664, which will grant read and write for the owner and group, read for everyone else.',
	'Craft file permissions are not correctly set: {paths}'=>'Craft file permissions are not correctly set: {paths}',
	'Craft folder permissions are correctly set'=>'Craft folder permissions are correctly set',
	'The folder permissions of the folders that Craft must be able to write to. Set these to at most 0775, which will grant everything for the owner and group, read and execute for everyone else.'=>'The folder permissions of the folders that Craft must be able to write to. Set these to at most 0775, which will grant everything for the owner and group, read and execute for everyone else.',
	'Craft folder permissions are not correctly set: {paths}'=>'Craft folder permissions are not correctly set: {paths}',
	'Craft folders are located above the web root'=>'Craft folders are located above the web root',
	'Keeping the Craft folders above the web root ensures that no one can access any of their files directly.'=>'Keeping the Craft folders above the web root ensures that no one can access any of their files directly.',
	'Craft folders not located above the web root: {paths}'=>'Craft folders not located above the web root: {paths}',
	'No admin account with username "admin" exists'=>'No admin account with username "admin" exists',
	'Since admin accounts have the highest privileges in Craft, it is better to avoid having any with the username "admin", which is easily guessable. This makes it harder for potential attackers to target admin user accounts.'=>'Since admin accounts have the highest privileges in Craft, it is better to avoid having any with the username "admin", which is easily guessable. This makes it harder for potential attackers to target admin user accounts.',
	'An admin account with username "admin" exists'=>'An admin account with username "admin" exists',
	'Site is running on a supported PHP version'=>'Site is running on a supported PHP version',
	'Each release branch of PHP is fully supported for two years from its initial stable release, followed by two additional years for critical security issues only. After this time the branch reaches its end of life and is no longer supported.

            • PHP 8.2 receives security support until 31 December 2026.
            • PHP 8.3 receives security support until 31 December 2027.

            Running a version of PHP that is no longer receiving security support may put your site at serious risk.'=>'Each release branch of PHP is fully supported for two years from its initial stable release, followed by two additional years for critical security issues only. After this time the branch reaches its end of life and is no longer supported.

            • PHP 8.2 receives security support until 31 December 2026.
            • PHP 8.3 receives security support until 31 December 2027.

            Running a version of PHP that is no longer receiving security support may put your site at serious risk.',
	'Supported Versions'=>'Supported Versions',
	'Site is running on a supported PHP version {version}'=>'Site is running on a supported PHP version {version}',
	'Site is running on an unsupported PHP version {version}'=>'Site is running on an unsupported PHP version {version}',
	'Composer is using the correct PHP version'=>'Composer is using the correct PHP version',
	'Craft adds a PHP platform requirement (config → platform → php) to your project\'s composer.json file when it is installed. You should change this version to reflect that used on your production web server. Doing so will ensure that the latest versions of dependencies will be used, according to their PHP version requirements.'=>'Craft adds a PHP platform requirement (config → platform → php) to your project\'s composer.json file when it is installed. You should change this version to reflect that used on your production web server. Doing so will ensure that the latest versions of dependencies will be used, according to their PHP version requirements.',
	'Composer Docs'=>'Composer Docs',
	'Set the PHP version in composer.json to {version}'=>'Set the PHP version in composer.json to {version}',
	'Re-run checks'=>'Re-run checks',
	'Run checks'=>'Run checks',
	'Pass'=>'Pass',
	'Fail'=>'Fail',
	'Warn'=>'Warn',
	'All'=>'All',
	'Passed'=>'Passed',
	'Warning'=>'Warning',
	'Failed'=>'Failed',
	'Check'=>'Check',
	'Details'=>'Details',
	'Action'=>'Action',
	'Info'=>'Info',


	// Domain checker
	'Domain Status'=>'Domain Status',
	'Expires'=>'Expires',
	'Renew at'=>'Renew at',
	'Check Domain'=>'Check Domain',
	'days remaining'=>'days remaining',
	'Domain Settings'=>'Domain Settings',
	'Send domain expiry reminders?'=>'Send domain expiry reminders?',
	'domainExpiryDateDescription'=>'Enter one or more email addresses to receive domain expiry reminders. Separate multiple addresses with a semicolon (;).<br>Domain expiry reminder email will be sent 30 days and 7 days before domain expiry date',
	"Local development domain detected - can't run check"=>"Local development domain detected - can't run check",
	'domain30dayReminderEmailSubject'=>'Domain Expiry Notice — 30 Days Remaining for {domain}',
	'domain7dayReminderEmailSubject'=>'Urgent: Domain name Expires in 7 days',
	'<b>{expDate}</b> in <b>{expDays}</b> days'=>'<b>{expDate}</b> in <b>{expDays}</b> days',

	// Disk space usage
	'Send disk space usage reminders?'=>'Send disk space usage reminders?',
	'When disk is % or more full'=>'When disk is % or more full',
	'Set the disk usage percentage threshold (1-100%) for sending reminders'=>'Set the disk usage percentage threshold (1-100%) for sending reminders',
	'diskSpaceReminderRecipients'=>'Enter one or more email addresses to receive disk usage reminders. Separate multiple addresses with a semicolon (;).',
	'diskSpaceUsageEmailSubject'=>'Disk Space Warning — {domain} is {percentage}% Full',

	'Disk Space Status'=>'Disk Space Status',
	'Unable to determine disk space'=>'Unable to determine disk space',
	'USED'=>'USED',
	'OUT OF'=>'OUT OF',

	// Control Panel Check
	'Control Panel Check'=>'Control Panel Check',
	'controlPanelCheckInstructions'=>'CraftCMS allows you to change the default control panel URL (<b>/admin</b>) for security reasons. Leaving it unchanged makes it easier for bots and attackers to target your login page.<br>To change it, edit the following line in your .env file:<br><code>CRAFT_CP_TRIGGER="your-custom-path"</code><br>Example:<br><code>CRAFT_CP_TRIGGER="mycontrolpanel"</code><br>After saving, access your control panel via:<br><code>{adminUrl}/mycontrolpanel</code>.',
	'controlPanelCheckGood'=>'The default CraftCMS administration control panel is accessible at <span class="{status}"><code>{adminUrl}</code></span>',

	// Dependency Audit
	'Dependency Audit'=>'Dependency Audit',
	'Checks installed Composer packages against the Packagist Security Advisories database.'=>'Checks installed Composer packages against the Packagist Security Advisories database.',
	'Run Scan'=>'Run Scan',
	'Dependency Audit'=>'Dependency Audit',
	'Last scanned: {date} at {time}'=>'Last scanned: {date} at {time}',
	'packages checked'=>'packages checked',
	'Scan took'=>'Scan took',
	'Packages Scanned'=>'Packages Scanned',
	'Vulnerabilities'=>'Vulnerabilities',
	'Critical'=>'Critical',
	'High'=>'High',
	'Medium'=>'Medium',
	'Low'=>'Low',
	'Package:'=>'Package:',
	'Installed:'=>'Installed:',
	'Affected:'=>'Affected:',
	'Reported:'=>'Reported:',
	'Source:'=>'Source:',
	'All Clear — No Known Vulnerabilities'=>'All Clear — No Known Vulnerabilities',
	'All {stats_total} installed Composer packages were checked against the Packagist Security Advisories database. No known CVEs affect your currently installed versions.'=>'All {stats_total} installed Composer packages were checked against the Packagist Security Advisories database. No known CVEs affect your currently installed versions.',
	'No Scan Results Available'=>'No Scan Results Available',
	'Click "Run Scan" to check your Composer packages for known security vulnerabilities.'=>'Click "Run Scan" to check your Composer packages for known security vulnerabilities.'
];
