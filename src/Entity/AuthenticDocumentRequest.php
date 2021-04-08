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
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')"
 *         },
 *         "post" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "method" = "POST",
 *             "status" = 202,
 *             "openapi_context" = {
 *                 "parameters" = {
 *                     {"name" = "body", "in" = "path", "description" = "Token", "type" = "string", "example" = {"token" = "photo-jpeg-available-token", "typeId" = "dummy-photo-jpeg-available"}, "required" = true}
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')"
 *         }
 *     },
 *     iri="https://schema.tugraz.at/AuthenticDocumentRequest",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"AuthenticDocumentRequest:output"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"AuthenticDocumentRequest:input"}
 *     }
 * )
 */
class AuthenticDocumentRequest
{
    /**
     * @ApiProperty(identifier=true, iri="https://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

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
     * @var \DateTimeInterface|null
     */
    private $estimatedTimeOfArrival;

    /**
     * @ApiProperty(iri="https://schema.org/dateCreated")
     * @Groups({"AuthenticDocumentRequest:output"})
     *
     * @var \DateTimeInterface
     */
    private $dateCreated;

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getTypeId(): ?string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): void
    {
        $this->typeId = $typeId;
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
     */
    public function setEstimatedTimeOfArrival(?\DateTimeInterface $estimatedTimeOfArrival): void
    {
        $this->estimatedTimeOfArrival = $estimatedTimeOfArrival;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }
}
