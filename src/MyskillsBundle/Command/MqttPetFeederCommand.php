<?php
namespace MyskillsBundle\Command;

use MyskillsBundle\Service\MqttService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');

class MqttPetFeederCommand extends Command {
    const SUBSCRIBER_MODE = 'subscriber';
    const PUBLISHER_MODE = 'publisher';
    const ADMIN_USER = 'admin';
    const ADMIN_PASS = 'lampl640013';
    const USER = 'test';
    const USER_PASS = 'testpass';

    protected function configure()
    {
        $this
            ->setName('mqtt:feeder')
            ->addArgument('mode', InputArgument::REQUIRED, 'mode [subscriber|publisher]')
            ->setDescription('mqtt publisher & subscriber')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\n<--start: ".date('Y-m-d H:i'));

        $mode = $input->getArgument('mode');

        if ($mode === self::SUBSCRIBER_MODE) {
            $mqttService = new MqttService('82.146.37.117', 61613, "test_subscriber");
            if (!$mqttService->connect(true, null, self::USER, self::USER_PASS)) {
                $output->writeln("<error>Can not connect to mqtt server</error>");
                exit;
            }
            $topics['test/a'] = [
                "qos" => 0,
                "function" => function ($topic, $message) use($output) {
                    $output->writeln("MESSAGE: <info>" . date("r") . "Topic:{$topic} $message</info>");
                }
            ];
            $mqttService->subscribe($topics, 0);

            while ($mqttService->proc()) {
            }
            $mqttService->close();
            $output->writeln("Subscriber is stopped");
        } elseif ($mode === self::PUBLISHER_MODE) {
            $mqttService = new MqttService('82.146.37.117', 61613, "test_publisher");
            if (!$mqttService->connect(true, null, self::ADMIN_USER, self::ADMIN_PASS)) {
                $output->writeln("<error>Can not connect to mqtt server</error>");
                exit;
            }
            $mqttService->publish("test/a", "Hello World! at ".date("r"), 0);
            $mqttService->close();
            $output->writeln("<info>Published!</info>");
        }

        $output->writeln("\n<--finish: ".date('Y-m-d H:i'));
    }
}
