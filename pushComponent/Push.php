<?php

namespace common\components\push;

use common\models\FirebaseToken;
use common\services\push\PushLogService;
use common\services\push\PushService;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/**
 * Class Push
 * @package common\components
 */
class Push extends Component
{
    protected PushService $pushService;
    protected PushLogService $pushLogService;
    protected ?PushMessage $message = null;
    private ?ResponseInterface $response = null;
    private ?FirebaseToken $userToken = null;
    private ?int $userId = null;
    private string $notificationTitle = '';
    private string $notificationBody = '';
    private array $messageData = [];

    /**
     * Push constructor.
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($config = [])
    {
        $this->pushService = Yii::createObject(PushService::class);
        $this->pushLogService = Yii::createObject(PushLogService::class);

        parent::__construct($config);
    }

    /**
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @param \common\models\FirebaseToken $token
     * @return $this
     */
    public function setUserToken(FirebaseToken $token): self
    {
        $this->userToken = $token;

        return $this;
    }

    /**
     * @param string|null $text
     * @return $this
     */
    public function setNotificationTitle(?string $text): self
    {
        $this->notificationTitle = $text ?? '';

        return $this;
    }

    /**
     * @param string|null $text
     * @return $this
     */
    public function setNotificationBody(?string $text): self
    {
        $this->notificationBody = $text ?? '';

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setMessageData(array $data): self
    {
        $this->messageData = $data;

        return $this;
    }

    /**
     * @return \common\components\push\PushResponse
     */
    public function send(): PushResponse
    {
        try {
            $this->prepareRequest();
            $this->sendRequest();
            $this->handleResponse();
        } catch (Throwable $e) {
            $this->pushService->setError($e->getMessage());
        } finally {
            $this->addLog();

            return $this->pushService->getResponse();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function prepareRequest(): void
    {
        $this->validateData();
        $this->generateMessage();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateData(): void
    {
        if (empty($this->userId)) {
            throw new InvalidArgumentException('ID пользователя не установлен');
        }

        if (empty($this->userToken->token)) {
            throw new InvalidArgumentException('Токен пользователя не установлен');
        }
    }

    protected function generateMessage(): void
    {
        $message = new PushMessage($this->userToken);

        $message->setNotificationTitle($this->notificationTitle);
        $message->setNotificationBody($this->notificationBody);
        $message->setMessageData($this->messageData);

        $this->message = $message;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest(): void
    {
        $this->response = $this->pushService->sendRequestToFirebase($this->message->getMessageArray());
    }

    /**
     * @throws \common\exceptions\ErrorSaveModelException
     */
    protected function handleResponse(): void
    {
        $this->pushService->handleResponse($this->response);
        $this->pushService->handleError($this->userToken, $this->message);
    }

    protected function addLog(): void
    {
        $this->pushLogService->setError($this->pushService->getError());
        $this->pushLogService->addLog($this->userId, $this->message->getMessageArray());
    }
}
