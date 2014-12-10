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
     * Mock event emitter
     *
     * @var \Evenement\EventEmitterInterface
     */
    protected $emitter;

    /**
     * Mock event
     *
     * @var \Phergie\Irc\Event\EventInterface
     */
    protected $event;

    /**
     * Mock event queue
     *
     * @var \Phergie\Irc\Bot\React\EventQueueInterface
     */
    protected $queue;

    /**
     * Mock logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;



    protected function setUp()
    {
        $this->event = Phake::mock('Phergie\Irc\Plugin\React\Command\CommandEvent');
        $this->queue = Phake::mock('Phergie\Irc\Bot\React\EventQueueInterface');
    }

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $this->assertInternalType('array', $this->getPlugin()->getSubscribedEvents());
    }

    /**
     * Tests that the default providers exist
     */
    public function testDefaultProvidersImplementation()
    {
        $providers = $this->getPlugin()->getProviders();
        $this->assertInternalType('array', $providers);

        foreach ($providers as $command => $class) {
            // Check the class file physically exists
            $providerExists = (class_exists($class)) ? true : false;
            $this->assertTrue($providerExists, "Class ".$class." does not exist");

            // Check it correct implements GoogleProviderInterface
            if ($providerExists) $this->assertInstanceOf('Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface', new $class);
        }
    }

    /**
     * Tests for the default "google" command
     */
    public function testSearchCommand()
    {
        $httpConfig = $this->doCommandTest("google", array("test", "search"));
        $data = file_get_contents(__DIR__ . '/_data/webSearchResults.json');
        $this->doResolveTest("google", $data, $httpConfig);
    }

    /**
     * Tests for the default "google" command
     */
    public function testSearchCommandNoResults()
    {
        $httpConfig = $this->doCommandTest("google", array("test", "search"));
        $data = file_get_contents(__DIR__ . '/_data/webSearchNoResults.json');
        $this->doResolveNoResultsTest("google", $data, $httpConfig);
    }

    /**
     * Tests for the default "google" command
     */
    public function testSearchCommandFailure()
    {
        $httpConfig = $this->doCommandTest("google", array("test", "search"));
        $this->doRejectTest("google", $httpConfig);
    }

    /**
     * Tests for the default "google" command
     */
    public function testSearchHelpCommand()
    {
        $this->doHelpCommandTest("help", array("google"));
    }

    /**
     * Tests the default "googlecount" command
     */
    public function testSearchCountCommand()
    {
        $httpConfig = $this->doCommandTest("googlecount", array("test", "search"));
        $data = file_get_contents(__DIR__ . '/_data/webSearchResults.json');
        $this->doResolveTest("googlecount", $data, $httpConfig);
    }

    /**
     * Tests for the default "google" command
     */
    public function testSearchCountHelpCommand()
    {
        $this->doHelpCommandTest("help", array("googlecount"));
    }

    /**
     * Tests handCommand() is doing what it's supposed to
     *
     * @param string $command
     * @param array $params
     *
     * @return array $httpConfig
     */
    protected function doCommandTest($command, $params)
    {
        // Test if we've been passed an array of parameters
        $this->assertInternalType('array', $params);

        $plugin = $this->getPlugin();

        Phake::when($this->event)->getCustomCommand()->thenReturn($command);
        Phake::when($this->event)->getCustomParams()->thenReturn($params);

        $plugin->handleCommand($this->event, $this->queue);
        Phake::verify($plugin->getEventEmitter())->emit('http.request', Phake::capture($httpConfig));

        // Grab a provider class
        $provider = $plugin->getProvider($this->event->getCustomCommand());

        $this->verifyHttpConfig($httpConfig, $provider);

        $request = reset($httpConfig);

        return $request->getConfig();
    }

    /**
     * Tests handCommand() is doing what it's supposed to
     *
     * @param string $command
     * @param array $params
     */
    protected function doHelpCommandTest($command, $params)
    {
        $this->assertInternalType('array', $params);

        $plugin = $this->getPlugin();

        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getCustomCommand()->thenReturn($command);
        Phake::when($this->event)->getCustomParams()->thenReturn($params);

        $plugin->handleCommandHelp($this->event, $this->queue);

        // Grab a provider class
        $provider = $plugin->getProvider($params[0]);

        if ($provider) {
            $helpLines = $provider->getHelpLines();
            $this->assertInternalType('array', $helpLines);

            foreach ((array)$helpLines as $responseLine) {
                Phake::verify($this->queue)->ircPrivmsg('#channel', $responseLine);
            }
        }
    }

    /**
     * Tests handCommand() is doing what it's supposed to
     *
     * @param array $httpConfig
     * @param string $provider
     */
    protected function verifyHttpConfig(array $httpConfig, $provider)
    {
        // Check we have an array with one element
        $this->assertInternalType('array', $httpConfig);
        $this->assertCount(1, $httpConfig);

        $request = reset($httpConfig);

        // Check we have an instance of the http plugin
        $this->assertInstanceOf('\WyriHaximus\Phergie\Plugin\Http\Request', $request);

        // Check the url stored by htttp is the same as what we've called
        $this->assertSame($provider->getApiRequestUrl($this->event), $request->getUrl());

        // Grab the response config and check the required callbacks exist
        $config = $request->getConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('resolveCallback', $config);
        $this->assertInternalType('callable', $config['resolveCallback']);
        $this->assertArrayHasKey('rejectCallback', $config);
        $this->assertInternalType('callable', $config['rejectCallback']);
    }

    /**
     * Tests handCommand() handles resolveCallback correctly
     *
     * @param string $command
     * @param array $httpConfig
     */
    protected function doResolveTest($command, $data, array $httpConfig)
    {
        $this->doPreCallbackSetup($command);
        $callback = $httpConfig['resolveCallback'];
        $responseLines = $this->getPlugin()->getProvider($command)->getSuccessLines($this->event, $data);
        $this->doPostCallbackTests($data, $callback, $responseLines);
    }

    /**
     * Tests handCommand() handles resolveCallback correctly
     *
     * @param string $command
     * @param array $httpConfig
     */
    protected function doResolveNoResultsTest($command, $data, array $httpConfig)
    {
        $this->doPreCallbackSetup($command);
        $callback = $httpConfig['resolveCallback'];
        $responseLines = $this->getPlugin()->getProvider($command)->getNoResultsLines($this->event, $data);
        $this->doPostCallbackTests($data, $callback, $responseLines);
    }

    /**
     * Tests handCommand() handles rejectCallback correctly
     *
     * @param string $command
     * @param array $httpConfig
     */
    protected function doRejectTest($command, array $httpConfig)
    {
        $error = "Foobar";
        $this->doPreCallbackSetup($command);
        $callback = $httpConfig['rejectCallback'];
        $responseLines = $this->getPlugin()->getProvider($command)->getRejectLines($this->event, $error);
        $this->doPostCallbackTests($error, $callback, $responseLines);
    }

    /**
     * Sets mocks pre-callback
     *
     * @param string $command
     */

    protected function doPreCallbackSetup($command)
    {
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getCustomCommand()->thenReturn($command);
    }

    /**
     * Sets mocks in preparation for a callback test
     *
     * @param string $data
     * @param string $command
     * @param string $command
     */

    protected function doPostCallbackTests($data, $callback, $responseLines)
    {
        // Test we've had an array back and it has at least one response message
        $this->assertInternalType('array', $responseLines);
        $this->assertArrayHasKey(0, $responseLines);

        // Run the resolveCallback callback
        $callback($data, $this->event, $this->queue);

        // Verify if each expected line was sent
        foreach ($responseLines as $responseLine) {
            Phake::verify($this->queue)->ircPrivmsg('#channel', $responseLine);
        }
    }

    /**
     * Returns a configured instance of the class under test.
     *
     * @param array $config
     *
     * @return \Chrismou\Phergie\Plugin\Google\Plugin
     */
    protected function getPlugin(array $config = array())
    {
        $plugin = new Plugin($config);
        $plugin->setEventEmitter(Phake::mock('\Evenement\EventEmitterInterface'));
        $plugin->setLogger(Phake::mock('\Psr\Log\LoggerInterface'));

        return $plugin;
    }

    /**
     * Returns a mock command event.
     *
     * @return \Phergie\Irc\Plugin\React\Command\CommandEvent
     */
    protected function getMockCommandEvent()
    {
        return Phake::mock('Phergie\Irc\Plugin\React\Command\CommandEvent');
    }

    /**
     * Returns a mock event queue.
     *
     * @return \Phergie\Irc\Bot\React\EventQueueInterface
     */
    protected function getMockEventQueue()
    {
        return Phake::mock('Phergie\Irc\Bot\React\EventQueueInterface');
    }

}
