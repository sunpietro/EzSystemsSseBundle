<?php

namespace EzSystems\SseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    public function indexAction()
    {
        $pheanstalk = $this->get('ez_systems_sse.pheanstalk');

        $response =  new StreamedResponse(
            function() use($pheanstalk) {
                while (true)
                {
                    $message = $pheanstalk
                        ->reserve();

                    echo str_pad($message->getData() . "\n\n:", 4094);
                    echo "\n\n";
                    flush();
                    ob_flush();

                    $pheanstalk->delete($message);
                }
            }
        );

        $response->headers->set('Content-Type', 'text/event-stream');

        return $response;
    }
}
