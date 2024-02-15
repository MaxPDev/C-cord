<?php

namespace App\Controller;

use App\Entity\Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends AbstractController
{
    #[Route('/api/messages/{id}', name: 'message', methods: ['GET'])]
    public function getOneMessage(
        Message $message,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $message_JSON = $serializer->serialize($message, 'json', ['groups' => 'getMessage']);
        return new JsonResponse($message_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }
}
