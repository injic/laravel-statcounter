StatCounter API with support for Laravel 5
---
[![Latest Stable Version](https://poser.pugx.org/injic/laravel-statcounter/v/stable.svg)](https://packagist.org/packages/injic/laravel-statcounter) [![License](https://poser.pugx.org/injic/laravel-statcounter/license.svg)](https://packagist.org/packages/injic/laravel-statcounter)

The package supports use with the [Laravel framework][2] (v5) providing a `Stat` facade for the [StatCounter API][1]. You can find the documentation for this package [here][6].

----
###Setup:
In order to install, add the following to your `composer.json` file within the `require` block:

```js
{
    "require": {
        "injic/laravel-statcounter": "2.*",
    }
}
```
Now, run the command `composer update`.

Within Laravel, locate the file `/config/app.php`. Add the following to the `providers` array:
```php
Injic\LaravelStatcounter\LaravelStatcounterServiceProvider::class,
```

Furthermore, add the following the `aliases` array:
```php
'Stat' => Injic\LaravelStatcounter\Facades\Stat::class,
```

Publish the configuration

```sh
$ php artisan vendor:publish --provider="Injic\LaravelStatcounter\LaravelStatcounterServiceProvider"
```

Find the package's config found in `/config/statcounter.php`. You'll need to fill out your StatCounter username, API password, and project information. Since the config file contains further information on the individual values, here are a few things to note:
 - `username` is the case-sensitive login username for StatCounter
 - `password` referes to the API password, **not** the login password (see [API Password][3])
 - `default` must match one of the project names under `projects`
 - `your-project-name` is the only value that isn't specified by StatCounter. It's an alias of your choosing used when calling functions which handle projects.
 - `projects` and `security-codes` must having matching keys (i.e. the project names).


----
###Usage:

Methods of the `Stat` class simplify what is needed for a StatCounter API query as described in the [StatCounter API Documentation][4]. Additionally, the `Stat` class was modeled off of the [Laravel DB Query][5] design, and you may expect similar methods. 

You can find the documentation for this package [here][6].

----
###Examples:

```php
// Retrieve an array of browser objects (http://api.statcounter.com/docs/v3#browsers)
$stats = Stat::browsers()->get();

// Retrieve an array of summary objects (http://api.statcounter.com/docs/v3#summary-daily)
$now = \Carbon\Carbon::now(); // http://carbon.nesbot.com/
$stats = Stat::summary()->setRange(Granularity::DAILY, $now->subWeek(), $now)->get();

// Paginate the recent visitor objects (http://api.statcounter.com/docs/v3#visitors)
// https://laravel.com/docs/5.2/pagination
$stats = Stat::recentVisitors()->paginate(20);

// Add new project to StatCounter with the project name, project url, and project timezone
$project Stat::addProject('project-name','www.example.com','America/Chicago');
```
```php
<html>
<body>
<!-- Prints the StatCounter tracker using Laravel Templates -->
{!! Stat::tracker() !!}
</body>
</html>
```

[1]: http://api.statcounter.com/
[2]: http://laravel.com/
[3]: http://api.statcounter.com/password
[4]: http://api.statcounter.com/docs/v3
[5]: http://laravel.com/docs/queries
[6]: https://github.com/injic/laravel-statcounter/wiki/API-Documentation
