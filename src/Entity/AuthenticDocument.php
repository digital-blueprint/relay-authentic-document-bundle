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
 *     collectionOperations={"get"},
 *     itemOperations={
 *         "get"={
 *             "openapi_context"={
 *                 "parameters"={
 *                    {"name"="id", "in"="path", "description"="Id of document to fetch", "required"="true", "type"="string", "example"="photo-jpeg-requested"},
 *                    {"name"="token", "in"="header", "description"="Token", "type"="string", "example"="photo-jpeg-available-token", "required"="true"}
 *                 }
 *             }
 *         }
 *     },
 *     iri="http://schema.org/MediaObject",
 *     description="Authentic document",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"AuthenticDocument:output"}}
 * )
 */
class AuthenticDocument
{
    /**
     * @Groups({"AuthenticDocument:output"})
     * @ApiProperty(identifier=true,iri="https://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"AuthenticDocument:output"})
     *
     * @var string
     */
    private $contentUrl;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"AuthenticDocument:output"})
     *
     * @var string
     */
    private $name;

    /**
     * @ApiProperty(iri="https://schema.org/contentSize")
     * @Groups({"AuthenticDocument:output"})
     *
     * @var int
     */
    private $contentSize;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getContentUrl()
    {
        return $this->contentUrl;
    }

    public function setContentUrl(string $contentUrl)
    {
        $this->contentUrl = $contentUrl;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getContentSize(): int
    {
        return $this->contentSize;
    }

    public function setContentSize(int $contentSize): self
    {
        $this->contentSize = $contentSize;

        return $this;
    }
}
