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
 *                    {"name"="token", "in"="query", "description"="Token", "type"="string", "example"="photo-jpeg-available-token", "required"="true"}
 *                 }
 *             },
 *         },
      },
 *    itemOperations={"get"},
 *    shortName="AuthenticDocumentType",
 *    iri="https://schema.tugraz.at/AuthenticDocumentType",
 *    normalizationContext={"jsonld_embed_context"=true, "groups"={"AuthenticDocumentType:output"}}
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
     * @var string
     */
    private $name;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
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
}
