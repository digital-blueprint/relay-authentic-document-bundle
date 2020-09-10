<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use Symfony\Component\HttpFoundation\Request;

final class AuthenticDocumentTypeCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(AuthenticDocumentApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AuthenticDocumentType::class === $resourceClass;
    }

    /**
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $api = $this->api;
        $filters = $context['filters'] ?? [];

        // get the token as header variable if not set
        if (!isset($filters['token'])) {
            $request = Request::createFromGlobals();
            $filters['token'] = $request->headers->get('token');
        }

        $authenticDocumentTypes = $api->getAuthenticDocumentTypes($filters);

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }

        return new ArrayFullPaginator($authenticDocumentTypes, $page, $perPage);
    }
}
