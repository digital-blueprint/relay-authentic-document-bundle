<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use Symfony\Component\HttpFoundation\RequestStack;

final class AuthenticDocumentRequestDataPersister implements DataPersisterInterface
{
    private $api;

    private $requestStack;

    public function __construct(AuthenticDocumentApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports($data): bool
    {
        return $data instanceof AuthenticDocumentRequest;
    }

    /**
     * @param AuthenticDocumentRequest $authenticImageRequest
     *
     * @return AuthenticDocumentRequest
     *
     * @throws \DBP\API\CoreBundle\Exception\ItemNotStoredException
     */
    public function persist($authenticImageRequest)
    {
        $typeId = $authenticImageRequest->getTypeId();

        if ($authenticImageRequest->getToken() == "" || $typeId == "") {
            throw new ItemNotStoredException("Token and typeId are mandatory!");
        }

        $api = $this->api;
        $authorizationHeader = $this->requestStack->getCurrentRequest()->headers->get('Authorization');
        $message = $api->createAndDispatchAuthenticDocumentRequestMessage($authenticImageRequest, $authorizationHeader);

        // TODO: Is there a better identifier? (not that we would need one)
        $authenticImageRequest->setIdentifier($typeId.'-'.time());
        $authenticImageRequest->setEstimatedTimeOfArrival($message->getEstimatedResponseDate());

        return $authenticImageRequest;
    }

    /**
     * @param AuthenticDocumentRequest $authenticImageRequest
     */
    public function remove($authenticImageRequest)
    {
    }
}
