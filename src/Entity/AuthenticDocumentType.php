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
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/authentic_document_types",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "method" = "GET",
 *             "openapi_context" = {
 *                 "tags" = {"AuthDoc"},
 *                 "parameters" = {
 *                     {"name" = "token", "in" = "header", "description" = "Token", "type" = "string", "example" = "photo-jpeg-available-token", "required" = true}
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/authentic_document_types/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "method" = "GET",
 *             "openapi_context" = {
 *                 "tags" = {"AuthDoc"},
 *                 "parameters" = {
 *                     {"name" = "id", "in" = "path", "description" = "Id", "type" = "string", "example" = "dummy-photo-jpeg-available", "required" = true},
 *                     {"name" = "token", "in" = "header", "description" = "Token", "type" = "string", "example" = "photo-jpeg-available-token", "required" = true}
 *                 }
 *             },
 *         },
 *     },
 *     iri="https://schema.tugraz.at/AuthenticDocumentType",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"AuthenticDocumentType:output"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"AuthenticDocumentRequest:input"}
 *     }
 * )
 */
class AuthenticDocumentType
{
    /**
     * @ApiProperty(identifier=true, iri="https://schema.org/identifier")
     * @Groups({"AuthenticDocumentType:output"})
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var string|null
     */
    private $availabilityStatus;

    /**
     * @Groups({"AuthenticDocumentType:output"})
     *
     * @var \DateTimeInterface|null
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

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param ?string $name
     *
     * @return AuthenticDocumentType
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

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
     *
     * @return AuthenticDocumentType
     */
    public function setAvailabilityStatus(?string $availabilityStatus): self
    {
        $this->availabilityStatus = $availabilityStatus;

        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getEstimatedTimeOfArrival(): ?\DateTimeInterface
    {
        return $this->estimatedTimeOfArrival;
    }

    /**
     * @param ?\DateTimeInterface $estimatedTimeOfArrival
     *
     * @return AuthenticDocumentType
     */
    public function setEstimatedTimeOfArrival(?\DateTimeInterface $estimatedTimeOfArrival): self
    {
        $this->estimatedTimeOfArrival = $estimatedTimeOfArrival;

        return $this;
    }
}
