<?php

namespace App\Controller;

use App\Entity\Stream;
use App\Repository\MessageRepository;
use App\Repository\StreamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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


    //* Utiliser pour retourner la route dans createStreamByRoom, si inutile ne pas le faire
    #[Route(path:'/api/streams/{id}', name:'ccord_getOneStream', methods: ['GET'])]
    public function getOneStream(
        Stream $stream,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        $stream_JSON = $serializer->serialize(
            $stream,
            'json',
            ['groups' => 'getStream']);

        return new JsonResponse(
            $stream_JSON,
            Response::HTTP_OK,
            ['accept'=>'json'],
            true);
        
    }

    #[Route(path:'/api/streams/{id}', name:'ccord_updateStream', methods: ['PUT'])]
    public function updateStream(
        Request $request,
        SerializerInterface $serializer,
        Stream $currentStream,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $updatedStream = $serializer->deserialize(
            $request->getContent(),
            Stream::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentStream]);

        $em->persist($updatedStream);
        $em->flush();

        return new JsonResponse($currentStream, Response::HTTP_NO_CONTENT);
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
