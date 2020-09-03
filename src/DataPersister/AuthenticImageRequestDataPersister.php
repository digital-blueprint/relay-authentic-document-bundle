<?php

declare(strict_types=1);

namespace DBP\API\EgizImageBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\EgizImageBundle\Entity\AuthenticImageRequest;
use DBP\API\EgizImageBundle\Service\EgizImageApi;

final class AuthenticImageRequestDataPersister implements DataPersisterInterface
{
    private $api;

    public function __construct(EgizImageApi $api)
    {
        $this->api = $api;
    }

    public function supports($data): bool
    {
        return $data instanceof AuthenticImageRequest;
    }

    /**
     * @param AuthenticImageRequest $authenticImageRequest
     *
     * @return AuthenticImageRequest
     */
    public function persist($authenticImageRequest)
    {
        $api = $this->api;
        $api->createAuthenticImageRequest($authenticImageRequest);

        return $authenticImageRequest;
    }

    /**
     * @param AuthenticImageRequest $authenticImageRequest
     */
    public function remove($authenticImageRequest)
    {
    }
}
