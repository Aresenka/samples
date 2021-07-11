<?php

namespace common\components\push;

use common\components\dictionaries\PushDictionary;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class PushMessage
 * @package common\components\push
 */
class PushMessageBuilder extends Component
{
    public const PRIORITY_NORMAL = 'NORMAL';
    public const PRIORITY_HIGH = 'HIGH';

    protected array $messageData = [];
    protected string $notificationTitle = '';
    protected string $notificationBody = '';
    protected string $messagePriority = self::PRIORITY_NORMAL;
    protected bool $isSilent = false;
    protected ?string $firebaseToken = null;
    protected bool $useSound = false;
    private ?int $timeToLiveInSeconds = null;

    /**
     * @param string $text
     * @return $this
     */
    public function setNotificationTitle(string $text): self
    {
        $this->notificationTitle = $text;

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setNotificationBody(string $text): self
    {
        $this->notificationBody = $text;

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
     * @param array $data
     * @return $this
     */
    public function addMessageData(array $data): self
    {
        $this->messageData = ArrayHelper::merge($this->messageData, $data);

        return $this;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setFirebaseToken(string $token): self
    {
        $this->firebaseToken = $token;

        return $this;
    }

    /**
     * @param int|null $ttlSeconds
     * @return $this
     */
    public function setTTL(?int $ttlSeconds): self
    {
        $this->timeToLiveInSeconds = $ttlSeconds;

        return $this;
    }

    /**
     * @return $this
     */
    public function makeSilent(): self
    {
        $this->isSilent = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function makePriorityHigh(): self
    {
        $this->messagePriority = self::PRIORITY_HIGH;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUseSound(bool $value = true): self
    {
        $this->useSound = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageJsonEncoded(): string
    {
        return Json::encode($this->getMessageArray());
    }

    /**
     * @return array
     */
    public function getMessageArray(): array
    {
        $messageArray = [
            'notification' => [
                'title' => $this->notificationTitle,
                'body' => $this->notificationBody,
            ],
            'android' => [
                'priority' => $this->messagePriority,
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => PushDictionary::SOUND_DEFAULT,
                    ],
                ],
            ],
        ];

        if ($this->firebaseToken !== null) {
            $messageArray['token'] = $this->firebaseToken;
        }

        if (!empty($this->messageData)) {
            $messageArray['data'] = $this->messageData;
        }

        if ($this->isSilent === true) {
            $messageArray['apns']['payload']['aps'] = ['content-available' => 1];
            $messageArray['data']['silent'] = 'true';
        }

        $messageArray['data']['sound_type'] = $this->useSound ? PushDictionary::SOUND_TYPE_ENABLE : PushDictionary::SOUND_TYPE_DISABLE;

        if ($this->timeToLiveInSeconds !== null) {
            $messageArray['apns']['headers']['apns-expiration'] = (string)($this->timeToLiveInSeconds + time());
            $messageArray['android']['ttl'] = $this->timeToLiveInSeconds . 's';
        }

        return ['message' => $messageArray];
    }

    /**
     * @param string $messageJson
     * @return static
     */
    public static function messageJsonDecode(string $messageJson): self
    {
        $data = Json::decode($messageJson);

        $object = new self();
        $object->setNotificationTitle($data['message']['notification']['title'] ?? '');
        $object->setNotificationBody($data['message']['notification']['body'] ?? '');

        if (!empty($data['message']['android']['priority']) && $data['message']['android']['priority'] === self::PRIORITY_HIGH) {
            $object->makePriorityHigh();
        }

        if (!empty($data['message']['token'])) {
            $object->setFirebaseToken($data['message']['token']);
        }

        if (!empty($data['message']['data'])) {
            $object->setMessageData($data['message']['data']);
        }

        if (!empty($data['message']['data']['silent'])) {
            $object->makeSilent();
        }

        $object->setUseSound(!empty($data['message']['data']['sound_type']));

        if (!empty($data['message']['android']['ttl'])) {
            $object->setTTL((int)$data['message']['android']['ttl']);
        }

        return $object;
    }
}
