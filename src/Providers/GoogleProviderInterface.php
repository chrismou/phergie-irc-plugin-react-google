<?php

namespace Chrismou\Phergie\Plugin\Google\Providers;

use Phergie\Irc\Plugin\React\Command\CommandEvent;
use Phergie\Irc\Bot\React\EventQueueInterface;

interface GoogleProviderInterface {

	function getApiRequestUrl(CommandEvent $event, EventQueueInterface $queue);

	function processSuccessResponse(CommandEvent &$event, EventQueueInterface &$queue, $response);

	function processFailedResponse(CommandEvent &$event, EventQueueInterface &$queue, $error);

	function getHelpLines();

}