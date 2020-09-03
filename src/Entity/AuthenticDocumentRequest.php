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
 *             "openapi_context"={
 *                 "parameters"={
 *                    {"name"="token", "in"="body", "description"="Token", "type"="string", "example"="photo-jpeg-available-token", "required"="true"}
 *                 }
 *             },
 *         },
      },
 *     itemOperations={"get"},
 *     iri="https://schema.tugraz.at/AuthenticDocumentRequest",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"AuthenticDocument:output"}}
 * )
 */
class AuthenticDocumentRequest
{
    /**
     * @Groups({"AuthenticDocument:output"})
     * @ApiProperty(identifier=true,iri="https://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"AuthenticDocument:output"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="photo-jpeg-available-token"
     *         }
     *     }
     * )
     * @var string
     */
    private $token;

    /**
     * @ApiProperty(iri="http://schema.org/Text")
     * @Groups({"AuthenticDocument:output"})
     *
     * @var string
     */
    private $type;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
