<?php

namespace common\services\push;

use common\components\dictionaries\PushDictionary;
use common\components\google\GoogleClient;
use common\components\HttpStatuses;
use common\components\push\PushError;
use common\components\push\PushMessage;
use common\components\push\PushResponse;
use common\models\FirebaseToken;
use common\models\MarketingPushTask;
use common\models\services\FirebaseErrorLogService;
use common\models\services\FirebaseTokenService;
use common\traits\SaveActiveRecordTrait;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Yii;

/**
 * Class PushService
 * @package common\services
 */
class PushService
{
    use SaveActiveRecordTrait;

    private ?PushError $error = null;
    private bool $tokenInvalid = false;

    private FirebaseTokenService $firebaseTokenService;
    private FirebaseErrorLogService $firebaseErrorLogService;
    private PushResponse $response;

    /**
     * PushService constructor.
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct()
    {
        $this->firebaseTokenService = Yii::createObject(FirebaseTokenService::class);
        $this->firebaseErrorLogService = Yii::createObject(FirebaseErrorLogService::class);
        $this->response = Yii::createObject(PushResponse::class);
    }

    /**
     * @return \common\components\push\PushError|null
     */
    public function getError(): ?PushError
    {
        return $this->error;
    }

    /**
     * @param string $message
     */
    public function setError(string $message)
    {
        $this->error = new PushError();

        $this->error->setMessage($message);
    }

    /**
     * @return \common\components\push\PushResponse
     */
    public function getResponse(): PushResponse
    {
        $response = new PushResponse();

        $response->setError($this->error);

        return $response;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function handleResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() !== HttpStatuses::OK) {
            $serverResponse = json_decode($response->getBody()->getContents());

            $this->setError('FCM вернул ошибку');
            $this->error->setServerResponse($serverResponse);

            if ($this->error->isCritical()) {
                $this->tokenInvalid = true;
            }
        }
    }

    /**
     * @param \common\models\FirebaseToken $token
     * @param \common\components\push\PushMessage $message
     * @throws \common\exceptions\ErrorSaveModelException
     */
    public function handleError(FirebaseToken $token, PushMessage $message)
    {
        if ($this->tokenInvalid === true) {
            $this->firebaseTokenService->setErrorAt($token);
            $this->firebaseErrorLogService->log($token->id, $message->getMessageJsonEncoded(), $this->error->getDataJsonEncoded());
        } else {
            $this->firebaseTokenService->removeErrorAt($token);
        }
    }

    /**
     * @param \common\models\MarketingPushTask $task
     * @throws \common\exceptions\ErrorSaveModelException
     */
    public function setTaskSendingStatus(MarketingPushTask $task)
    {
        $task->status_sending = $this->error === null ? MarketingPushTask::STATUS_SENDING_SENT : MarketingPushTask::STATUS_SENDING_ERROR;

        $this->saveAr($task);
    }

    /**
     * @param array $message
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequestToFirebase(array $message): ResponseInterface
    {
        $client = GoogleClient::authorize([PushDictionary::SCOPE_FCM]);

        return $client->post(PushDictionary::URL_FCM, [RequestOptions::JSON => $message]);
    }
}
