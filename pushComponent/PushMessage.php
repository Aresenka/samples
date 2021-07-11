<?php

namespace common\components\push;

use common\models\FirebaseToken;

/**
 * Class PushMessage
 * @package common\components\push
 */
class PushMessage extends PushMessageBuilder
{
    private FirebaseToken $userToken;

    /**
     * PushMessage constructor.
     * @param \common\models\FirebaseToken $userToken
     * @param array $config
     */
    public function __construct(FirebaseToken $userToken, $config = [])
    {
        $this->userToken = $userToken;
        $this->setFirebaseToken($this->userToken->token);

        parent::__construct($config);
    }
}
