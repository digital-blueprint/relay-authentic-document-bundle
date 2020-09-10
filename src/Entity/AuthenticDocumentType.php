<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Note: We need a "collectionOperations" setting for "get" to get an "entryPoint" in JSONLD.
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *             "method"="GET",
 *             "openapi_context"={
 *                 "parameters"={
 *                    {"name"="token", "in"="header", "description"="Token", "type"="string", "example"="photo-jpeg-available-token", "required"="true"}
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={"get"},
 *     iri="https://schema.tugraz.at/AuthenticDocumentType",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"AuthenticDocumentType:output"}},
 *     denormalizationContext={"groups"={"AuthenticDocumentRequest:input"}}
 * )
 */
class AuthenticDocumentType
{
    /**
     * @ApiProperty(identifier=true,iri="https://schema.org/identifier")
     * @Groups({"AuthenticDocumentType:output"})
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var string|null
     */
    private $urlSafeAttribute;

    /**
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var string|null
     */
    private $availabilityStatus;

    /**
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var string|null
     */
    private $documentToken;

    /**
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var \DateTime|null
     */
    private $expireData;

    /**
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var \DateTime|null
     */
    private $estimatedTimeOfArrival;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getUrlSafeAttribute(): ?string
    {
        return $this->urlSafeAttribute;
    }

    public function setUrlSafeAttribute(?string $urlSafeAttribute): self
    {
        $this->urlSafeAttribute = $urlSafeAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvailabilityStatus(): ?string
    {
        return $this->availabilityStatus;
    }

    /**
     * @param ?string $availabilityStatus
     * @return AuthenticDocumentType
     */
    public function setAvailabilityStatus(?string $availabilityStatus): self
    {
        $this->availabilityStatus = $availabilityStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentToken(): ?string
    {
        return $this->documentToken;
    }

    /**
     * @param ?string $documentToken
     * @return AuthenticDocumentType
     */
    public function setDocumentToken(?string $documentToken): self
    {
        $this->documentToken = $documentToken;

        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getExpireData(): ?\DateTime
    {
        return $this->expireData;
    }

    /**
     * @param ?\DateTime $expireData
     * @return AuthenticDocumentType
     */
    public function setExpireData(?\DateTime $expireData): self
    {
        $this->expireData = $expireData;

        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getEstimatedTimeOfArrival(): ?\DateTime
    {
        return $this->estimatedTimeOfArrival;
    }

    /**
     * @param ?\DateTime $estimatedTimeOfArrival
     * @return AuthenticDocumentType
     */
    public function setEstimatedTimeOfArrival(?\DateTime $estimatedTimeOfArrival): self
    {
        $this->estimatedTimeOfArrival = $estimatedTimeOfArrival;

        return $this;
    }
}
