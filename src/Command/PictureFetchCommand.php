<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Command;

use DBP\API\AuthenticDocumentBundle\UCard\UCardService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class PictureFetchCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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
        $config = $this->container->getParameter('dbp_api.authenticdocument.config');
        assert(is_array($config));
        $clientId = $config['co_oauth2_api_client_id'] ?? '';
        $clientSecret = $config['co_oauth2_api_client_secret'] ?? '';
        $baseUrl = $config['co_oauth2_api_api_url'] ?? '';

        $service = $this->service;
        $service->setBaseUrl($baseUrl);
        $token = $service->fetchToken($clientId, $clientSecret)['access_token'];
        $service->setToken($token);

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
