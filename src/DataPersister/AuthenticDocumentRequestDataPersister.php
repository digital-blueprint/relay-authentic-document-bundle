<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;

final class AuthenticDocumentRequestDataPersister implements DataPersisterInterface
{
    private $api;

    public function __construct(AuthenticDocumentApi $api)
    {
        $this->api = $api;
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
        $authenticDocumentType = $api->createAuthenticDocumentRequestMessage($authenticImageRequest);

        // TODO: Is there a better identifier (not that we would need one)
        $authenticImageRequest->setIdentifier($typeId.'-'.time());
        $authenticImageRequest->setEstimatedTimeOfArrival($authenticDocumentType->getEstimatedTimeOfArrival());

        return $authenticImageRequest;
    }

    /**
     * @param AuthenticDocumentRequest $authenticImageRequest
     */
    public function remove($authenticImageRequest)
    {
    }
}
