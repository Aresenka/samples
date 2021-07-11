<?php

namespace common\components\google;

use Google_Client;
use GuzzleHttp\ClientInterface;
use yii\base\Component;

/**
 * Class GoogleClient
 * @package common\components\google
 */
class GoogleClient extends Component
{
    /**
     * @param array $scopes
     * @return \GuzzleHttp\Client|\GuzzleHttp\ClientInterface
     */
    public static function authorize(array $scopes): ClientInterface
    {
        $client = new Google_Client();

        $client->useApplicationDefaultCredentials();
        $client->addScope($scopes);

        return $client->authorize();
    }
}
