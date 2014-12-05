# phergie/phergie-irc-plugin-react-google

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for Perform various Google searches/lookups from within IRC.

[![Build Status](https://secure.travis-ci.org/phergie/phergie-irc-plugin-react-google.png?branch=master)](http://travis-ci.org/phergie/phergie-irc-plugin-react-google)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "phergie/phergie-irc-plugin-react-google": "dev-master"
    }
}
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
new Chrismou\Phergie\Plugin\Google\Plugin(array(



))
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.
