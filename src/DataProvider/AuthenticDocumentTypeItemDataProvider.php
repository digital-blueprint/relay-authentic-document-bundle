<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;

final class AuthenticDocumentTypeItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AuthenticDocumentType::class === $resourceClass;
    }

    /**
     * @param array|int|string $id
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?AuthenticDocumentType
    {
        assert(is_string($id));
        $authenticDocumentType = new AuthenticDocumentType();
        $authenticDocumentType->setIdentifier("Test");

        return $authenticDocumentType;
    }
}
