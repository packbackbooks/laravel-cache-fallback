<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Driver Fallback Order
    |--------------------------------------------------------------------------
    |
    | Here you may list all the possible cache drivers you want to use,
    | in the order you want to fall back to them on. The package will go
    | down the list until it finds a working cache driver.
    |
    */

    'fallback_order' => [
        'redis',
        'memcached',
        'database',
        'file',
        'array'
    ],

    /*
    |--------------------------------------------------------------------------
    | Attempts Before Fallback
    |--------------------------------------------------------------------------
    |
    | In some cases, such as connection timeout errors, retrying whatever
    | operation we performed (e.g. trying to instantiate a driver) will
    | get rid of whatever exception came up initially. This setting can be
    | configured to the number of attempts that should be made before a
    | fallback occurs.
    |
    */
    'attempts_before_fallback' => 1,

    /*
    |--------------------------------------------------------------------------
    | Interval Between Attempts
    |--------------------------------------------------------------------------
    |
    | This defines, in milliseconds, how long we should wait before
    | performing our retries. See the previous configuration comment for
    | why we may want this.
    |
    */
    'interval_between_attempts' => 20,

    /*
    |--------------------------------------------------------------------------
    | Fallback On __call() Failure
    |--------------------------------------------------------------------------
    |
    | After a cache driver is instantiated and we have a connection to it,
    | that connection may get lost due to various reasons. By default,
    | this will do the normal behavior of triggering an exception, but
    | it is possible for this to have the same fallback behavior apply as
    | during cache store instantiation, so for example Cache::get() will
    | fallback to alternatives.
    |
    */
    'fallback_on_call_failure' => false,

];
