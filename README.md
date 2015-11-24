# Google plugin for [Phergie](http://github.com/phergie/phergie-irc-bot-react/)

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for returning Google search results and estimated result counts.

[![Build Status](https://img.shields.io/travis/chrismou/phergie-irc-plugin-react-google/master.svg?style=flat-square)](https://travis-ci.org/chrismou/phergie-irc-plugin-react-google)
[![Test Coverage](https://codeclimate.com/github/chrismou/phergie-irc-plugin-react-google/badges/coverage.svg)](https://codeclimate.com/github/chrismou/phergie-irc-plugin-react-google/coverage)
[![Code Climate](https://codeclimate.com/github/chrismou/phergie-irc-plugin-react-google/badges/gpa.svg)](https://codeclimate.com/github/chrismou/phergie-irc-plugin-react-google)

## About

This plugin provides a simple method for performing Google lookups from within IRC.  By default, the plugin accepts one of 2 commands (google and googlecount) and outputs either the top result
for your search query, or the estimated result count.

I'd also recommend installing the [CommandAlias plugin](https://github.com/phergie/phergie-irc-plugin-react-commandalias), which can be used to alias the commands (ie, to use "g" instead of "google").

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```
composer require chrismou/phergie-irc-plugin-react-google
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

This plugin requires the [Command plugin](https://github.com/phergie/phergie-irc-plugin-react-command) to recognise commands, and the
[http plugin](https://github.com/phergie/plugin-http) to query Google for your search results.

If you're new to Phergie or Phergie plugins, see the [Phergie setup instructions](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#configuration)
for more information.  Otherwise, add the following references to your config file:

```php
return array(
    // ...
    'plugins' => array(
        new \Chrismou\Phergie\Plugin\Google\Plugin,
        new \Phergie\Irc\Plugin\React\Command\Plugin,  // dependency
        new \Phergie\Plugin\Dns\Plugin,                // dependency
        new \Phergie\Plugin\Http\Plugin	               // dependency
    )
)
```

By default, the plugin will respond to "google" for Google searches, and "googlecount" for estimated results.
counts.

Or, you can pass references to the providers you want to use as a config array, where the array key is the command you want 
the bot to respond to and the value is the class to use.

```php
new \Chrismou\Phergie\Plugin\Google\Plugin(array(
    'providers' => array(
        "google" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
        "googlecount" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount"
    )
)),
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See [LICENSE](LICENSE).
