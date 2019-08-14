##Installation

`$ composer require jmaloneytrevetts/bagistohubexport`
`$ composer require laravel/slack-notification-channel`

`$ composer dump-autoload`

In config/app.php add under providers:
`jmaloneytrevetts\bagistohubexport\BagistoHubExportServiceProvider::class `

Other dependencies:
`composer require laravel/slack-notification-channel`

###.env

Key  | Example Value
------------- | -------------
HUB_ADDRESS  | https://www.hub.com
HUB_API_KEY  | ”abcdefg123”
HUB_SHIP_METHOD_ID  | 2 [Look up equivalent id in hub tblshippingmethods]
SLACK_HOOK  | SLACK_HOOK=https://hooks.slack.com/services/abc123

###Artisan Commands

Key  | Example Value
------------- | -------------
hub:export  | [orderID optional parameter]
