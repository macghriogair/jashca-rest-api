<?php

declare(strict_types=1);

namespace Tests\Functional\App;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiDocsTest extends WebTestCase
{
    use PHPMatcherAssertions;

    /**
     * @testWith ["/api/doc.json"]
     */
    public function testOpenApiDocsJson(string $docsUrl): void
    {
        $client = self::createClient();
        $client->request('get', $docsUrl);

        $response = $client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson((string)$response->getContent());
        self::assertMatchesPattern(
            <<<'JSON'
                    {
                    "openapi": "3.0.0",
                        "info": {
                            "title": "JASHCA OpenAPI Doc",
                            "description": "@string@",
                            "version": "@string@"
                        },
                        "servers": @array@,
                        "paths": {
                            "/api/login": "@json@",
                            "/api/basket/{identifier}": "@json@",
                            "/api/basket": "@json@",
                            "/api/basket/{basket_identifier}/item/{item_identifier}": "@json@",
                            "/api/product": "@json@"
                        },
                        "components": @array@,
                        "security": @array@
                    }
                JSON,
            $response->getContent(),
        );
    }
}
