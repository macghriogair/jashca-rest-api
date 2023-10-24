<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use Domain\Basket\Command\AddItemToBasketCommand;
use Domain\Basket\Exception\DomainException;
use Domain\Basket\Model\WriteBasketItem;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Infrastructure\Http\BasketItemDto;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
#[Route('/basket/{identifier}', name: 'api_add_basket_item', methods: ['POST'])]
final class AddBasketItemController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use HandleTrait;

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
        Basket $basket,
        #[MapRequestPayload] BasketItemDto $basketItemDto,
    ): Response {
        // TODO: extract
        if (null !== ($user = $this->tokenStorage->getToken()?->getUser())) {
            if ($user->getUserIdentifier() !== $basket->getOwner()?->getUserIdentifier()) {
                throw new AccessDeniedHttpException();
            }
        }
        $req = $this->requestStack->getCurrentRequest();
        if (
            null === $user
            && $basket->getGuestToken() !== $req->headers->get(self::GUEST_TOKEN_HEADER)
        ) {
            throw new AccessDeniedHttpException();
        }

        try {
            $result = $this->handle(
                $this->mapToCommand($basket, $basketItemDto)
            );
        } catch (HandlerFailedException $e) {
            $this->logger->error($e->getMessage(), [$e]);
            // rethrow the inner exception, let the global error handler take care
            throw $e->getPrevious() instanceof DomainException ? $e->getPrevious() : $e;
        }

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

    private function mapToCommand(Basket $basket, BasketItemDto $basketItemDto): AddItemToBasketCommand
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
