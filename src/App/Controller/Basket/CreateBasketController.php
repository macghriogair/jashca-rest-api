<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use Domain\Basket\Command\CreateBasketCommand;
use Domain\Basket\Exception\DomainException;
use Domain\Basket\Model\WriteBasketItem;
use Domain\Entity\Basket;
use Infrastructure\Http\BasketItemDto;
use Infrastructure\Http\CreateBasketDto;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
#[Route('/basket', name: 'api_basket_create', methods: ['POST'])]
final class CreateBasketController implements LoggerAwareInterface
{
    use HandleTrait;
    use LoggerAwareTrait;

    // TODO: service!
    private const GUEST_TOKEN_HEADER = 'X-GUEST-TOKEN';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        /** @phpstan-ignore-next-line required by HandleTrait */
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateBasketDto $createBasketDto,
    ): Response {
        $result = null;
        try {
            $result = $this->handle(
                $this->mapToCommand($createBasketDto)
            );
        } catch (HandlerFailedException $e) {
            $this->logger->error($e->getMessage(), [$e]);
            if ($e->getPrevious() instanceof DomainException) {
                // rethrow the inner exception, let the global error handler take care
                throw $e->getPrevious();
            }
        }

        if (false === $result instanceof Basket) {
            return new JsonResponse(
                ['message' => 'Failed to create basket'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response(
            null,
            Response::HTTP_CREATED,
            headers: ['Location' => '/api/basket/' . $result->getIdentifier()]
        );
    }

    private function mapToCommand(CreateBasketDto $createBasketDto): CreateBasketCommand
    {
        // either an authenticated user or a guest
        if (null === ($user = $this->tokenStorage->getToken()?->getUser())) {
            $req = $this->requestStack->getCurrentRequest();
            if ($req->headers->get(self::GUEST_TOKEN_HEADER)) {
                $guestToken = $req->headers->get(self::GUEST_TOKEN_HEADER);
            } else {
                // TODO: use jwt someday for adding trusted data
                $guestToken = Uuid::uuid4()->toString();
            }
        }

        return new CreateBasketCommand(
            items: array_map(
                fn (BasketItemDto $itemDto) => new WriteBasketItem(
                    Uuid::fromString($itemDto->productId),
                    $itemDto->amount
                ),
                $createBasketDto->items
            ),
            user: $user,
            guestToken: $guestToken ?? null
        );
    }
}
