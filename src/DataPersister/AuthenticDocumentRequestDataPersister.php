<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AuthenticDocumentRequestDataPersister extends AbstractController implements ContextAwareDataPersisterInterface
{
    private $api;

    private $requestStack;

    public function __construct(AuthenticDocumentApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof AuthenticDocumentRequest;
    }

    /**
     * @param AuthenticDocumentRequest $data
     *
     * @return AuthenticDocumentRequest
     */
    public function persist($data, array $context = [])
    {
        $authenticDocumentRequest = $data;
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $filters = $context['filters'] ?? [];
        $token = $filters['token'] ?? $this->requestStack->getCurrentRequest()->headers->get('token');
        if ($token === null) {
            throw new BadRequestHttpException("Missing 'token' parameter or header");
        }

        $typeId = $authenticDocumentRequest->getTypeId();
        if ($typeId === '') {
            throw new ItemNotStoredException('typeId is mandatory!');
        }

        // TODO: Is there a better identifier? (not that we would need one)
        $authenticDocumentRequest->setIdentifier($typeId.'-'.time());
        $authenticDocumentRequest->setDateCreated(new \DateTimeImmutable());
        $message = $this->api->createAndDispatchAuthenticDocumentRequestMessage($authenticDocumentRequest, $token);

        $authenticDocumentRequest->setEstimatedTimeOfArrival($message->getEstimatedResponseDate());

        return $authenticDocumentRequest;
    }

    /**
     * @param AuthenticDocumentRequest $data
     */
    public function remove($data, array $context = [])
    {
    }
}
