<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use App\Security\BasketAccess;
use Domain\Basket\Model\ReadBasket;
use Domain\Basket\Query\ShowBasketQuery;
use Domain\Entity\Basket;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/basket/{identifier}', name: 'api_get_basket', methods: ['GET'])]
final class ShowBasketController extends AbstractBasketController
{
    #[OA\Tag(name: 'Basket')]
    #[OA\Get(summary: 'Show Basket by Id')]
    #[OA\Parameter(
        name: 'identifier',
        description: 'The Basket id',
        in: 'path',
        schema: new OA\Schema(type: 'string'),
        example: '4a63cb9a-7b00-33aa-aa58-c81d71464c9b'
    )]
    #[OA\HeaderParameter(
        name: BasketAccess::GUEST_TOKEN_HEADER,
        description: 'Guest Token that was delivered via Create Basket Response.'
        . ' Required for non-authenticated requests',
        example: 'ae39b6a2-9f0a-33a9-9bcd-9b00b6a2cc17'
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Show basket',
        content: new Model(type: ReadBasket::class)
    )]
    public function __invoke(Basket $basket): JsonResponse
    {
        $this->basketAccess->assertCanAccessBasket($basket);

        $result = $this->handle(
            new ShowBasketQuery($basket)
        );

        $serializedBasket = $this->serializer->serialize(
            $result ?? null,
            'json' // TODO: infere format via content negotiation $request->getRequestFormat()
        );

        return new JsonResponse(
            $serializedBasket,
            json: true
        );
    }
}
