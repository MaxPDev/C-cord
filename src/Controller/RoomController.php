<?php

namespace App\Controller;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Response; //? Utile ?

class RoomController extends AbstractController
{
    #[Route('/api/rooms', name: 'rooms', methods: ['GET'])]
    public function getRooms(
        RoomRepository $roomRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        
        $rooms = $roomRepository->findAll();
        $rooms_JSON = $serializer->serialize($rooms,'json', ['groups' => 'getRooms']);
        return new JsonResponse($rooms_JSON, Response::HTTP_OK, [], true);
    }

    #[Route('/api/rooms/{id}', name:'room', methods: ['GET'])]
    public function getRoom(
        Room $room, 
        SerializerInterface $serializer
    ): JsonResponse
    {
        $room_JSON = $serializer->serialize($room, 'json', ['groups' => 'getRoom']);
        return new JsonResponse($room_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
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
}
