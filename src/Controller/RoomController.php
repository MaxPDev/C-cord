<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{
    #[Route('/api/rooms', name: 'ccord_room', methods: ['GET'])]
    public function getRooms(RoomRepository $roomRepository): JsonResponse
    {
        
        $rooms = $roomRepository->findAll();

        return new JsonResponse([
            'rooms' => $rooms,
        ]);
    }
}
