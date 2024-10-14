<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inisialisasi dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$topicName = $_ENV["KAFKA_TOPIC"];
$groupId = $_ENV["KAFKA_GROUP"];

$conf = new RdKafka\Conf();
$conf->set('bootstrap.servers', $_ENV["KAFKA_BROKER"]);
$conf->set('group.id', $_ENV["KAFKA_GROUP"]);
//Set SSL Config
$conf->set('security.protocol', 'SASL_SSL');
$conf->set('ssl.ca.location', $_ENV["KAFKA_SSL_CA"]);
// Set SASL Config
$conf->set('sasl.mechanisms', 'PLAIN');
$conf->set('sasl.username', $_ENV["KAFKA_SASL_USERNAME"]);
$conf->set('sasl.password', $_ENV["KAFKA_SASL_PASSWORD"]);

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
