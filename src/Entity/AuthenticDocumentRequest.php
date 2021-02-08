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
 *     collectionOperations={"get",
 *         "post"={
 *             "method"="POST",
 *             "status"=202,
 *             "openapi_context"={
 *                 "parameters"={
 *                    {"name"="body", "in"="path", "description"="Token", "type"="string", "example"={"token"="photo-jpeg-available-token", "typeId"="dummy-photo-jpeg-available"}, "required"=true}
 *                 }
 *             },
 *         },
      },
 *     itemOperations={"get"},
 *     iri="https://schema.tugraz.at/AuthenticDocumentRequest",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"AuthenticDocumentRequest:output"}},
 *     denormalizationContext={"groups"={"AuthenticDocumentRequest:input"}}
 * )
 */
class AuthenticDocumentRequest
{
    /**
     * @ApiProperty(identifier=true,iri="https://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"AuthenticDocumentRequest:output", "AuthenticDocumentRequest:input"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="photo-jpeg-available-token"
     *         }
     *     }
     * )
     *
     * @var string
     */
    private $token;

    /**
     * @ApiProperty(iri="http://schema.org/Text")
     * @Groups({"AuthenticDocumentRequest:output", "AuthenticDocumentRequest:input"})
     *
     * @var string
     */
    private $typeId;

    /**
     * @Groups({"AuthenticDocumentRequest:output"})
     *
     * @var \DateTime|null
     */
    private $estimatedTimeOfArrival;

    /**
     * @ApiProperty(iri="https://schema.org/dateCreated")
     * @Groups({"AuthenticDocumentRequest:output"})
     *
     * @var \DateTime
     */
    private $dateCreated;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getTypeId(): ?string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): self
    {
        $this->typeId = $typeId;

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
     *
     * @return AuthenticDocumentRequest
     */
    public function setEstimatedTimeOfArrival(?\DateTime $estimatedTimeOfArrival): self
    {
        $this->estimatedTimeOfArrival = $estimatedTimeOfArrival;

        return $this;
    }

    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @return AuthenticDocumentRequest
     */
    public function setDateCreated(\DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
