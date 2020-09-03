<?php

declare(strict_types=1);
/**
 * Message test command.
 *
 * execute:
 * bin/console dbp:message-test
 */

namespace DBP\API\EgizImageBundle\Command;

use DBP\API\CoreBundle\Service\PersonProviderInterface;
use DBP\API\EgizImageBundle\Message\EgizImageRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MessageTestCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dbp:message-test';

    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    public function __construct(MessageBusInterface $bus, PersonProviderInterface $personProvider)
    {
        $this->bus = $bus;
        $this->personProvider = $personProvider;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Just for testing. Please ignore.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $person = $this->personProvider->getPerson('woody007', true);

        // date we would get from egiz
        $date = new \DateTime();
        // add 5 sec for testing
        $date->add(new \DateInterval('PT5S'));

        $delayInterval = $date->diff(new \DateTime());
        $seconds = $delayInterval->days * 86400 + $delayInterval->h * 3600
            + $delayInterval->i * 60 + $delayInterval->s;
        dump($seconds);

        $this->bus->dispatch(new EgizImageRequest($person, $date), [
            // wait 5 seconds before processing
            new DelayStamp($seconds * 1000),
        ]);

        return 0;
    }
}
