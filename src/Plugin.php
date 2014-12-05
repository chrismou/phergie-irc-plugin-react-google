<?php
/**
 * Phergie plugin for Perform various Google searches/lookups from within IRC (https://github.com/chrismou/phergie-irc-plugin-react-google)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2014 Chris Chrisostomou (http://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Phergie\Irc\Plugin\React\Google
 */

namespace Chrismou\Phergie\Plugin\Google;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;
use WyriHaximus\Phergie\Plugin\Http\Request as HttpRequest;

/**
 * Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\Google
 */
class Plugin extends AbstractPlugin
{

	protected $providers = array(
		"google" => "GoogleSearch"
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

    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
			'command.google' => 'handleGoogleCommand',
			'command.google.help' => 'handleGoogleHelp'
        );
    }


	protected function getApiRequestUrl($url, $params=array())
	{
		return sprintf("%s?%s", $url, http_build_query($params));
	}


	protected function getApiRequest($apiRequestUrl, Event $event, Queue $queue)
	{
		$self = $this;

		return new HttpRequest(array(
			'url' => $apiRequestUrl,
			'resolveCallback' => function($data) use ($self, $apiRequestUrl, $event, $queue) {

				$json = json_decode($data);
				$json = $json->responseData;

				//TODO: colour plugin?
				if ($json->cursor->estimatedResultCount > 0) {
					$queue->ircPrivmsg($event->getSource(), sprintf(
						"%s [ %s ]",
						$json->results[0]->titleNoFormatting,
						$json->results[0]->url
					));

					//$queue->ircPrivmsg($event->getSource(), preg_replace("/[^a-z0-9,.\ ]+/i", "", strip_tags($json->results[0]->content)));
					$queue->ircPrivmsg($event->getSource(), sprintf("More results: %s", $json->cursor->moreResultsUrl));

				} else {
					$msg = 'No results for this query.'.$json->cursor->estimatedResultCount;
					$queue->ircPrivmsg($event->getSource(), $msg);
				}
			},
			'rejectCallback' => function($error) use ($self, $apiRequestUrl, $event, $queue) {
				$queue->ircPrivmsg($event->getSource(), "Failed! :-(");
			}
		));
	}

    /**
     *
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleGoogleCommand(Event $event, Queue $queue)
    {
		$url = 'http://ajax.googleapis.com/ajax/services/search/web';

		$params = $event->getCustomParams();
		if (!count($params)) return $this->handleGoogleHelp($event, $queue);

		$query = trim(implode(" ", $params));

		$apiParams = array(
			'v' => '1.0',
			'q' => $query
		);

		$request = $this->getApiRequest($this->getApiRequestUrl($url, $apiParams), $event, $queue);
		$this->getEventEmitter()->emit('http.request', array($request));

		/*$response = $this->plugins->http->get($url, $params);
		$json = $response->getContent()->responseData;
		$event = $this->getEvent();
		$source = $event->getSource();
		$nick = $event->getNick();
		if ($json->cursor->estimatedResultCount > 0) {
			$msg
				= $nick
				. ': [ '
				. $json->results[0]->titleNoFormatting
				. ' ] - '
				. $json->results[0]->url
				. ' - More results: '
				. $json->cursor->moreResultsUrl;
			$this->doPrivmsg($source, $msg);
		} else {
			$msg = $nick . ': No results for this query.';
			$this->doPrivmsg($source, $msg);
		}*/
    }

	/**
	 * Google Command Help
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
	 */
	public function handleGoogleHelp(Event $event, Queue $queue)
	{
		$this->sendHelpReply($event, $queue, array(
			'Usage: google [search query]',
			'[search query] - the word or phrase you want to search for',
			'Instructs the bot to query Google and respond with the top result'
		));
	}

	/**
	 * Responds to a help command.
	 *
	 * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
	 * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
	 * @param array $messages
	 */
	protected function sendHelpReply(Event $event, Queue $queue, array $messages)
	{
		$method = 'irc' . $event->getCommand();
		$target = $event->getSource();
		foreach ($messages as $message) {
			$queue->$method($target, $message);
		}
	}
}
