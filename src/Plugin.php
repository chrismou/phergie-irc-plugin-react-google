<?php
/**
 * Phergie plugin for returning Google search results and estimated result counts
 * (https://github.com/chrismou/phergie-irc-plugin-react-google)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2016 Chris Chrisostomou (https://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Google
 */

namespace Chrismou\Phergie\Plugin\Google;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use Phergie\Plugin\Http\Request as HttpRequest;
use Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface;
use GuzzleHttp\Message\Response;

/**
 * Plugin class.
 *
 * @category Phergie
 * @package Chrismou\Phergie\Plugin\Google
 */
class Plugin extends AbstractPlugin
{
    /**
     * Array of default providers
     *
     * @var array
     */
    protected $providers = [
        "google" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
        "googlecount" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount"
    ];


    /**
     * Accepts plugin configuration
     *
     * Supported keys:
     *        providers - array of provider classes to replace the default set ($this->providers)
     *
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['providers']) && is_array($config['providers'])) {
            $this->providers = $config['providers'];
        }
    }


    /**
     * Return an array of commands and associated methods
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        $events = [];
        foreach ($this->providers as $command => $provider) {
            $events['command.' . $command] = 'handleCommand';
            $events['command.' . $command . '.help'] = 'handleCommandHelp';
        }

        return $events;
    }


    /**
     * Main plugin handler for registered action commands
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleCommand(Event $event, Queue $queue)
    {
        $provider = $this->getProvider($event->getCustomCommand());
        if ($provider && $provider->validateParams($event->getCustomParams())) {
            $request = $this->getApiRequest($event, $queue, $provider);
            $this->getEventEmitter()->emit('http.request', [$request]);
        } else {
            $this->handleCommandhelp($event, $queue);
        }
    }


    /**
     * Main plugin handler for help requests
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleCommandHelp(Event $event, Queue $queue)
    {

        $params = $event->getCustomParams();
        $provider = $this->getProvider(
            ($event->getCustomCommand() === "help") ? $params[0] : $event->getCustomCommand()
        );

        if ($provider) {
            $this->sendIrcResponse($event, $queue, $provider->getHelpLines());
        }
    }


    /**
     * Get a single provider class by command
     *
     * @param string $command
     * @return mixed
     */
    public function getProvider($command)
    {
        $providerExists = (isset($this->providers[$command]) && class_exists($this->providers[$command]));
        return ($providerExists) ? new $this->providers[$command] : false;
    }


    /**
     * Set up the API request and set the callbacks
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     * @param \Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface $provider
     * @return \Phergie\Plugin\Http\Request
     */
    protected function getApiRequest(Event $event, Queue $queue, GoogleProviderInterface $provider)
    {
        $self = $this;

        return new HttpRequest([
            'url' => $provider->getApiRequestUrl($event),
            'resolveCallback' => function (Response $response) use ($self, $event, $queue, $provider) {
                $self->sendIrcResponse($event, $queue, $provider->getSuccessLines($event, $response->getBody()));
            },
            'rejectCallback' => function ($error) use ($self, $event, $queue, $provider) {
                $self->sendIrcResponse($event, $queue, $provider->getRejectLines($event, $error));
            }
        ]);
    }


    /**
     * Send an array of response lines back to IRC
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     * @param array $ircResponse
     */
    protected function sendIrcResponse(Event $event, Queue $queue, array $ircResponse)
    {
        foreach ($ircResponse as $ircResponseLine) {
            $this->sendIrcResponseLine($event, $queue, $ircResponseLine);
        }
    }


    /**
     * Send a single response line back to IRC
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     * @param string $ircResponseLine
     */
    protected function sendIrcResponseLine(Event $event, Queue $queue, $ircResponseLine)
    {
        $queue->ircPrivmsg($event->getSource(), $ircResponseLine);
    }


    /**
     * Return an array of providers classes
     */
    public function getProviders()
    {
        return $this->providers;
    }
}
