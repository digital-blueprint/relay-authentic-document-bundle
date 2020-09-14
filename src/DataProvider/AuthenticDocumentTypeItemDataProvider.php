<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use Symfony\Component\HttpFoundation\RequestStack;

final class AuthenticDocumentTypeItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    private $requestStack;

    public function __construct(AuthenticDocumentApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AuthenticDocumentType::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?AuthenticDocumentType
    {
        $api = $this->api;
        $filters = $context['filters'] ?? [];

        // get the token as header variable if not set
        if (!isset($filters['token'])) {
            $filters['token'] = $this->requestStack->getCurrentRequest()->headers->get('token');
        }

        return $api->getAuthenticDocumentType($id, $filters);
    }
}
