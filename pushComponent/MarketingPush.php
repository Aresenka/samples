<?php

namespace common\components\push;

use common\models\MarketingPushTask;
use common\modules\pusher\helpers\TemplateHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class MarketingPush
 * @package common\components\push
 */
class MarketingPush extends Push
{
    private ?MarketingPushTask $pushTask = null;

    /**
     * @param \common\models\MarketingPushTask $task
     * @return $this
     */
    public function setTask(MarketingPushTask $task): self
    {
        $this->pushTask = $task;

        return $this;
    }

    protected function generateMessage(): void
    {
        $this->prepareTemplate();

        parent::generateMessage();
    }

    protected function handleResponse(): void
    {
        parent::handleResponse();

        $this->pushService->setTaskSendingStatus($this->pushTask);
    }

    protected function addLog(): void
    {
        $this->pushLogService->setError($this->pushService->getError());
        $this->pushLogService->addMarketingLog($this->pushTask->id, $this->message->getMessageArray());
    }

    private function prepareTemplate(): void
    {
        $templateParams = Json::decode($this->pushTask->params);
        $metaParams = TemplateHelper::replaceParams($this->pushTask->push->metaParams, $templateParams);

        $this->setNotificationTitle(TemplateHelper::replaceParams($this->pushTask->push->title, $templateParams));
        $this->setNotificationBody(TemplateHelper::replaceParams($this->pushTask->push->body, $templateParams));

        if (!empty($metaParams)) {
            $this->setMessageData(ArrayHelper::map($metaParams, 'key', 'value'));
        }
    }
}
