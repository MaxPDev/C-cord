<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class MessageController extends AbstractController
{

    //! Route uniquement pour récupérer ID facilement pour dev. Inutile dans l'application ?
    #[Route('/api/messages/', name: 'messages', methods: ['GET'])]
    public function getMessages(
        MessageRepository $messageRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $messages = $messageRepository->findAll();
        //! Même groupe  que getMessage, mais si route vraiment utilisé, repenser. (Que les ids ?)
        $messages_JSON = $serializer->serialize($messages, 'json', ['groups' => 'getMessage']);
        return new JsonResponse($messages_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }
    
    #[Route('/api/messages/{id}', name: 'message', methods: ['GET'])]
    public function getOneMessage(
        Message $message,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $message_JSON = $serializer->serialize($message, 'json', ['groups' => 'getMessage']);
        return new JsonResponse($message_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }

    #[Route('/api/messages/{id}', name: 'deleteMessage', methods: ['DELETE'])]
    public function deleteMessage(
        Message $message,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $em->remove($message);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
