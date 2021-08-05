<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Command;

use DBP\API\AuthenticDocumentBundle\UCard\UCardAPI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PictureFetchCommand extends Command
{
    protected static $defaultName = 'dbp:picture-fetch';

    private $service;
    private $config;

    public function __construct(UCardAPI $service)
    {
        parent::__construct();

        $this->service = $service;
        $this->config = [];
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    protected function configure()
    {
        $this->setDescription('Download pictures for a CO user');
        $this->addArgument('ident', InputArgument::REQUIRED, 'The IDENT-NR-OBFUSCATED of the user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->config;
        $clientId = $config['co_oauth2_api_client_id'] ?? '';
        $clientSecret = $config['co_oauth2_api_client_secret'] ?? '';
        $baseUrl = $config['co_oauth2_api_api_url'] ?? '';

        $service = $this->service;
        $service->setBaseUrl($baseUrl);
        $service->fetchToken($clientId, $clientSecret);

        $ident = $input->getArgument('ident');
        $cards = $service->getCardsForIdent($ident);
        if (count($cards) === 0) {
            $output->writeln("No pictures found for '$ident'");

            return 0;
        }
        foreach ($cards as $card) {
            $pic = $service->getCardPicture($card);
            $filename = $card->ident.'-'.$card->cardType.'-'.$pic->id.'.jpg';
            $output->writeln('Creating '.$filename);
            file_put_contents($filename, $pic->content);
        }

        return 0;
    }
}
