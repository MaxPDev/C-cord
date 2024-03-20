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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
//* Serializer-pack annotations
// use Symfony\Component\Serializer\SerializerInterface;
//* JMS Serializer annotations
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;

class MessageController extends AbstractController
{

    //* Exemple de doc https://github.com/zircote/swagger-php/tree/master/Examples
    /**
     * **Retourne l'emsemble des Messages**
     * 
     * * Gère la mise en cache pour un exact même requête selon les paramètre de pagination
     * * Récupère les message depuis le repsository
     * * Règle les groupes de champs retournées
     * * Créer la réponse JSON, et l'envoie.
     * 
     * 
     * @param MessageRepository $messageRepository 
     * @param SerializerInterface $serializer JMS Serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[OA\Get(
        description: "Obtenir la liste des messages",
        tags: ["Messages"]
    )]
    #[OA\Response(
        response: 200,
        description: 'Tableau paginé de messages',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Message::class, groups: ['getOneMessage']))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Authorisation nécessaire',
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Numéro de page',
        schema: new OA\Schema(type: 'int')
        )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'limite de résultat par page',
        schema: new OA\Schema(type: 'int')
        )]
    // #[OA\Tag(
    //     name: "Message",
    //     description: "test de Description depuis tag"
    // )]
    //! Route uniquement pour récupérer ID facilement pour dev. Inutile dans l'application ?
    #[Route(path:'/api/messages', name: 'ccord_getAllMessages', methods: ['GET'])]
    public function getAllMessages(
        MessageRepository $messageRepository,
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
        $idCache = "getAllMessages-" . $page . "-" . $limit;

        //* Only for JMS Serializer
        // Context for group serializing
        $context = SerializationContext::create()->setGroups(['getOneMessage']);

        // Retour de l'élément mis en cache, sinon récupération depuis le repository
        $messages_JSON = $cachePool->get(
            $idCache,
            function(ItemInterface $item) use (
                $messageRepository, $page, $limit, $serializer, $context)
            {
                // Tag pour le nettoyage du cache
                $item->tag("allMessagesCache");

                // Retour en JSON de la récupération des données (bypass LazyLoading)
                return $serializer->serialize(
                    $messageRepository->findAllWithPagination($page, $limit),
                    'json',
                    // ['groups' => 'getOneMessage']
                    $context
                );
            });
        
        // Retour de la liste des Users en JSON
        return new JsonResponse(
            $messages_JSON, 
            Response::HTTP_OK, 
            ['accept'=>'json'], 
            true);
    }
    
    #[Route(path:'/api/messages/{id}', name: 'ccord_getOneMessage', methods: ['GET'])]
    public function getOneMessage(
        Message $message,
        SerializerInterface $serializer
    ): JsonResponse
    {
        //* Only for JMS Serializer
        // Context for group serializing
        $context = SerializationContext::create()->setGroups(['getOneMessage']);

        $message_JSON = $serializer->serialize(
            $message, 
            'json', 
            //* If Serialize-pack from Symfony is used
            // ['groups' => 'getOneMessage'])
            //* If JMS Serializer is used, so $context is
            $context
        );

        return new JsonResponse(
            $message_JSON,
             Response::HTTP_OK, 
             ['accept'=>'json'], 
             true);
    }

    #[Route(path:"/api/streams/{id}/messages", name:"ccord_getMessageByStream", methods: ["GET"])]
    public function getMessagesByStream(
        Stream $stream,
        SerializerInterface $serializer,
        MessageRepository $messageRepository
        ): JsonResponse
    {
        //* Only for JMS Serializer
        // Context for group serializing
        $context = SerializationContext::create()->setGroups(['getOneMessage']);

        $messages = $messageRepository->findByStream($stream);
        $messages_JSON = $serializer->serialize(
            $messages,
            "json",
            // ['groups' => 'getOneMessage' ]
            $context
        );

        return new JsonResponse(
            $messages_JSON,
            Response::HTTP_OK,
            [],
            true);
    }

    // #[Route(path:"/api/rooms/{id}/messages", name:"ccord_getMessageByStream", methods: ["GET"])]
    #[Route(path:"/api/rooms/{id}/messages", name:"ccord_getMessageByRoom", methods: ["GET"])]
    public function getMessagesByRoom(
        Room $room,
        SerializerInterface $serializer,
        MessageRepository $messageRepository
        ): JsonResponse
    {
        //* Only for JMS Serializer
        // Context for group serializing
        $context = SerializationContext::create()->setGroups(['getOneMessage']);

        $messages = $messageRepository->findByRoom($room);
        $messages_JSON = $serializer->serialize(
            $messages,
            "json",
            // ['groups' => 'getOneMessage' ]
            $context
        );

        return new JsonResponse(
            $messages_JSON,
            Response::HTTP_OK,
            [],
            true);
    }

    #[Route(path:"/api/users/{id}/messages", name:"ccord_getMessagesByUser", methods: ["GET"])]
    public function getMessagesByUser(
        User $user,
        SerializerInterface $serializer,
        MessageRepository $messageRepository
        ): JsonResponse
    {

        //* Only for JMS Serializer
        // Context for group serializing
        $context = SerializationContext::create()->setGroups(['getOneMessage']);

        $messages = $messageRepository->findByUser($user);
        $messages_JSON = $serializer->serialize(
            $messages,
            "json",
            // ['groups' => 'getOneMessage' ]
            $context
        );

        return new JsonResponse(
            $messages_JSON,
            Response::HTTP_OK,
            [],
            true);
    }

    #[Route('/api/messages', name: 'ccord_createMessage', methods: ['POST'])]
    public function createMessage(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository,
        StreamRepository $streamRepository,
        RoomRepository $roomRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $message = new Message();
        $context = DeserializationContext::create();
        $context->setAttribute('deserialization-constructor-target', $message);

        // Création de l'objet et insertion dans la DB
        $message = $serializer->deserialize(
            $request->getContent(), 
            Message::class, 
            'json',
            $context
        );

        // Validation du format des données
        $errors = $validator->validate($message);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }
        
        // On récupère tous les objets sous forme de tableau
        $content = $request->toArray();
        
        // Récupération des idRoom, idStream et idUser
        //! Gérer si null, ou si non existant
        $idUser = $content['idUser'] ?? -1;
        $idRoom = $content['idRoom'] ?? -1;
        $idStream = $content['idStream'] ?? -1;
        
        // Assignation des Steam, Room et User à l'objet message, null si non trouvé
        $message->setUser($userRepository->find($idUser));
        $message->setRoom($roomRepository->find($idRoom));
        $message->setStream($streamRepository->find($idStream));

     
        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allMessagesCache"]);

        // Insertion dans la BD
        $em->persist($message);
        $em->flush();

        //* Only for JMS Serializer
        // Context for group serializing
        $contextSer = SerializationContext::create()->setGroups(['getOneMessage']);

        // Objet sérialisé en JSON pour envoyer un retour de ce qui est créé
        $message_JSON = $serializer->serialize(
            $message, 
            'json', 
            // ['groups'=> 'getOneMessage']
            $contextSer
        );

        // Appelle une route,on utilise le nom de la route de GET Message
        $location = $urlGenerator->generate(
            'ccord_getOneMessage', 
            ['id' => $message->getId()], 
            UrlGeneratorInterface::ABSOLUTE_URL);    

        return new JsonResponse(
            $message_JSON,
             Response::HTTP_CREATED, 
             ["Location" => $location], 
             true);
    }

    //! Attention, un user pas présent dans la room ne doit pas pouvoir publier
    //todo: faire ce contrôle
    //! ça ou createMessageByStream, il faut choisir
    #[Route('/api/rooms/{id}/message', name:'ccord_createMessageByRoom', methods: ['POST'])]
    public function createMessageByRoom(
        Room $room,
        Request $request,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        StreamRepository $streamRepository,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $message = new Message();
        $context = DeserializationContext::create();
        $context->setAttribute('deserialization-constructor-target', $message);

        $message = $serializer->deserialize(
            $request->getContent(),
            Message::class,
            'json',
            $context
        );
        
        // Validation du format des données
        $errors = $validator->validate($message);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }
        
        $message->setRoom($room);

        $content = $request->toArray();

        $idUser = $content['idUser'] ?? -1;
        $idStream = $content['idStream'] ?? -1;        

        //todo fait automatique après dev de l'api user, uuid etc...
        $message->setUser($userRepository->find($idUser));
        $message->setStream($streamRepository->find($idStream));

        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allMessagesCache"]);

        // Insertion dans la BD
        $em->persist($message);
        $em->flush();

        //* Only for JMS Serializer
        // Context for group serializing
        $contextSer = SerializationContext::create()->setGroups(['getOneMessage']);

        // Objet sérialisé en JSON pour envoyer un retour de ce qui est créé
        $message_JSON = $serializer->serialize(
            $message, 
            'json', 
            $contextSer
        );

        // Appelle une route,on utilise le nom de la route de GET Message
        $location = $urlGenerator->generate(
            'ccord_getOneMessage', 
            ['id' => $message->getId()], 
            UrlGeneratorInterface::ABSOLUTE_URL);    

        return new JsonResponse(
            $message_JSON,
            Response::HTTP_CREATED, 
            ["Location" => $location], 
            true);

    }

    #[Route(path:"/api/streams/{id}/message", name:"ccord_createMessageByStream", methods: ["POST"])]
    public function createMessageByStream(
        Stream $stream,
        Request $request,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $message = new Message();
        $context = DeserializationContext::create();
        $context->setAttribute('deserialization-constructor-target', $message);

        // Création de l'objet message depuis les données en JSON reçues
        $newMessage = $serializer->deserialize(
            $request->getContent(),
            Message::class,
            "json",
            $context
        );

        // Validation du format des données
        $errors = $validator->validate($newMessage);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }

        // Attribution du Stream depuisde l'id de la route 
        $newMessage->setStream($stream);

        // Attribution de la room directement depuis la Stream (et non des données reçu)
        //todo créer un find depuis le streamRepository plutôt qu'une requette ! ?
        $newMessage->setRoom($stream->getRoom());

        // Récupération du contenu JSON de la rquête en tableau
        $content = $request->toArray();

        // Récupération de l'idUser depuis l'objet fait depuis les données
        $idUser = $content['idUser'] ?? -1;

        // Attribution de l'utilisation depuis l'id reçu des données
        $newMessage->setUser($userRepository->find($idUser));

        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allMessagesCache"]);

        // Peristance des données et écriture dans la BD
        $em->persist($newMessage);
        $em->flush();

        //* Only for JMS Serializer
        // Context for group serializing
        $contextSer = SerializationContext::create()->setGroups(['getOneMessage']);

        // Objet sérialisé en JSON pour envoyer un retour de ce qui est créé
        $message_JSON = $serializer->serialize(
            $newMessage, 
            'json', 
            // ['groups'=> 'getOneMessage']
            $contextSer
        );

        // Appelle une route,on utilise le nom de la route de GET Message
        $location = $urlGenerator->generate(
            'ccord_getOneMessage', 
            ['id' => $newMessage->getId()], 
            UrlGeneratorInterface::ABSOLUTE_URL);  

        return new JsonResponse(
            $message_JSON,
            Response::HTTP_CREATED, 
            ["Location" => $location], 
            true);
    }

    //? Route pour modifier le Stream du message et/ou route modifiant le contenu.
    //? Ici les deux pourl l'instant :
    #[Route('/api/messages/{id}', name: 'ccord_updateMessage', methods: ['PUT'])]
    public function udpateMessage(
        Request $request,
        SerializerInterface $serializer,
        Message $currentMessage,
        EntityManagerInterface $em,
        StreamRepository $streamRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        // //* JMS Serializer methode 1 : Recréer un objet
        // $newMessage = $serializer->deserialize(
        //     $request->getContent(), 
        //     Message::class,
        //     'json');
        // $currentMessage->setText($newMessage->getText());
        
        $context = DeserializationContext::create();
        $context->setAttribute('deserialization-constructor-target', $currentMessage);
        
        $updatedMessage = $serializer->deserialize(
            //* Version Symfony deserializer
            // $request->getContent(),
            // Message::class,
            // 'json',
            // //? On "repopulate" le message qui arrive de la requete
            // //? Comment cette variable est utilisée ensuite ??
            // [AbstractNormalizer::OBJECT_TO_POPULATE => $currentMessage]
            
            //* Version JMS Serializer methode 2 AVEC deserializer dans objet
            $request->getContent(),
            Message::class,
            // get_class($currentMessage),
            'json',
            $context
            );

        // Validation du format des données
        $errors = $validator->validate($updatedMessage);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }

        $content = $request->toArray();
        
        // Seul le Stream d'un message doit être modifiable, on ne gère que lui
        $idStream = $content['idStream'] ?? -1;
        $updatedMessage->setStream($streamRepository->find($idStream));

        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allMessagesCache"]);

        // On persiste $updateMessage pas $currentMessage pour persister uniquement les modifications
        $em->persist($updatedMessage);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/messages/{id}', name: 'ccord_deleteMessage', methods: ['DELETE'])]
    public function deleteMessage(
        Message $message,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allMessagesCache"]);

        // Suppresion de la BD
        $em->remove($message);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
