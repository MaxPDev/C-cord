<?php

namespace App\Controller;

Use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Response;
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
        $user_JSON = $serializer->serialize($user,'json', ['groups' => 'getOneUser']);

        return new JsonResponse($user_JSON, Response::HTTP_OK, ['accept'=>'json'], true);
    }

    //? Lorsque createUser : route "LinkUserAndRoom ?", ou et création d'une room en même temps mais non
}
