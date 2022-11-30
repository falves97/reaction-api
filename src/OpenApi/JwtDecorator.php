<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;

class JwtDecorator implements \ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }


    /**
     * @inheritDoc
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'readOnly' => true
                ]
            ]
        ]);

        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'username' => [
                    'type' => 'string',
                    'example' => '123456 or user@email.com'
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'apassword'
                ]
            ]
        ]);

        $schemas['RefreshToken'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'refresh_token' => [
                    'type' => 'string'
                ]
            ]
        ]);

        $schemas = $openApi->getComponents()->getSecuritySchemes() ?? [];
        $schemas['JWT'] = new \ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ]);

        $tokenPathItem = new PathItem(
            ref: 'JWT Token',
            post: new Operation(
                operationId: 'postCredentialsItem',
                tags: ['Token'],
                responses: [
                    '200' => [
                        'description' => 'Get JWT Token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token'
                                ]
                            ]
                        ]
                    ]
                ],
                summary: 'Get JWT Token to login.',
                requestBody: new RequestBody(
                    description: 'Generate new JWT Token',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials'
                            ]
                        ]
                    ])
                ),
                security: []
            ),
        );

        $refreshTokenpathItem = new PathItem(
            ref: 'JWT Token',
            post: new Operation(
                operationId: 'postRefreshTokenItem',
                tags: ['Token'],
                responses: [
                    '200' => [
                        'description' => 'Get Refresh JWT Token',
                        'content' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Token'
                            ]
                        ]
                    ]
                ],
                summary: 'Get Refresh JWT Token',
                requestBody: new RequestBody(
                    description: 'Refresh JWT Token',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/RefreshToken'
                            ]
                        ]
                    ])
                ),
                security: []
            )
        );

        $openApi->getPaths()->addPath('/api/login', $tokenPathItem);
        $openApi->getPaths()->addPath('/api/token/refresh', $refreshTokenpathItem);

        return $openApi;
    }
}