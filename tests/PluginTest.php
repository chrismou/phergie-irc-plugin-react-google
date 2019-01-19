<?php
/**
 * Phergie plugin for Perform various Google searches/lookups from within IRC (https://github.com/chrismou/phergie-irc-plugin-react-google)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2016 Chris Chrisostomou (https://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Google
 */

namespace Chrismou\Phergie\Tests\Plugin\Google;

use Phake;
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
     * @var \Evenement\EventEmitterInterface
     */
    protected $emitter;

    /**
     * @var \Phake_IMock
     */
    protected $event;

    /**
     * @var \Phake_IMock
     */
    protected $apiResponse;

    /**
     * @var \Phake_IMock
     */
    protected $queue;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $config = [];

    protected function setUp()
    {
        $this->event = Phake::mock('Phergie\Irc\Plugin\React\Command\CommandEvent');
        $this->queue = Phake::mock('Phergie\Irc\Bot\React\EventQueueInterface');
        $this->apiResponse = Phake::mock('GuzzleHttp\Message\Response');
        $this->config = [
            'google_custom_search_id' => '1',
            'google_custom_search_key' => '1',
        ];
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
            $this->assertTrue($providerExists, "Class " . $class . " does not exist");

            // Check it correct implements GoogleProviderInterface
            if ($providerExists) $this->assertInstanceOf('Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface', new $class($this->config));
        }
    }

    /**
     * Tests valid custom providers can be set
     */
    public function testSetCustomProviders()
    {
        $testConfig = [
            "test1" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
            "test2" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount"
        ];
        $plugin = new Plugin(["providers" => $testConfig]);

        foreach ($testConfig as $command => $provider) {
            $this->assertInstanceOf($provider, $plugin->getProvider($command));
        }
    }

    /**
     * Tests non-existent custom providers are ignored
     */
    public function testSetCustomProvidersWithNonExistentClass()
    {
        $testConfig = [
            "test1" => "\\fake\\path\\to\\class",
            "test2" => "\\another\\fake\\class"
        ];
        $plugin = new Plugin(["providers" => $testConfig]);

        foreach ($testConfig as $command => $provider) {
            $this->assertEquals($plugin->getProvider($command), false);
        }
    }


    /**
     * Tests for the default "google" command
     */
    public function testSearchCommand()
    {
        $httpConfig = $this->doCommandTest("google", ["test", "search"]);
        $json = file_get_contents(__DIR__ . '/_data/webSearchResults.json');
        Phake::when($this->apiResponse)->getBody()->thenReturn($json);

        $this->doResolveTest("google", $this->apiResponse, $httpConfig);
    }

    /**
     * Tests for the default "google" command with no results
     */
    public function testSearchCommandNoResults()
    {
        $httpConfig = $this->doCommandTest("google", ["test", "search"]);
        $json = file_get_contents(__DIR__ . '/_data/webSearchNoResults.json');
        Phake::when($this->apiResponse)->getBody()->thenReturn($json);

        $this->doResolveNoResultsTest("google", $this->apiResponse, $httpConfig);
    }

    /**
     * Tests for the default "google" command with a connection failure
     */
    public function testSearchCommandFailure()
    {
        $httpConfig = $this->doCommandTest("google", ["test", "search"]);
        $this->doRejectTest("google", $httpConfig);
        $this->doCommandInvalidParamsTest([]);
    }

    /**
     * Tests for the default "google" help command
     */
    public function testSearchHelpCommand()
    {
        $this->doHelpCommandTest("help", ["google"]);
    }

    /**
     * Tests the default "googlecount" command
     */
    public function testSearchCountCommand()
    {
        $httpConfig = $this->doCommandTest("googlecount", ["test", "search"]);
        $json = file_get_contents(__DIR__ . '/_data/webSearchResults.json');
        Phake::when($this->apiResponse)->getBody()->thenReturn($json);

        $this->doResolveTest("googlecount", $this->apiResponse, $httpConfig);
    }

    /**
     * Tests for the default "googlecount" command with a connection failure
     */
    public function testSearchCountCommandFailure()
    {
        $httpConfig = $this->doCommandTest("googlecount", ["test", "search"]);
        $this->doRejectTest("googlecount", $httpConfig);
        $this->doCommandInvalidParamsTest([]);
    }

    /**
     * Tests for the default "google" help command
     */
    public function testSearchCountHelpCommand()
    {
        $this->doHelpCommandTest("help", ["googlecount"]);
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

            foreach ((array) $helpLines as $responseLine) {
                Phake::verify($this->queue)->ircPrivmsg('#channel', $responseLine);
            }
        }
    }

    /**
     * Tests handleCommand() is doing what it's supposed to
     * @return array $httpConfig
     */
    protected function doCommandInvalidParamsTest(array $params = [])
    {
        // GRab a fresh queue instance to test on
        $queue = $this->getMockEventQueue();
        // Set the "invalid" parameters
        Phake::when($this->event)->getCustomParams()->thenReturn($params);
        $plugin = $this->getPlugin();
        $plugin->handleCommand($this->event, $queue);

        $helpLines = $plugin->getProvider($this->event->getCustomCommand())->getHelpLines();
        $this->assertInternalType('array', $helpLines);

        foreach ((array) $helpLines as $responseLine) {
            Phake::verify($queue)->ircPrivmsg('#channel', $responseLine);
        }
    }

    /**
     * Verify the http object looks like what we're expecting
     *
     * @param array $httpConfig
     * @param \Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface $provider
     */
    protected function verifyHttpConfig(array $httpConfig, $provider)
    {
        // Check we have an array with one element
        $this->assertInternalType('array', $httpConfig);
        $this->assertCount(1, $httpConfig);

        $request = reset($httpConfig);

        // Check we have an instance of the http plugin
        $this->assertInstanceOf('\Phergie\Plugin\Http\Request', $request);

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
     * @param \GuzzleHttp\Message\Response $response
     * @param array $httpConfig
     */
    protected function doResolveTest($command, $response, array $httpConfig)
    {
        $this->doPreCallbackSetup($command);
        $callback = $httpConfig['resolveCallback'];
        $responseLines = $this->getPlugin()->getProvider($command)->getSuccessLines($this->event, $response->getBody());
        $this->doPostCallbackTests($response, $callback, $responseLines);
    }

    /**
     * Tests handCommand() handles resolveCallback correctly
     *
     * @param string $command
     * @param \GuzzleHttp\Message\Response $response
     * @param array $httpConfig
     */
    protected function doResolveNoResultsTest($command, $response, array $httpConfig)
    {
        $this->doPreCallbackSetup($command);
        $callback = $httpConfig['resolveCallback'];
        $responseLines = $this->getPlugin()->getProvider($command)->getNoResultsLines($this->event, $response->getBody());
        $this->doPostCallbackTests($response, $callback, $responseLines);
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
     * @param \GuzzleHttp\Message\Response $response
     * @param callable $callback
     * @param array $responseLines
     */

    protected function doPostCallbackTests($response, $callback, $responseLines)
    {
        // Test we've had an array back and it has at least one response message
        $this->assertInternalType('array', $responseLines);
        $this->assertArrayHasKey(0, $responseLines);

        $this->assertInternalType('callable', $callback);

        // Run the resolveCallback callback
        $callback($response, $this->event, $this->queue);

        // Verify if each expected line was sent
        foreach ($responseLines as $responseLine) {
            Phake::verify($this->queue)->ircPrivmsg('#channel', $responseLine);
        }
    }

    /**
     * Returns a configured instance of the class under test.
     *     *
     * @return \Chrismou\Phergie\Plugin\Google\Plugin
     */
    protected function getPlugin()
    {
        $plugin = new Plugin(['config' => $this->config]);
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
