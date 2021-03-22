<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Command;

use DBP\API\AuthenticDocumentBundle\UCard\UCardService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PictureUploadCommand extends Command
{
    protected static $defaultName = 'dbp:picture-upload';

    private $service;

    public function __construct(UCardService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    protected function configure()
    {
        $this->setDescription('Upload pictures for a CO user');
        $this->addArgument('ident', InputArgument::REQUIRED, 'The IDENT-NR-OBFUSCATED of the user');
        $this->addArgument('card-type', InputArgument::REQUIRED, 'The card type to set for');
        $this->addArgument('path', InputArgument::REQUIRED, 'The path to a picture');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientId = $_ENV['CO_OAUTH2_CLIENT_ID'];
        $clientSecret = $_ENV['CO_OAUTH2_CLIENT_SECRET'];
        $baseUrl = $_ENV['CO_OAUTH2_BASE_URL'];

        $service = $this->service;
        $service->setBaseUrl($baseUrl);
        $token = $service->fetchToken($clientId, $clientSecret)['access_token'];
        $service->setToken($token);

        $ident = $input->getArgument('ident');
        $cardType = $input->getArgument('card-type');
        $filePath = $input->getArgument('path');

        $cards = $service->getCardsForIdent($ident, $cardType);
        // If there exists no card of the specified type we have to create one
        if (count($cards) === 0) {
            $service->createCardForIdent($ident, $cardType);
            $cards = $service->getCardsForIdent($ident, $cardType);
        }

        assert($cards[0]->cardType === $cardType);

        $card = $cards[0];
        $data = file_get_contents($filePath);
        $service->setCardPicture($card, $data);

        return 0;
    }
}
