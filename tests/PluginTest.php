<?php
/**
 * Phergie plugin for Perform various Google searches/lookups from within IRC (https://github.com/chrismou/phergie-irc-plugin-react-google)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2014 Chris Chrisostomou (http://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Google
 */

namespace Chrismou\Phergie\Tests\Plugin\Google;

use Phake;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use Chrismou\Phergie\Plugin\Google\Plugin;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package Chrismou\Phergie\Plugin\Google
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin;
        $this->assertInternalType('array', $plugin->getSubscribedEvents());
    }

	/**
	 * Tests that the default providers exist
	 */
	public function testProviderClassExists() {
		$plugin = new Plugin;
		$providers = $plugin->getProviders();

		foreach ($providers as $command => $class) {
			$providerExists = (class_exists($class)) ? true : false;
			$this->assertTrue($providerExists, "Class ".$class." does not exist");
		}
	}

}
