# Omniauth - multi-provider authentication middleware

Omniauth is a middleware for multi-provider authentication inspired by [Opauth](http://opauth.org).

## How to use it?

Check out and see [examples](examples/index.php) to find how it work.

The constructor of the `Omniauth` class has serval options to control its behavior.

```php
<?php

use winwin\omniauth\Omniauth;

$omniauth = new Omniauth([
    'route' => '/auth/:strategy/:action',
    'strategies' => [
        'provider' => [
            'provider' => 'http://my-login-service/login',
            'key' => 'mysecretkey'
        ]
    ]
]);
```

### strategies

An array of configuration for authentication providers.
The key is the provider name and also will be used for route match,
and the value will be the contructor parameters for the strategy.

The common configuration key for the strategy is `strategy_class`，which also can set
by call:

```php
<?php

$omniauth->getStrategyFactory()->register($strategyName, $strategyClass);
```
### default

### route

A pattern to match which uri will do the authentication.
The default value is `/:strategy/:action`. The `:strategy` place holder
will expand to an regexp match all provider name listed in `strategies` configuration,
and the `:action` place holder will match nothing or any word.

### auth_key

The key name to save user identity in `$_SESSION` array. The default value is `'auth'`

### auto_login

If value is true, omniauth will check current user whether is logged in (by check `$_SESSION['auth']` is not empty),
if not, it will redirect user to the default login page. The default value is `true`.

### redirect_uri_key

The key name to save current page before redirect user to login page in `$_SESSION` array and when user login successfully,
omniauth will redirect user to the saved page.
The default value is `login_redirect_uri`.

### identity_transformer

A function to transformer user identity before save to session.

## How to add my authentication strategy?

Check out [PasswordStrategy](tests/strategies/PasswordStrategy.php) to see how to implements an new authentication strategy.

Usually, a strategy should extends [AbstractStrategy](src/AbstractStrategy.php) and have to implement two function `authenticate` and `verify`.
The `authenticate` function initiate the authentication flow,
and the `verify` function will check user's credential and call `$this->login($user)` to set user identity and return back the page before login.

## API

```php
<?php

if (!$omniauth->isAuthenticated()) {
    $omniauth->getStrategy($strategyName)->authenticate();
}

if ($omniauth->isAuthenticated()) {
    $userProfile = $omniauth->getIdentity();
}

$omniauth->clear();
```

