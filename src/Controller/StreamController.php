<?php

namespace App\Controller;

use App\Entity\Stream;
use App\Repository\MessageRepository;
use App\Repository\RoomRepository;
use App\Repository\StreamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    #[Route(path:'api/rooms/{id}/streams', name:'ccord_getStreamsByRoom', methods: ['GET'])]
    public function getStreamsByRoom(
        int $id,
        SerializerInterface $serializer,
        StreamRepository $streamRepository
    ): JsonResponse
    {
        $streamsByRoom = $streamRepository->findByRoom($id);

        $streams_JSON = $serializer->serialize(
            $streamsByRoom,
            "json",
            ['groups' => 'getStreams']);

        return new JsonResponse(
            $streams_JSON, 
            Response::HTTP_OK, 
            [], 
            true);
    }
        //? Dans StreamController ?
    #[Route('/api/rooms/{id}/stream', name:'ccord_createStreamByRoom', methods: ['POST'])]
    public function createStreamByRoom(
        int $id, //? Ou Room $room ? Tester les deux
        // Room $room,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        RoomRepository $roomRepository
    ): JsonResponse
    {
        $stream = $serializer->deserialize(
            $request->getContent(),
            Stream::class,
            'json'
        );

        //* On définit ici la room d'où le stream est crée, ce n'est pas le client qui décide
        //todo: faire pareil pour messages
        $stream->setRoom($roomRepository->find($id));

        //* Deux options équivalentes
        // print_r($id);
        // print_r($room->getId());

        $em->persist($stream);
        $em->flush();

        $stream_JSON = $serializer->serialize($stream,'json', ['groups' => 'getStreams']);

        $location = $urlGenerator->generate(
            'ccord_getOneStream',
            ['id'=> $stream->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $stream_JSON,
            Response::HTTP_CREATED,
            ['Location'=> $location],
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

    #[Route(path:'/api/streams/{id}', name:'ccord_deleteStream', methods: ['DELETE'])]
    public function deleteStream(
        Stream $stream,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $em->remove($stream);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
