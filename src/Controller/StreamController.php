<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Stream;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class StreamController extends AbstractController
{
    //todo: obtenir stream / room :roo/id/stream | et stream message : stream/id/messag

    //* Route uniquement pour dev
    #[Route(path:"/api/streams", name:"ccord_getAllStreams", methods: ["GET"])]
    public function getAllStreams(
        SerializerInterface $serializer, 
        StreamRepository $streamRepository,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
{
        // N° Page, 1 par défaut
        $page = $request->get('page', 1);
        // limte de résultat par page, 10 par défaut
        $limit = $request->get('limit', 10);

        // ID pour la mise en cache
        $idCache = "getAllStreams" . $page . "-" . $limit;

        // Retour de l'élément mis en cache, sinon récupération depuis le repository
        $streams_JSON = $cachePool->get(
            $idCache,
            function(ItemInterface $item) use (
                $streamRepository, $page, $limit, $serializer)
            {
                // Tag pour le nettoyage du cache
                $item->tag("allStreamsCache");

                // Retour en JSON de la récupération des données (ici pour ByPass LazyLoading)
                return $serializer->serialize(
                    $streamRepository->findAllWithPagination($page, $limit),
                    "json",
                    ['groups' => 'getAllStreams']);
            }
        );

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
            ['groups' => 'getOneStream']);

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
            ['groups' => 'getAllStreams']);

        return new JsonResponse(
            $streams_JSON, 
            Response::HTTP_OK, 
            [], 
            true);
    }
  
    #[Route('/api/rooms/{id}/stream', name:'ccord_createStreamByRoom', methods: ['POST'])]
    public function createStreamByRoom(
        // int $id, //? Ou Room $room ? Tester les deux
        Room $room,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $stream = $serializer->deserialize(
            $request->getContent(),
            Stream::class,
            'json'
        );

        // Validation du format des données
        $errors = $validator->validate($stream);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }

        //* On définit ici la room d'où le stream est crée, ce n'est pas le client qui décide
        //todo: faire pareil pour messages
        $stream->setRoom($room);

        //* Deux options équivalentes
        // print_r($id);
        // print_r($room->getId());

        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allStreamsCache"]);

        // Insertion du Stream dans la BD
        $em->persist($stream);
        $em->flush();

        $stream_JSON = $serializer->serialize($stream,'json', ['groups' => 'getAllStreams']);

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
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $updatedStream = $serializer->deserialize(
            $request->getContent(),
            Stream::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentStream]);

        // Validation du format des données
        $errors = $validator->validate($updatedStream);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors,'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true);
        }

        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allStreamsCache"]);

        // Mise à jour dans la BD des modification du stream
        $em->persist($updatedStream);
        $em->flush();

        return new JsonResponse($currentStream, Response::HTTP_NO_CONTENT);
    }

    #[Route(path:'/api/streams/{id}', name:'ccord_deleteStream', methods: ['DELETE'])]
    public function deleteStream(
        Stream $stream,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        // Tag du cache des Messages invalidé
        $cachePool->invalidateTags(["allStreamsCache"]);

        // Suppresion du stream dans la BD
        $em->remove($stream);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
