<?php

namespace common\components\push;

use common\models\dictionaries\FirebaseTokenDictionary;
use stdClass;
use yii\base\Component;
use yii\helpers\Json;

/**
 * Class PushError
 * @package common\components\push
 */
class PushError extends Component
{
    public const ERROR_NOT_CRITICAL = 0;
    public const ERROR_CRITICAL = 1;

    private string $message = '';
    private string $errorCode = '';
    private int $errorIsCritical = self::ERROR_NOT_CRITICAL;
    private ?stdClass $serverResponse = null;

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param \stdClass $response
     * @return $this
     */
    public function setServerResponse(stdClass $response): self
    {
        $this->serverResponse = $response;

        if (!empty($response->error->details)) {
            $this->errorCode = $response->error->details[0]->errorCode ?? '';
        }

        if (in_array($this->errorCode, FirebaseTokenDictionary::getErrors())) {
            $this->makeCritical();
        }

        return $this;
    }

    public function getServerResponse(): stdClass
    {
        return $this->serverResponse;
    }

    /**
     * @return string
     */
    public function getDataJsonEncoded(): string
    {
        $data = [
            'message' => $this->message,
            'serverResponse' => $this->serverResponse,
            'errorIsCritical' => $this->errorCode,
        ];

        return Json::encode($data);
    }

    /**
     * @return $this
     */
    public function makeCritical(): self
    {
        $this->errorIsCritical = self::ERROR_CRITICAL;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->errorIsCritical === self::ERROR_CRITICAL;
    }
}
