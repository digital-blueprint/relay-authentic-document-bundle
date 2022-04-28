<?php

declare(strict_types=1);
/**
 * Message test command.
 *
 * execute:
 * bin/console dbp:message-test
 */

namespace DBP\API\AuthenticDocumentBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailTestCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dbp:relay:authentic-document:mail-test';

    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Just for testing. Please ignore.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: send email (keep in mind tugraz.at seems to deny mails from our docker smtp)
        $email = (new Email())
            ->from('patrizio.bekerle@tugraz.at')
            ->to('patrizio.bekerle@tugraz.at')
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);
        dump($email);

        return 0;
    }
}
