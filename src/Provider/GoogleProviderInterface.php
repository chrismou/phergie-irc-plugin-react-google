<?php
/**
 * interface for various providers for the Google plugin for Phergie (https://github.com/phergie/phergie-irc-bot-react)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2014 Chris Chrisostomou (http://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Google
 */

namespace Chrismou\Phergie\Plugin\Google\Provider;

use Phergie\Irc\Plugin\React\Command\CommandEvent;
use Phergie\Irc\Bot\React\EventQueueInterface;

interface GoogleProviderInterface {

	function validateParams(array $params);

	function getApiRequestUrl(CommandEvent $event);

	function getSuccessLines(CommandEvent $event, $apiResponse);

	function getFailureLines(CommandEvent $event, $apiError);

	function getHelpLines();

}