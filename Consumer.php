<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inisialisasi dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$environment = 'DEV';
$user = 'USRDEV1';
$topicName = 'usrDev1_topic';
$groupId = 'usrDev1_group';

$brokers = $_ENV[$environment . "_KAFKA_BROKER"];
$sslCa = $_ENV[$environment . "_" . $user . "_KAFKA_SSL_CA"];
$sslPass = $_ENV[$environment . "_" . $user . "_KAFKA_SSL_PASS"];
$saslUser = $_ENV[$environment . "_" . $user . "_KAFKA_SASL_USERNAME"];
$saslPass = $_ENV[$environment . "_" . $user . "_KAFKA_SASL_PASSWORD"];

$conf = new RdKafka\Conf();
$conf->set('bootstrap.servers', $brokers);
$conf->set('group.id', $groupId);

//Set SSL Config
$conf->set('security.protocol', 'SASL_SSL');
$conf->set('ssl.ca.location', $sslCa);

// Set SASL Config
$conf->set('sasl.mechanisms', 'PLAIN');
$conf->set('sasl.username', $saslUser);
$conf->set('sasl.password', $saslPass);

// Set a rebalance callback to log partition assignments (optional)
$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "Assigning Partitions\n";
            $kafka->assign($partitions);
            break;

        case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
            echo "Revoking Partitions\n";
            $kafka->assign(NULL);
            break;

        default:
            throw new \Exception($err);
    }
});

$consumer = new RdKafka\KafkaConsumer($conf);
$consumer->subscribe([$topicName]);

echo "Waiting for messages...\n";
while (true) {
    $message = $consumer->consume(120*1000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            echo "Message received: " . $message->payload . "\n";
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "No more messages; will wait for more\n";
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "Timed out\n";
            break;
        default:
            throw new \Exception($message->errstr(), $message->err);
            break;
    }
}
?>
