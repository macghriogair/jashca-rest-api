<?php

declare(strict_types=1);

namespace Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Domain\Entity\BasketStatus;
use Domain\Entity\Product;
use Domain\Entity\ProductPrice;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class ApiContext extends RawMinkContext implements Context
{
    private ?Response $lastResponse = null;

    /**
     * @var array|string[]
     */
    private array $defaultHeaders;

    /**
     * @var array|string[]
     */
    private array $currentHeaders = [];

    /**
     * @var array<string, mixed> $scopes
     */
    private array $scopes = [];

    public function __construct(
        //private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTEncoderInterface $jwtDecoder
    ) {
        $this->defaultHeaders = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    /**
     * @Given /^I send a "([^"]*)" request to "([^"]*)" with body:$/
     */
    public function iSendARequestToWithBody(string $method, string $path, PyStringNode $payload): void
    {
        $this->doRequest($method, $path, $payload);
    }

    /**
     * @Then I send a :method request to :path
     */
    public function iSendARequestTo(string $method, string $path): void
    {
        $this->doRequest($method, $path);
    }

    private function doRequest(string $method, string $path, ?PyStringNode $payload = null): void
    {
        /** @var BrowserKitDriver $driver */
        $driver = $this->getSession()->getDriver();
        /** @var KernelBrowser $client */
        $client = $driver->getClient();

        $serverHeaders = $this->defaultHeaders;
        foreach ($this->currentHeaders as $headerKey => $headerValue) {
            $serverHeaders['HTTP_' . strtoupper(str_replace('-', '_', $headerKey))] = $headerValue;
        }

        $client->request(
            $method,
            $path,
            server: $serverHeaders,
            content: $payload?->getRaw(),
        );

        $this->lastResponse = $client->getResponse();
    }

    /**
     * @Then the response status code should be :status
     */
    public function theResponseStatusCodeShouldBe(int $status): void
    {
        $this->getMinkContext()->assertResponseStatus($status);
    }

    /**
     * @Then the response is valid json
     */
    public function theResponseIsValidJson(): void
    {
        Assert::assertJson((string)$this->lastResponse?->getContent());
    }

    /**
     * @Then the response should be empty
     */
    public function theResponseShouldBeEmpty(): void
    {
        Assert::assertEmpty((string)$this->lastResponse?->getContent());
    }

    /**
     * @Then the response header :key should be present
     */
    public function theResponseHeaderShouldBePresent(string $key): void
    {
        $normalizedKey = self::normalizeHeaderKey($key);
        Assert::assertArrayHasKey($normalizedKey, $this->lastResponse?->headers->all());

        $this->scopes['lastResponseHeaders'] = $this->scopes['lastResponseHeaders'] ?? [];
        $this->scopes['lastResponseHeaders'][$normalizedKey] = $this->lastResponse?->headers->get($normalizedKey);
        Assert::assertNotEmpty($this->scopes['lastResponseHeaders'][$normalizedKey]);
    }

    /**
     * @When I set the last response header :headerKey in the request headers
     */
    public function iSetTheHeaderInTheRequestHeaders(string $headerKey): void
    {
        $headerKey = self::normalizeHeaderKey($headerKey);
        $this->currentHeaders[$headerKey] = $this->scopes['lastResponseHeaders'][$headerKey];
    }

    /**
     * @Then the :key header should point to the Resource URI under :path
     */
    public function theHeaderShouldPointToTheResourceUri(string $key, string $path): void
    {
        $normalizedKey = self::normalizeHeaderKey($key);
        $this->scopes['lastResourceUri'] = $this->lastResponse?->headers->get($normalizedKey);
        Assert::assertStringStartsWith($path, $this->scopes['lastResourceUri']  ?? '');
    }

    /**
     * @Then I send a :method request to the last Resource URI
     */
    public function whenISendARequestToTheLastResourceUri(string $method): void
    {
        Assert::assertNotNull($path = $this->scopes['lastResourceUri'] ?? null);
        $this->iSendARequestTo($method, $path);
    }

    /**
     * TODO: entity handling into own context
     * @Given a product with the following attributes:
     * @Given /^given a product with the following attributes:$/
     */
    public function aProductWithTheFollowingAttributes(TableNode $table): void
    {
        $attributes = $table->getRowsHash();

        $product = new Product();
        $product->setIdentifier(Uuid::fromString($attributes['identifier']));
        $product->setName($attributes['name']);
        $product->setPrice(new ProductPrice((int)$attributes['price']));
        $product->setStockQuantity((int)$attributes['stockQuantity']);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->scopes['product'] = $product;
    }

    /**
     * @Then the basket should contain the scoped product with amount :expectedAmount
     */
    public function theBasketShouldContainTheScopedProduct(int $expectedAmount): void
    {
        Assert::assertInstanceOf(Product::class, $product = $this->scopes['product'] ?? null);
        $jsonData = $this->decodeLastResponse();
        /** @var Product $product */
        Assert::assertSame(
            $jsonData['items'][0]['product']['id'],
            (string)$product->getIdentifier(),
        );
        Assert::assertSame(
            $jsonData['items'][0]['amount'],
            $expectedAmount
        );
    }

    /**
     * @Given a basket with the following attributes:
     */
    public function aBasketWithTheFollowingAttributes(TableNode $table): void
    {
        $attributes = $table->getRowsHash();

        $basket = new Basket();
        $basket->setIdentifier(Uuid::fromString($attributes['identifier']));
        if (!empty($attributes['guestToken'])) {
            $basket->setGuestToken($attributes['guestToken']);
        }
        $basket->setStatus(BasketStatus::from($attributes['status']));
        $this->entityManager->persist($basket);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->scopes['basket'] = $basket;
    }

    /**
     * @Then the basket item entity with identifier :identifier should have been deleted
     */
    public function theBasketItemEntityWithIdentifierShouldHaveBeenDeleted(string $identifier): void
    {
        Assert::assertNull(
            /** @phpstan-ignore-next-line findOneBy<field> exists */
            $this->entityManager->getRepository(BasketItem::class)->findOneByIdentifier($identifier),
            sprintf('Expected not to find basket item with identifier %s in database', $identifier)
        );
    }

    /**
     * @Given given a basket item with the following attributes:
     */
    public function givenABasketItemWithTheFollowingAttributes(TableNode $table): void
    {
        $attributes = $table->getRowsHash();

        $item = new BasketItem();
        $item->setIdentifier(Uuid::fromString($attributes['identifier']));
        $item->setBasket(
             /** @phpstan-ignore-next-line findOneBy<field> exists */
            $this->entityManager->getRepository(Basket::class)->findOneByIdentifier($attributes['basket'])
        );
        $item->setProduct(
            /** @phpstan-ignore-next-line findOneBy<field> exists */
            $this->entityManager->getRepository(Product::class)->findOneByIdentifier($attributes['product'])
        );
        $item->setQuantity((int)$attributes['quantity']);
        $this->entityManager->persist($item);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->scopes['basket_item'] = $item;
    }

    /**
     * @Then I should see a valid JWT for :email in the :key field of the response
     */
    public function iShouldSeeAJwtInTheFieldOfTheResponse(string $email, string $key): void
    {
        $jsonData = $this->decodeLastResponse();
        Assert::assertArrayHasKey($key, $jsonData);

        $decodedJwt = $this->jwtDecoder->decode($jsonData[$key]);
        Assert::assertEqualsCanonicalizing(
            ['iat', 'exp', 'email', 'roles'],
            array_keys($decodedJwt),
            'Expected to see all claims in the decoded JWT'
        );
        Assert::assertSame($email, $decodedJwt['email']);
    }
    /**
     * @Then the error message is: :expectedErrorDetail
     */
    public function theErrorMessageIs(string $expectedErrorDetail): void
    {
        $jsonData = $this->decodeLastResponse();
        Assert::assertSame('An error occurred', $jsonData['title'] ?? null,);
        Assert::assertSame($expectedErrorDetail, $jsonData['detail'] ?? null,);
    }

    /**
     * @When I set the request header :key to: :value
     */
    public function iSetTheRequestHeaderTo(string $key, string $value): void
    {
        $this->currentHeaders[$key] = $value;
    }

    /**
     * @Then the response json should be:
     */
    public function theResponseShouldBe(PyStringNode $json): void
    {
        $actualJson = $this->decodeLastResponse();
        Assert::assertEquals(json_decode($json->getRaw(), true, flags: JSON_THROW_ON_ERROR), $actualJson);
    }

    protected function getMinkContext(): MinkContext
    {
        $context = new MinkContext();
        $context->setMink($this->getMink());
        $context->setMinkParameters($this->getMinkParameters());

        return $context;
    }

    private static function normalizeHeaderKey(string $key): string
    {
        return strtolower($key);
    }

    /**
     * @return array<string,mixed>
     * @throws JsonException
     */
    private function decodeLastResponse(): array
    {
        return json_decode(
            (string)$this->lastResponse?->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }
}
