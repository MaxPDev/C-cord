<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Stream;
use App\Repository\RoomRepository;
use App\Repository\StreamRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Response; //? Utile ?
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class RoomController extends AbstractController
{
    #[Route('/api/rooms', name: 'ccord_getRooms', methods: ['GET'])]
    public function getRooms(
        RoomRepository $roomRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        
        $rooms = $roomRepository->findAll();
        $rooms_JSON = $serializer->serialize(
            $rooms,
            'json', 
            ['groups' => 'getRooms']);

        return new JsonResponse(
            $rooms_JSON, 
            Response::HTTP_OK, 
            [], 
            true);
    }

    #[Route('/api/rooms/{id}', name:'ccord_getRoom', methods: ['GET'])]
    public function getRoom(
        Room $room, 
        SerializerInterface $serializer
    ): JsonResponse
    {
        $room_JSON = $serializer->serialize($room, 'json', ['groups' => 'getRoom']);
        return new JsonResponse($room_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }

    #[Route('/api/rooms', name:'ccord_createRoom', methods: ['POST'])]
    public function createRoom(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse
    {
        // Création de l'objet et insertion dans la DB
        $room = $serializer->deserialize($request->getContent(), Room::class,'json');
        $em->persist($room);
        $em->flush();

        // Objet sérializé en JSON pour envoyer un retour de ce qui est crée
        $room_JSON = $serializer->serialize($room, 'json', ['groups'=> 'getRoom']);

        // Applle une route, on utilise de nom de la route de GET Room
        $location = $urlGenerator->generate(
            'ccord_getRoom', 
            ['id' => $room->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($room_JSON, Response::HTTP_CREATED, ['location'=> $location], true);
    }

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

    // #[Route('/api/rooms/{id}', name:'room', methods: ['GET'])]
    // public function getRoom(int $id, 
    //     RoomRepository $roomRepository,
    //     SerializerInterface $serializer): JsonResponse
    // {
    //     $room = $roomRepository->find($id);   
    //     if ($room) {
    //         $room_JSON = $serializer->serialize($room, 'json', ['groups' => 'getRoom']);
    //         return new JsonResponse($room_JSON, Response::HTTP_OK, [], true);
    //     }
    //     return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    // }
    
    #[Route('/api/rooms/{id}', name:'ccord_updateRoom', methods: ['PUT'])]
    public function updateRoom(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        Room $currentRoom
    ): JsonResponse
    {
        $updatesRoom = $serializer->deserialize(
            $request->getContent(), 
            Room::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentRoom]);

        $em->persist($updatesRoom);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


}
