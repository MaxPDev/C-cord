<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Room;
use App\Entity\Stream;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\RoomRepository;
use App\Repository\StreamRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Response; //? Utile ?
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Gestions des droits
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class RoomController extends AbstractController
{
    #[Route('/api/rooms', name: 'ccord_getAllRooms', methods: ['GET'])]
    public function getAllRooms(
        RoomRepository $roomRepository,
        SerializerInterface $serializer,
        Request $request, //* Pour pagination
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        // N° de page
        $page = $request->get('page', 1);
        // Limite de résultat par page
        $limit = $request->get('limit', 3);
        
        // ID pour la mise en cache
        $idCache = "getAllRooms-" . $page . "-" . $limit;

        // Retour de l'élément mis en cache, sinon récupération depuis le repository
        $rooms = $cachePool->get(
            $idCache, 
            function (ItemInterface $item) use ($roomRepository, $page, $limit)
            {
                // Tag pour le nettoyage du cache
                $item->tag("roomsCache");

                // retour de la récupération des données
                return $roomRepository->findAllWithPagination($page, $limit);
            });

        // $rooms = $roomRepository->findAllWithPagination($page, $limit);

        // Sérialisation de l'objet en JSON
        $rooms_JSON = $serializer->serialize(
            $rooms,
            'json', 
            ['groups' => 'getAllRooms']);

        // Retour de la liste des Rooms en JSON
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


    #[Route("/api/users/{id}/rooms", name:"ccord_getRoomsByUser", methods: ["GET"])]
    public function getRoomsByUser(
        User $user,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $room_JSON = $serializer->serialize(
            $user->getRoom(),
            "json", 
            ['groups' => 'getAllRooms']); //? créer un groupe getRoomsByUser avec l'lid USer ?

        return new JsonResponse(
            $room_JSON,
            Response::HTTP_OK,
            ['accept'=>'json'],
            true);
    }

    #[Route('/api/rooms', name:'ccord_createRoom', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas la permission de créer une Room.')]
    public function createRoom(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse
    {
        // Création de l'objet
        $room = $serializer->deserialize($request->getContent(), Room::class,'json');
        
        // Validation du format des données
        $errors = $validator->validate($room);

        if ($errors->count() > 0) {

            //* Si utilisation du subscriber :
            // throw new HttpException(
            //     JsonResponse::HTTP_BAD_REQUEST,
            //     "Mauvaise requête"
            // );

            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }
        
        // Peristence et écriture de la BD
        $em->persist($room);
        $em->flush();

        // Objet sérializé en JSON pour envoyer un retour de ce qui est crée
        $room_JSON = $serializer->serialize($room, 'json', ['groups'=> 'getRoom']);

        // Applle une route, on utilise de nom de la route de GET Room
        $location = $urlGenerator->generate(
            'ccord_getRoom', 
            ['id' => $room->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $room_JSON, 
            JsonResponse::HTTP_CREATED, 
            ['location'=> $location], 
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
        Room $currentRoom,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $updatesRoom = $serializer->deserialize(
            $request->getContent(), 
            Room::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentRoom]);

        $errors = $validator->validate($updatesRoom);
        if($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }

        $em->persist($updatesRoom);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/rooms/{id}', name:'ccord_deleteRoom', methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas la permission de créer une Room.')]
    public function deleteRoom(
        Room $room,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $em->remove($room);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


}
