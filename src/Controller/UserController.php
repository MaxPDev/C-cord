<?php

namespace App\Controller;

use App\Entity\Room;
Use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'ccord_getUsers', methods: ['GET'])]
    public function getUsers(
        UserRepository $userRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $users = $userRepository->findAll();
        $users_JSON = $serializer->serialize($users,'json', ['groups' => 'getUsers']);

        return new JsonResponse($users_JSON, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name:'ccord_getUser', methods: ['GET'])]
    public function getOneUser(
        User $user,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $user_JSON = $serializer->serialize(
            $user,
            'json', 
            ['groups' => 'getOneUser']);

        return new JsonResponse(
            $user_JSON, 
            Response::HTTP_OK, 
            ['accept'=>'json'], 
            true);
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
            ['groups' => 'getRooms']); //? créer un groupe getRoomsByUser avec l'lid USer ?

        return new JsonResponse(
            $room_JSON,
            Response::HTTP_OK,
            ['accept'=>'json'],
            true);
    }

    #[Route('/api/users', name:'ccord_createUser', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator
        ): JsonResponse
        {
            //? 5 étape :
            //?  1. Désérializer le content JSON en Objet de la classe voulu
            //?  2. Faire persister et i'objet et inscrit dans la BD
            //?  3. Sérializer l'objet créé en JSON en vu du return
            //?  4. Obtenir l'URL de la ressource
            //?  5. Return le JSON obtenu, avec la réponse HTTP adéquate et la nouvel URI

            //! Contrôler pseudo unique

            $user = $serializer->deserialize(
                $request->getContent(), 
                User::class,
                'json');

            $em->persist($user);
            $em->flush();

            $user_JSON = $serializer->serialize(
                $user,
                'json', 
                ['groups'=> 'getOneUser']);

            //? Ou absolute PATH ? adresse uniquement à partir de /api
            //? Regarder version Slim, URL en var... recomprendre
            $location = $urlGenerator->generate(
                'ccord_getUser',
                ['id' => $user->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse(
                $user_JSON,
                Response::HTTP_CREATED,
                ["Location" => $location],
                true);
            
        }

    //! Sûrement à repenser / réécrire
    //TODO: room/id/user POST lorsqu'un user rentre dans une room : Faire avec le bon système d'authentification de user
    #[Route("/api/rooms/{id}/user", name:"ccord_userJoinRoom", methods: ["POST"])]
    public function userToRoom(
        Request $request,
        Room $room,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
    ):JsonResponse
    {
        // Récupération de l'id User
        $content = $request->toArray();
        $idUser = $content['idUser'];

        // Ajout de l'utilisateur dansl room
        $room->addUser($userRepository->find($idUser));

        // Peristance de la donnée et écriture dans la BD
        $em->persist($room);
        $em->flush();

        // Création de l'objet utilisateur
        $user = $em->getRepository(User::class)->find($idUser);

        // Sérialization de l'objet en JSON, de des Rooms de l'utilisateur
        $rommsByUser_JSON = $serializer->serialize(
            $user->getRoom(),
            'json',
            ['groups'=>'getRooms']);

        return new JsonResponse(
            $rommsByUser_JSON,
            Response::HTTP_CREATED,
            [],
            true
        );
    }

        #[Route("/api/users/{id}", name:"ccord_updateUser", methods: ["PUT"])]
        public function updateUser(
            Request $request,
            SerializerInterface $serializer,
            EntityManagerInterface $em,
            User $currentUser
        ): JsonResponse
        {
            $userUpdated = $serializer->deserialize(
                $request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

            $em->persist($userUpdated);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        
        }

        //? user to room, ou faire dans Room ? Essayons les deux
        //! Doublon avec room/{id}/user PUT, choisir leur utilité ou la plus pertinente

        //? ici user enter room, et dans room, room/id/user(existe déjà sousen tendu, idem avec stream)
        // peutêtre faire une root getRoom pour info, et Room/id/user pour obtenir user en détail, avec avatar et isamdin
}
