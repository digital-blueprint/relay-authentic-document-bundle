<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

final class AuthenticDocumentRequestDataPersister extends AbstractController implements DataPersisterInterface
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
     * @param AuthenticDocumentRequest $authenticDocumentRequest
     *
     * @return AuthenticDocumentRequest
     *
     * @throws \DBP\API\CoreBundle\Exception\ItemNotStoredException
     */
    public function persist($authenticDocumentRequest)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $typeId = $authenticDocumentRequest->getTypeId();

        if ($authenticDocumentRequest->getToken() === '' || $typeId === '') {
            throw new ItemNotStoredException('Token and typeId are mandatory!');
        }

        // TODO: Is there a better identifier? (not that we would need one)
        $authenticDocumentRequest->setIdentifier($typeId.'-'.time());
        $authenticDocumentRequest->setDateCreated(new \DateTimeImmutable());
        $api = $this->api;
        $message = $api->createAndDispatchAuthenticDocumentRequestMessage($authenticDocumentRequest);

        $authenticDocumentRequest->setEstimatedTimeOfArrival($message->getEstimatedResponseDate());

        return $authenticDocumentRequest;
    }

    /**
     * @param AuthenticDocumentRequest $authenticImageRequest
     */
    public function remove($authenticImageRequest)
    {
    }
}
