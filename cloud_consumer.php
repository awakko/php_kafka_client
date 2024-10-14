<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inisialisasi dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


// Topic name
$topicName = 'topic_php';

//Kafka Config
$conf = new RdKafka\Conf();
$conf->set('bootstrap.servers', $_ENV["CLOUD_KAFKA_BROKER"]);
$conf->set('group.id', 'php_group');
$conf->set('security.protocol', 'SASL_SSL');
$conf->set('sasl.mechanisms', 'PLAIN');
$conf->set('sasl.username', $_ENV["CLOUD_KAFKA_SASL_USERNAME"]);
$conf->set('sasl.password', $_ENV["CLOUD_KAFKA_SASL_PASSWORD"]);

// Consume from the earliest available offset if no committed offset exists
$conf->set('auto.offset.reset', 'earliest'); 

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

// Create a consumer instance and subscribe
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
            // Mungkin di sini terjadi error 'not implemented'
            error_log("Kafka error: " . $message->errstr() . " (Error code: " . $message->err . ")");
            break;
    }
}
?>
