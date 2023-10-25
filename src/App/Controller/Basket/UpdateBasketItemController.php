<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use App\Security\BasketAccess;
use Domain\Basket\Command\UpdateBasketItemCommand;
use Domain\Basket\Model\ReadBasketItem;
use Domain\Basket\Model\WriteBasketItem;
use Domain\Basket\Query\ShowBasketItemQuery;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Infrastructure\Http\UpdateBasketItemDto;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route(
    '/basket/{basket_identifier}/item/{item_identifier}',
    name: 'api_update_basket_item',
    methods: ['PUT']
)]
final class UpdateBasketItemController extends AbstractBasketController
{
    #[OA\Tag(name: 'Basket')]
    #[OA\Put(
        summary: 'Update a Basket Item',
        requestBody: new OA\RequestBody(content: new Model(type: UpdateBasketItemDto::class)),
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
                example: 'aed7fc4a-ea5d-3884-b6a9-ee1db0d102f7'
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
        response: Response::HTTP_OK,
        description: 'The updated item.',
        content: new Model(type: ReadBasketItem::class)
    )]
    public function __invoke(
        #[MapEntity(mapping: ['basket_identifier' => 'identifier'])]
        Basket $basket,
        #[MapEntity(mapping: ['item_identifier' => 'identifier'])]
        BasketItem $basketItem,
        #[MapRequestPayload]
        UpdateBasketItemDto $updateItemPayload,
    ): JsonResponse {
        $this->basketAccess->assertCanAccessBasket($basket);

        // avoid subresource access through other resource
        if (!$basket->getBasketItems()->contains($basketItem)) {
            throw new NotFoundHttpException('Basket Item not found.');
        }

        $result = $this->handle(
            $this->mapToCommand($basket, $basketItem, $updateItemPayload)
        );

        if (false === $result instanceof BasketItem) {
            return new JsonResponse(
                ['message' => 'Failed to update basket item'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->showUpdatedItem($result);
    }

    private function mapToCommand(
        Basket $basket,
        BasketItem $basketItem,
        UpdateBasketItemDto $updateItemPayload
    ): UpdateBasketItemCommand {
        return new UpdateBasketItemCommand(
            basket: $basket,
            item: new WriteBasketItem(
                productIdentifier: $basketItem->getProduct()->getIdentifier(),
                amount: $updateItemPayload->amount,
                basketItemIdentifier: $basketItem->getIdentifier()
            )
        );
    }

    private function showUpdatedItem(BasketItem $basketItem): JsonResponse
    {
        $query = new ShowBasketItemQuery($basketItem);
        $updatedReadModel = $this->handle($query);

        $serializedProducts = $this->serializer->serialize(
            $updatedReadModel,
            'json' // TODO: infere format via content negotiation $request->getRequestFormat()
        );

        return new JsonResponse(
            $serializedProducts,
            headers: ['Content-Type' => 'application/json'],
            json: true
        );
    }
}
