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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    #[Route(path:'/api/users', name: 'ccord_getAllUsers', methods: ['GET'])]
    public function getAllUsers(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        // N° de page, 1 par défaut
        $page = $request->get('page', 1);
        // limte de résultat par page, 10 par défaut
        $limit = $request->get('limit', 10);

        // ID pour la mise en cache
        $idCache = "getAllUsers-" . $page . "-" . $limit;

        // Retour de l'élément mis en cache, sinon récupération de puise le repository en JSON
        $users_JSON = $cachePool->get(
            $idCache,
            function (ItemInterface $item) use (
                $userRepository, $page, $limit, $serializer) {
                
                // Tag pour le nettoyage du cache
                $item->tag('allUsersCache');

                // Récupération des users depuis le repository
                $users = $userRepository->findAllWithPagination($page, $limit);

                // Retour sérializer en JSON de la récupération des données
                //* Sérializer en JSON ici pour "neutraliser" le lay loading de Doctrine,
                //* Les sous entités n'étant pas chargé avant utilisations, ici les sérizaliser
                //* Si on retournait une uniquement dpeuis le repository, et après cette fonction on serialisait,
                //* les sous entité ne seraient pas présentes, puisque non utilisées/chargées.
                return $serializer->serialize(
                    $users,
                    'json', 
                    ['groups' => 'getAllUsers']);        
            });
        

        // Retour de la liste des Users en JSON
        return new JsonResponse(
            $users_JSON, 
            Response::HTTP_OK, 
            [], 
            true);
    }

    #[Route(path:'/api/users/{id}', name:'ccord_getOneUser', methods: ['GET'])]
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

    #[Route(path:"/api/rooms/{id}/users", name:"ccord_getUsersByRoom", methods: ["GET"])]
    public function getUsersByRoom(
        Room $room,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        $roomsByUser_JSON = $serializer->serialize(
            $room->getUser(),
            'json',
            ['groups'=>'getAllUsers']);

        return new JsonResponse(
            $roomsByUser_JSON,
            Response::HTTP_OK,
            [],
            true);
        
    }

    #[Route(path:'/api/users', name:'ccord_createUser', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
        ): JsonResponse
        {
            //? 5 étape :
            //?  1. Désérializer le content JSON en Objet de la classe voulu
            //?  2. Vérifier les données avec un validateur
            //?  3. Faire persister et i'objet et inscrit dans la BD
            //?  4. Sérializer l'objet créé en JSON en vu du return
            //?  5. Obtenir l'URL de la ressource
            //?  6. Return le JSON obtenu, avec la réponse HTTP adéquate et la nouvel URI

            //! Contrôler pseudo unique
            //TODO: Faire ce contrôle

            $user = $serializer->deserialize(
                $request->getContent(), 
                User::class,
                'json');

            // Validation du format des données
            $errors = $validator->validate($user);
            if ($errors->count() > 0) {
                return new JsonResponse(
                    $serializer->serialize($errors,'json'),
                    JsonResponse::HTTP_BAD_REQUEST,
                    [],
                    true);
            }
            

            // Tags du cache des Users invalidé
            $cachePool->invalidateTags(["allUsersCache"]);

            // Insertion de l'user dans la BD
            $em->persist($user);
            $em->flush();

            $user_JSON = $serializer->serialize(
                $user,
                'json', 
                ['groups'=> 'getOneUser']);

            //? Ou absolute PATH ? adresse uniquement à partir de /api
            //? Regarder version Slim, URL en var... recomprendre
            $location = $urlGenerator->generate(
                'ccord_getOneUser',
                ['id' => $user->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse(
                $user_JSON,
                Response::HTTP_CREATED,
                ["Location" => $location],
                true);
            
        }
    

    //! Sûrement à repenser / réécrire A METTRE DANS ROOM ?
    //TODO: room/id/user POST lorsqu'un user rentre dans une room : Faire avec le bon système d'authentification de user
    #[Route("/api/rooms/{id}/user", name:"ccord_userJoinRoom", methods: ["POST"])]
    public function userToRoom(
        Request $request,
        Room $room,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ):JsonResponse
    {
        // Récupération de l'id User
        $content = $request->toArray();
        $idUser = $content['idUser'];

        // Ajout de l'utilisateur dansl room
        $room->addUser($userRepository->find($idUser));

        // Validation du format des données
        $errors = $validator->validate($room);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }
        

        // Peristance de la donnée et écriture dans la BD
        $em->persist($room);
        $em->flush();

        // Création de l'objet utilisateur
        //todo créer un find depuis le userRepository plutôt qu'une reqûte ! ?
        $user = $em->getRepository(User::class)->find($idUser);

        // Sérialization de l'objet en JSON, de des Rooms de l'utilisateur
        $rommsByUser_JSON = $serializer->serialize(
            $user->getRoom(),
            'json',
            ['groups'=>'getAllRooms']);

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
            User $currentUser,
            ValidatorInterface $validator,
            TagAwareCacheInterface $cachePool
        ): JsonResponse
        {
            $userUpdated = $serializer->deserialize(
                $request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

            // Validation du format des données
            $errors = $validator->validate($userUpdated);
            if ($errors->count() > 0) {
                return new JsonResponse(
                    $serializer->serialize($errors,'json'),
                    JsonResponse::HTTP_BAD_REQUEST,
                    [],
                    true);
            }
            
            // Tags du cache des Users invalidé
            $cachePool->invalidateTags(["allUsersCache"]);

            // Mise à jour dans le BD
            $em->persist($userUpdated);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        
        }

        #[Route(path:'/api/users/{id}', name:'ccord_deleteUser', methods: ['DELETE'])]
        public function deleteUser(
            User $user,
            EntityManagerInterface $em,
            TagAwareCacheInterface $cachePool
        ): JsonResponse
        {
            // Tags du cache des Users invalidé
            //* Pour que la prochaine requête d'obtention des users se fasse
            //* en allant chercher dans la BD
            $cachePool->invalidateTags(["allUsersCache"]);

            // Suppresion dans la base de données
            $em->remove($user);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        //? user to room, ou faire dans Room ? Essayons les deux
        //! Doublon avec room/{id}/user PUT, choisir leur utilité ou la plus pertinente

        //? ici user enter room, et dans room, room/id/user(existe déjà sousen tendu, idem avec stream)
        // peutêtre faire une root getRoom pour info, et Room/id/user pour obtenir user en détail, avec avatar et isamdin
}
