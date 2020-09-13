# Laravel Microsoft Graph Mail

This package makes it easy to send emails from your personal, work or school account using Microsoft's Graph API,
allowing you to benefit from HTTP instead of SMTP with Laravel.

_Tested with different company (Microsoft 365 Business) accounts_

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

Valid values for `tenant` are your tenant identifier (work & school accounts) or `common` for personal accounts.

**Note:** This package relies on [Laravel's Cache](https://laravel.com/docs/cache) interface for caching access tokens.
Make sure to configure it properly, too!

### Version

The latest version is only compatible with Laravel 8.x. Use an older version if you didn't upgrade to Laravel 8 yet:

| Package Version | Laravel Version |
|-----------------|-----------------|
| ^1.0            | 7.x             |
| ^2.0            | 8.x             |


### Getting the credentials

To get the necessary client ID and secret you'll need to register your application and grant it the required
permissions. Head over to [the Azure Portal to do so](https://docs.microsoft.com/en-us/graph/auth-register-app-v2)
(you don't need to be an Azure user).

Make sure to grant the _Mail.Send_ permission and to generate a secret afterwards (may be hidden during app registration).

**Work & School accounts:** Granting your app the _Mail.Send_ permission allows you by default to send emails with every
valid email address within your company/school. Use an [Exchange Online Application Access Policy](https://docs.microsoft.com/en-us/graph/auth-limit-mailbox-access)
to restrict which email addresses are valid senders for your application.
