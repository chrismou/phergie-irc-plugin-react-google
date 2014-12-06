# phergie/phergie-irc-plugin-react-google

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for performing Google searches.

[![Build Status](https://travis-ci.org/chrismou/phergie-irc-plugin-react-google.svg)](https://travis-ci.org/chrismou/phergie-irc-plugin-react-google)
## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "chrismou/phergie-irc-plugin-react-google": "dev-master"
    }
}
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

Add the following line to your config file:

```php
new Chrismou\Phergie\Plugin\Google\Plugin()
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
