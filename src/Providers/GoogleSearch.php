<?php

namespace Chrismou\Phergie\Plugin\Google\Providers;

use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;

class GoogleSearch implements GoogleProviderInterface
{

	protected $apiUrl = 'http://ajax.googleapis.com/ajax/services/search/web';

	public function getApiRequestUrl(Event $event, Queue $queue)
	{
		$params = $event->getCustomParams();
		//if (!count($params)) return $this->handleGoogleHelp($event, $queue);
		if (!count($params)) return "";

		$query = trim(implode(" ", $params));

		$querystringParams = array(
			'v' => '1.0',
			'q' => $query
		);

		return sprintf("%s?%s", $this->apiUrl, http_build_query($querystringParams));
	}

	public function processSuccessResponse(Event &$event, Queue &$queue, $response)
	{
		$json = json_decode($response);
		$json = $json->responseData;

		if ($json->cursor->estimatedResultCount > 0) {
			$queue->ircPrivmsg($event->getSource(), sprintf(
				"%s [ %s ]",
				$json->results[0]->titleNoFormatting,
				$json->results[0]->url
			));
			$queue->ircPrivmsg($event->getSource(), sprintf("More results: %s", $json->cursor->moreResultsUrl));

		} else {
			$msg = 'No results for this query.' . $json->cursor->estimatedResultCount;
			$queue->ircPrivmsg($event->getSource(), $msg);
		}
	}

	public function processFailedResponse(Event &$event, Queue &$queue, $error)
	{
		$queue->ircPrivmsg($event->getSource(), "something went wrong... ಠ_ಠ");
	}

	/**
	 *
	 *
	 * @return array
	 */
	public function getHelpLines()
	{
		return array(
			'Usage: google [search query]',
			'[search query] - the word or phrase you want to search for',
			'Instructs the bot to query Google and respond with the top result'
		);
	}

}