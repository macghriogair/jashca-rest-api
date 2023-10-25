<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use App\Security\BasketAccess;
use Domain\Basket\Command\CreateBasketCommand;
use Domain\Basket\Model\WriteBasketItem;
use Domain\Entity\Basket;
use Domain\Entity\User;
use Infrastructure\Http\CreateBasketItemDto;
use Infrastructure\Http\CreateBasketDto;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/basket', name: 'api_basket_create', methods: ['POST'])]
final class CreateBasketController extends AbstractBasketController
{
    #[OA\Tag(name: 'Basket')]
    #[OA\Post(
        summary: 'Create new Basket',
        requestBody: new OA\RequestBody(content: new Model(type: CreateBasketDto::class))
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Basket was created successfully',
    )]
    public function __invoke(
        #[MapRequestPayload] CreateBasketDto $createBasketDto,
    ): Response {
        // either an authenticated user or a guest
        if (null === ($user = $this->basketAccess->getCurrentUser())) {
            // TODO: use jwt someday for adding trusted data
            $guestToken = Uuid::uuid4()->toString();
        }

        /** @var User|null $user */
        $result = $this->handle(
            $this->mapToCommand($createBasketDto, $user, $guestToken ?? null)
        );

        if (false === $result instanceof Basket) {
            return new JsonResponse(
                ['message' => 'Failed to create basket'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response(
            null,
            Response::HTTP_CREATED,
            headers: [
                'Location' => '/api/basket/' . $result->getIdentifier(),
                BasketAccess::GUEST_TOKEN_HEADER => $result->getGuestToken(),
            ]
        );
    }

    private function mapToCommand(
        CreateBasketDto $createBasketDto,
        ?User $user = null,
        ?string $guestToken = null
    ): CreateBasketCommand {
        return new CreateBasketCommand(
            items: array_map(
                fn (CreateBasketItemDto $itemDto) => new WriteBasketItem(
                    Uuid::fromString($itemDto->productId),
                    $itemDto->amount
                ),
                $createBasketDto->items
            ),
            user: $user,
            guestToken: $guestToken
        );
    }
}
