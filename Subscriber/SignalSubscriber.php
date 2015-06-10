<?php

namespace EzSystems\SseBundle\Subscriber;


use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\Core\MVC\Symfony\Event\SignalEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\Core\SignalSlot\Signal\ContentService\CreateContentSignal;
use eZ\Publish\Core\SignalSlot\Signal\ContentService\DeleteContentSignal;
use eZ\Publish\Core\SignalSlot\Signal\ContentService\UpdateContentSignal;
use Pheanstalk\Pheanstalk;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalSubscriber implements EventSubscriberInterface
{
    private $pheanstalk;
    private $contentService;

    public function __construct(Pheanstalk $pheanstalk, ContentService $contentService)
    {
        $this->pheanstalk = $pheanstalk;
        $this->contentService = $contentService;
    }

    public function onApiSignal(SignalEvent $event)
    {
        $signal = $event->getSignal();

        if ($signal instanceof CreateContentSignal || $signal instanceof UpdateContentSignal)
        {
            $contentId = $signal->contentId;
            $versionNo = $signal->versionNo;

            $contentInfo = $this->contentService->loadContentInfo($contentId);

            $data = json_encode([
                'contentId' => $contentId,
                'versionNo' => $versionNo,
                'title' => $contentInfo->name
            ]);

            $message = sprintf("event: create\ndata: $data");

            $this->pheanstalk->put($message);
        }

        if ($signal instanceof UpdateContentSignal)
        {
            $contentId = $signal->contentId;
            $versionNo = $signal->versionNo;

            $contentInfo = $this->contentService->loadContentInfo($contentId);

            $data = json_encode([
                'contentId' => $contentId,
                'versionNo' => $versionNo,
                'title' => $contentInfo->name
            ]);

            $message = sprintf("event: update\ndata: $data");

            $this->pheanstalk->put($message);
        }

        if ($signal instanceof DeleteContentSignal)
        {
            $data = json_encode([
                'contentId' => $signal->contentId
            ]);

            $message = sprintf("event: delete\ndata: $data");

            $this->pheanstalk->put($message);
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            MVCEvents::API_SIGNAL => 'onApiSignal'
        ];
    }
}
