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

This plugin requires the [Command plugin](https://github.com/phergie/phergie-irc-plugin-react-command) to recognise commands, and the
[http plugin](https://github.com/WyriHaximus/PhergieHttp) to query Google for your search results.

If you're new to Phergie or Phergie plugins, see the [Phergie setup instructions](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#configuration)
for more information.  Otherwise, add the following references to your config file:

```php
return array(
	// ...
    'plugins' => array(
    	new \Chrismou\Phergie\Plugin\Google\Plugin
		new \Phergie\Irc\Plugin\React\Command\Plugin,	// dependency
		new \WyriHaximus\Phergie\Plugin\Dns\Plugin,		// dependency
		new \WyriHaximus\Phergie\Plugin\Http\Plugin		// dependency
	)
)
```

By default, the plugin will respond to both google and g for Google searches, and googlecount and gc for estimated result 
counts.

Or, you can pass references to the providers you want to use as a config array, where the array key is the command you want 
the bot to respond to and the value is the class to use.

```php
new \Chrismou\Phergie\Plugin\Google\Plugin(array(
    'providers' => array(
        "google" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
        "g" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
        "googlecount" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount",
        "gc" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount"
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
