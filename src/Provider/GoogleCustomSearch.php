<?php
/**
 * Web search provider for the Google plugin for Phergie (https://github.com/phergie/phergie-irc-bot-react)
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-google for the canonical source repository
 * @copyright Copyright (c) 2016 Chris Chrisostomou (https://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Google
 */

namespace Chrismou\Phergie\Plugin\Google\Provider;

use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;

/**
 * Provider class
 *
 * @category Phergie
 * @package Chrismou\Phergie\Plugin\Google\Provider
 */
class GoogleCustomSearch implements GoogleProviderInterface
{
    /**
     * @var string
     */
    protected $apiUrl = 'https://www.googleapis.com/customsearch/v1';

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!array_key_exists('google_custom_search_id', $config)) {
            throw new \Error('Missing Custom search ID');
        }
        if (!array_key_exists('google_custom_search_key', $config)) {
            throw new \Error('Missing Custom search Key');
        }
        $this->config = $config;
    }

    /**
     * Validate the provided parameters
     *
     * @param array $params
     * @return boolean
     */
    public function validateParams(array $params)
    {
        return (count($params)) ? true : false;
    }

    /**
     * Get the url for the API request
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @return string
     */
    public function getApiRequestUrl(Event $event)
    {
        $params = $event->getCustomParams();
        $query = trim(implode(" ", $params));

        $querystringParams = [
            'v' => isset($this->config['version']) ? $this->config['version'] : '1.0',
            'q' => $query,
            'cx' => $this->config['google_custom_search_id'],
            'key' => $this->config['google_custom_search_key'],
            'num' => isset($this->config['number_of_results']) ? $this->config['number_of_results'] : 3,
        ];

        return sprintf("%s?%s", $this->apiUrl, http_build_query($querystringParams));
    }
    
    /**
     * Returns an array of lines to send back to IRC when the http request is successful
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param string $apiResponse
     *
     * @return array
     */
    public function getSuccessLines(Event $event, $apiResponse)
    {
        $json = json_decode($apiResponse);
        return (count(isset($json->items) ? $json->items : []) > 0) ? $this->getResultLines($json->items) : $this->getNoResultsLines($event, $apiResponse);
    }

    public function getResultLines($items)
    {
        $messages = [];
        foreach ($items as $item) {
            $messages[] = sprintf(
                "%s [ %s ]",
                $item->title,
                $item->link
            );
        }
        return $messages;
    }

    /**
     * Returns an array of lines to send back to IRC when there are no results
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param string $apiResponse
     *
     * @return array
     */
    public function getNoResultsLines(Event $event, $apiResponse)
    {
        return ['No results for this query'];
    }

    /**
     * Returns an array of lines to send back to IRC when the http request fails
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param string $apiError
     *
     * @return array
     */
    public function getRejectLines(Event $event, $apiError)
    {
        return [
            'something went wrong... ಠ_ಠ',
        ];
    }

    /**
     * Returns an array of lines for the help response
     *
     * @return array
     */
    public function getHelpLines()
    {
        return [
            'Usage: google [search query]',
            '[search query] - the word or phrase you want to search for',
            'Instructs the bot to query Google and respond with the top result'
        ];
    }
}
