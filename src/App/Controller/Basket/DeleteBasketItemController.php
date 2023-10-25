<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use App\Security\BasketAccess;
use Domain\Basket\Command\DeleteBasketItemCommand;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[AsController]
#[Route(
    '/basket/{basket_identifier}/item/{item_identifier}',
    name: 'api_delete_basket_item',
    methods: ['DELETE']
)]
final class DeleteBasketItemController extends AbstractBasketController
{
    #[OA\Tag(name: 'Basket')]
    #[OA\Delete(
        summary: 'Remove a Basket Item',
        parameters: [
            new OA\Parameter(
                name: 'basket_identifier',
                description: 'The Basket id',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: '4a63cb9a-7b00-33aa-aa58-c81d71464c9b'
            ),
            new OA\Parameter(
                name: 'item_identifier',
                description: 'The Basket Item id',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: '0d1ba937-0a11-4932-83b2-88a12cdb9d62'
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
        response: Response::HTTP_NO_CONTENT,
        description: 'Item was removed.',
    )]
    public function __invoke(
        #[MapEntity(mapping: ['basket_identifier' => 'identifier'])]
        Basket $basket,
        #[MapEntity(mapping: ['item_identifier' => 'identifier'])]
        BasketItem $basketItem,
    ): Response {
        $this->basketAccess->assertCanAccessBasket($basket);

        // avoid subresource access through other resource
        if (!$basket->getBasketItems()->contains($basketItem)) {
            throw new NotFoundHttpException('Basket Item not found.');
        }

        $this->handle(
            new DeleteBasketItemCommand($basket, $basketItem->getIdentifier())
        );

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
