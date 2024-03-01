<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message'=> $exception->getMessage(),

                //? Pas utilie, puisque dans le cas de HTTP EXCEPTION ?
                'line' => $exception->getCode(),
                'getTrace' => $exception->getTrace()
            ];

            $event->setResponse(new JsonResponse($data));
        } else {
            $data = [
                'status'=> 500,
                'message'=> $exception->getMessage(),

                //? Pas utilie ?
                'line' => $exception->getCode(),
                'getTrace' => $exception->getTrace()
            ];

            $event->setResponse(new JsonResponse($data));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
