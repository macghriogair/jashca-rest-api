<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use App\Security\BasketAccess;
use Domain\Basket\Command\AddItemToBasketCommand;
use Domain\Basket\Model\WriteBasketItem;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Infrastructure\Http\CreateBasketItemDto;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/basket/{identifier}', name: 'api_add_basket_item', methods: ['POST'])]
final class AddBasketItemController extends AbstractBasketController
{
    #[OA\Tag(name: 'Basket')]
    #[OA\Post(
        summary: 'Add new Item to Basket',
        requestBody: new OA\RequestBody(content: new Model(type: CreateBasketItemDto::class)),
        parameters: [
            new OA\Parameter(
                name: 'identifier',
                description: 'The Basket id',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: 'ae39b6a2-9f0a-33a9-9bcd-9b00b6a2cc17'
            ),
        ]
    )]
    #[OA\HeaderParameter(
        name: BasketAccess::GUEST_TOKEN_HEADER,
        description: 'Guest Token that was delivered via Create Basket Response.'
            . ' Required for non-authenticated requests',
        example: 'ae39b6a2-9f0a-33a9-9bcd-9b00b6a2cc17'
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Item was added to basket.',
    )]
    public function __invoke(
        Basket $basket,
        #[MapRequestPayload]
        CreateBasketItemDto $basketItemDto,
    ): Response {
        $this->basketAccess->assertCanAccessBasket($basket);

        $result = $this->handle(
            $this->mapToCommand($basket, $basketItemDto)
        );

        if (false === $result instanceof BasketItem) {
            return new JsonResponse(
                ['message' => 'Failed to add basket item'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response(
            null,
            Response::HTTP_CREATED,
            headers: [
                // TODO: use Router to generate the url
                'Location' => sprintf(
                    '/api/basket/%s/item/%s',
                    $basket->getIdentifier(),
                    $result->getIdentifier()
                ),
            ]
        );
    }

    private function mapToCommand(Basket $basket, CreateBasketItemDto $basketItemDto): AddItemToBasketCommand
    {
        return new AddItemToBasketCommand(
            basket: $basket,
            item: new WriteBasketItem(
                productIdentifier: Uuid::fromString($basketItemDto->productId),
                amount: $basketItemDto->amount
            )
        );
    }
}
