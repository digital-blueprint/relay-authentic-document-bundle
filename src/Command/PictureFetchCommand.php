<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Command;

use DBP\API\AuthenticDocumentBundle\UCard\UCardService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PictureFetchCommand extends Command
{
    protected static $defaultName = 'dbp:picture-fetch';

    private $service;

    public function __construct(UCardService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    protected function configure()
    {
        $this->setDescription('Download pictures for a CO user');
        $this->addArgument('ident', InputArgument::REQUIRED, 'The IDENT-NR-OBFUSCATED of the user.');
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
        $cards = $service->getCardsForIdent($ident);
        foreach ($cards as $card) {
            $pic = $service->getCardPicture($card);
            $filename = $card->ident.'-'.$card->cardType.'-'.$pic->id.'.jpg';
            $output->writeln('Creating '.$filename);
            file_put_contents($filename, $pic->content);
        }

        return 0;
    }
}
