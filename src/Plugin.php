<?php
/**
 * Phergie plugin for returning Google search results and estimated result counts (https://github.com/chrismou/phergie-irc-plugin-react-google)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2014 Chris Chrisostomou (http://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Google
 */

namespace Chrismou\Phergie\Plugin\Google;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use WyriHaximus\Phergie\Plugin\Http\Request as HttpRequest;
use Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface;

/**
 * Plugin class.
 *
 * @category Phergie
 * @package Chrismou\Phergie\Plugin\Google
 */
class Plugin extends AbstractPlugin
{

	protected $providers = array(
		"google" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
		"g" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearch",
		"googlecount" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount",
		"gc" => "Chrismou\\Phergie\\Plugin\\Google\\Provider\\GoogleSearchCount"
	);

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['providers']) && is_array($config['providers'])) $this->providers = $config['providers'];
    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
		$events = array();
		foreach ($this->providers as $command => $provider) {
			$events['command.'.$command] = 'handleCommand';
			$events['command.'.$command.'.help'] = 'handleCommandHelp';
		}

		return $events;
    }

	/**
	 *
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
	 */
	public function handleCommand(Event $event, Queue $queue)
	{
		$provider = $this->getProvider($event->getCustomCommand());

        if ($provider) {
		    $request = ($provider->validateParams($event->getCustomParams())) ? $this->getApiRequest($event, $queue, $provider) : $this->handleCommandhelp($event, $queue);
		    $this->getEventEmitter()->emit('http.request', array($request));
        }
	}

	/**
	 *
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
	 * @param \Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface
	 */
	public function handleCommandHelp(Event $event, Queue $queue)
	{

		$params = $event->getCustomParams();
		$provider = $this->getProvider(($event->getCustomCommand() == "help") ? $params[0] : $event->getCustomCommand());

		if ($provider) {
			$this->sendHelpReply($event, $queue, $provider->getHelpLines());
		}
	}

	/**
	 *
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @return \Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface $provider|false
	 */
	public function getProvider($command)
	{
		return (isset($this->providers[$command]) && class_exists($this->providers[$command])) ? new $this->providers[$command] : false;
	}

	/**
	 *
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
	 * @param array $messages
	 */
	protected function sendHelpReply(Event $event, Queue $queue, array $messages)
	{
		foreach ($messages as $message) {
			$this->sendIrcResponse($event, $queue, $message);
		}
	}

	/**
	 *
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
	 * @param \Chrismou\Phergie\Plugin\Google\Provider\GoogleProviderInterface $provider
	 * @return \WyriHaximus\Phergie\Plugin\Http\Request
	 */
	protected function getApiRequest(Event $event, Queue $queue, GoogleProviderInterface $provider)
	{
		$self = $this;

		return new HttpRequest(array(
			'url' => $provider->getApiRequestUrl($event, $queue),
			'resolveCallback' => function ($data) use ($self, $event, $queue, $provider) {

				//$provider->processSuccessResponse($event, $queue, $data);
				foreach ((array) $provider->getSuccessLines($event, $data) as $ircResponseLine) {
					$self->sendIrcResponse($event, $queue, $ircResponseLine);
				}

			},
			'rejectCallback' => function ($error) use ($self, $event, $queue, $provider) {

				//$provider->processFailedResponse($event, $queue, $error);
				foreach ((array )$provider->getFailureIrcLines($event, $error) as $ircResponseLine) {
					$self->sendIrcResponse($event, $queue, $ircResponseLine);
				}

			}
		));
	}

	public function sendIrcResponse(Event $event, Queue $queue, $ircResponseLine) {
		$queue->ircPrivmsg($event->getSource(), $ircResponseLine);
	}

	public function getProviders()
	{
		return $this->providers;
	}

}
