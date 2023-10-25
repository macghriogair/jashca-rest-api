<?php

declare(strict_types=1);

namespace App\Controller\Inventory;

use Domain\Inventory\Model\ReadProduct;
use Domain\Inventory\Query\ListProductsQuery;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
#[Route('/product', name: 'api_product_read', methods: ['GET'])]
final class GetProductListController
{
    use HandleTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        /** @phpstan-ignore-next-line required by HandleTrait */
        private MessageBusInterface $messageBus,
    ) {
    }

    #[OA\Tag(name: 'Product')]
    #[OA\Get(summary: 'List products')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List of available products',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ReadProduct::class))
        )
    )]
    public function __invoke(): JsonResponse
    {
        $result = $this->handle(new ListProductsQuery());
        $serializedProducts = $this->serializer->serialize(
            $result ?? [],
            'json' // TODO: infere format via content negotiation $request->getRequestFormat()
        );

        return new JsonResponse(
            $serializedProducts,
            headers: ['Content-Type' => 'application/json'],
            json: true
        );
    }
}
