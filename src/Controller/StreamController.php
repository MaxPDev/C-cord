<?php

namespace App\Controller;

use App\Entity\Stream;
use App\Repository\MessageRepository;
use App\Repository\StreamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class StreamController extends AbstractController
{
    //todo: obtenir stream / room :roo/id/stream | et stream message : stream/id/messag

    //* Route uniquement pour dev
    #[Route(path:"/api/streams", name:"ccord_streams", methods: ["GET"])]
    public function getAllStream(
        SerializerInterface $serializer, 
        StreamRepository $streamRepository
    ): JsonResponse
    {
        $streams = $streamRepository->findAll();
        $streams_JSON = $serializer->serialize(
            $streams,
            "json",
            ['groups' => 'getStreams']);

            //todo: renvoyer Link plutôt que id/name de room
        
            return new JsonResponse(
                $streams_JSON, 
                Response::HTTP_OK, 
                [], 
                true);
    }

    #[Route(path:"/api/streams/{id}/messages", name:"ccord_getMessageByStream", methods: ["GET"])]
    public function getMessagesByStream(
        Stream $stream,
        SerializerInterface $serializer,
        MessageRepository $messageRepository
        ): JsonResponse
    {
        $messages = $messageRepository->findByStream($stream);
        $messages_JSON = $serializer->serialize(
            $messages,
            "json",
            ['groups' => 'getMessage' ]);

        return new JsonResponse(
            $messages_JSON,
            Response::HTTP_OK,
            [],
            true);
    }
}
