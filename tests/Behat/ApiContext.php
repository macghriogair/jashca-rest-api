<?php

declare(strict_types=1);

namespace Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\RawMinkContext;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class ApiContext extends RawMinkContext implements Context
{
    private ?Response $lastResponse = null;

    /**
     * @var array|string[]
     */
    private array $defaultHeaders;

    public function __construct(
        //private readonly KernelInterface $kernel,
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
        /** @var BrowserKitDriver $driver */
        $driver = $this->getSession()->getDriver();
        /** @var KernelBrowser $client */
        $client = $driver->getClient();
        $client->request(
            $method,
            $path,
            server: $this->defaultHeaders,
            content: $payload->getRaw(),
        );

        $this->lastResponse = $client->getResponse();
    }

    /**
     * @Then the response status code should be :status
     */
    public function theResponseStatusCodeShouldBe(int $status): void
    {
        Assert::assertSame($status, $this->lastResponse?->getStatusCode());
    }

    /**
     * @Then the response is valid json
     */
    public function theResponseIsValidJson(): void
    {
        Assert::assertJson((string)$this->lastResponse?->getContent());
    }

    /**
     * @Then I should see a valid JWT for :email in the :key field of the response
     */
    public function iShouldSeeAJwtInTheFieldOfTheResponse(string $email, string $key): void
    {
        $jsonData = json_decode((string)$this->lastResponse?->getContent(), true, flags: JSON_THROW_ON_ERROR);
        Assert::assertArrayHasKey($key, $jsonData);

        $decodedJwt = $this->jwtDecoder->decode($jsonData[$key]);
        Assert::assertEqualsCanonicalizing(
            ['iat', 'exp', 'email', 'roles'],
            array_keys($decodedJwt),
            'Expected to see all claims in the decoded JWT'
        );
        Assert::assertSame($email, $decodedJwt['email']);
    }

//    protected function getMinkContext(): MinkContext {
//        $context = new MinkContext();
//        $context->setMink($this->getMink());
//        $context->setMinkParameters($this->getMinkParameters());
//
//        return $context;
//    }
}
