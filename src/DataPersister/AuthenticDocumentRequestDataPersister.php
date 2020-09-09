<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;

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
        $api = $this->api;
        $api->createAuthenticDocumentRequestMessage($authenticImageRequest);
        $type = $authenticImageRequest->getType() ?? 'generic';

        // TODO: Is there a better identifier (not that we would need one)
        $authenticImageRequest->setIdentifier($type.'-'.time());

        return $authenticImageRequest;
    }

    /**
     * @param AuthenticDocumentRequest $authenticImageRequest
     */
    public function remove($authenticImageRequest)
    {
    }
}
