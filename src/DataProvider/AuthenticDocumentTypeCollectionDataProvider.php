<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AuthenticDocumentTypeCollectionDataProvider extends AbstractController implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

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

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $api = $this->api;
        $filters = $context['filters'] ?? [];

        $token = $filters['token'] ?? $this->requestStack->getCurrentRequest()->headers->get('token');
        if ($token === null) {
            throw new BadRequestHttpException("Missing 'token' parameter or header");
        }
        assert($token !== null);

        $authenticDocumentTypes = $api->getAuthenticDocumentTypes($token);

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
