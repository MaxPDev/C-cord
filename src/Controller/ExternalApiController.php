<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalApiController extends AbstractController
{
    //* Exemple automatique avec Twig
    // #[Route('/external/api', name: 'app_external_api')]
    // public function index(): Response
    // {
    //     return $this->render('external_api/index.html.twig', [
    //         'controller_name' => 'ExternalApiController',
    //     ]);
    // }

    // DOC HTTP Client : https://symfony.com/doc/6.4/http_client.html#installation

    #[Route('/api/external/getSfDoc', name: 'external_api', methods: 'GET')]
    public function getSymfonyDoc(HttpClientInterface $httpClient): JsonResponse
    {
        $response = $httpClient->request(
            'GET',
            'https://api.github.com/repos/symfony/symfony-docs'
        );

        return new JsonResponse(
            $response->getContent(),
            $response->getStatusCode(),
            [],
            true
        );
    }
}
