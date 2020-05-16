# Laravel Microsoft Graph Mail

This package makes it easy to send emails from your personal, work or school account using Microsoft's Graph API,
allowing you to benefit from HTTP instead of SMTP with Laravel 7.x.

_Tested with personal and company (Microsoft 365 Business) accounts_

## Installation

Install the package using composer:

```
composer require wapacro/laravel-msgraph-mail
```

Add the configuration to your mail.php config file:

```php
'mailers' => [

    'microsoft-graph' => [
        'transport' => 'microsoft-graph',
        'tenant' => env('MAIL_MSGRAPH_TENANT', 'common'),
        'client' => env('MAIL_MSGRAPH_CLIENT'),
        'secret' => env('MAIL_MSGRAPH_SECRET')
    ]

    // ...

]
```

### Getting the credentials

To get the necessary client id and secret you'll need to register your application.
