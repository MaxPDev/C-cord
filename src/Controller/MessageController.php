<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\RoomRepository;
use App\Repository\StreamRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class MessageController extends AbstractController
{

    //! Route uniquement pour récupérer ID facilement pour dev. Inutile dans l'application ?
    #[Route('/api/messages', name: 'ccord_getMessages', methods: ['GET'])]
    public function getMessages(
        MessageRepository $messageRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $messages = $messageRepository->findAll();
        //! Même groupe  que getMessage, mais si route vraiment utilisé, repenser. (Que les ids ?)
        $messages_JSON = $serializer->serialize($messages, 'json', ['groups' => 'getMessage']);
        return new JsonResponse($messages_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }
    
    #[Route('/api/messages/{id}', name: 'ccord_getMessage', methods: ['GET'])]
    public function getOneMessage(
        Message $message,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $message_JSON = $serializer->serialize($message, 'json', ['groups' => 'getMessage']);
        return new JsonResponse($message_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }

    #[Route('/api/messages', name: 'ccord_createMessage', methods: ['POST'])]
    public function createMessage(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository,
        StreamRepository $streamRepository,
        RoomRepository $roomRepository
    ): JsonResponse
    {
        // Création de l'objet et insertion dans la DB
        $message = $serializer->deserialize($request->getContent(), Message::class, 'json');

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
     
        $em->persist($message);
        $em->flush();

        // Objet sérialisé en JSON pour envoyer un retour de ce qui est créé
        $message_JSON = $serializer->serialize($message, 'json', ['groups'=> 'getMessage']);

        // Appelle une route,on utilise le nom de la route de GET Message
        $location = $urlGenerator->generate(
            'ccord_getMessage', 
            ['id' => $message->getId()], 
            UrlGeneratorInterface::ABSOLUTE_URL);    

        return new JsonResponse($message_JSON, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    //? Route pour modifier le Stream du message et/ou route modifiant le contenu.
    //? Ici les deux pourl l'instant :
    #[Route('/api/messages/{id}', name: 'ccord_updateMessage', methods: ['PUT'])]
    public function udpateMessage(
        Request $request,
        SerializerInterface $serializer,
        Message $currentMessage,
        EntityManagerInterface $em,
        StreamRepository $streamRepository
    ): JsonResponse
    {
        $updatedMessage = $serializer->deserialize(
            $request->getContent(),
            Message::class,
            'json',
            //? On "repopulate" le message qui arrive de la requete
            //? Comment cette variable est utilisée ensuite ??
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentMessage]);

        $content = $request->toArray();
        
        // Seul le Stream d'un message doit être modifiable, on ne gère que lui
        $idStream = $content['idStream'] ?? -1;
        $updatedMessage->setStream($streamRepository->find($idStream));

        // On persiste $updateMessage pas $currentMessage pour persister uniquement les modifications
        $em->persist($updatedMessage);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/messages/{id}', name: 'ccord_deleteMessage', methods: ['DELETE'])]
    public function deleteMessage(
        Message $message,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $em->remove($message);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
