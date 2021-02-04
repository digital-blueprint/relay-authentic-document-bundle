<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\UCardPhoto;

class UCardPhotoService
{
    private $token;
    private $baseUrl;

    public function __construct(string $token)
    {
        $this->token = $token;
        // This is <instanz/dad>
        $this->baseUrl = 'https://online.tugraz.at/tug_online';
    }

    public function getCardForIdent(string $ident): UCard
    {
        // GET https://<instanz/dad>/pl/rest/brm.pm.extension.ucardfoto?access_token=TOKEN$filter=IDENT_NR_OBFUSCATED-eq=054792FDE3956438

        return new UCard();
    }

    public function createCardForIdent(string $ident, string $cardType): UCard
    {
        // POST https://<instanz/dad>/pl/rest/brm.pm.extension.ucardfoto?access_token=TOKEN$filter=IDENT_NR_OBFUSCATED-eq=054792FDE3956438
        // CARD_TYPE = STA
        // IDENT_NR_OBFUSCATED = 054792FDE3956438

        return new UCard();
    }

    public function getCardPhoto(UCard $card): string
    {
        // GET https://<instanz/dad>/pl/rest/brm.pm.extension.ucardfoto/${contentId}/content?access_token=TOKEN

        return 'some binary data';
    }

    public function setCardPhoto(UCard $card, string $data)
    {
        // POST https://<instanz/dad>/pl/rest/brm.pm.extension.ucardfoto/${contentId}/content?access_token=TOKEN
        // Content = base64
    }
}
