<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocument;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AuthenticDocumentItemDataProvider extends AbstractController implements ItemDataProviderInterface, RestrictedDataProviderInterface
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
        return AuthenticDocument::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?AuthenticDocument
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $api = $this->api;
        $filters = $context['filters'] ?? [];

        $token = $filters['token'] ?? $this->requestStack->getCurrentRequest()->headers->get('token');
        if ($token === null) {
            throw new BadRequestHttpException("Missing 'token' parameter or header");
        }
        assert($token !== null);

        return $api->getAuthenticDocument($id, $token);
    }
}
