<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inisialisasi dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


$topicName = $_ENV["KAFKA_TOPIC"]; //Change topic name to produc

//Config
$conf = new RdKafka\Conf();
$conf->set('bootstrap.servers', $_ENV["KAFKA_BROKER"]);
// Set SSL Config
$conf->set('security.protocol', 'SASL_SSL');
$conf->set('ssl.ca.location', $_ENV["KAFKA_SSL_CA"]);
// Set SASL Config
$conf->set('sasl.mechanisms', 'PLAIN');
$conf->set('sasl.username', $_ENV["KAFKA_SASL_USERNAME"]);
$conf->set('sasl.password', $_ENV["KAFKA_SASL_PASSWORD"]);


$conf->setErrorCb(function ($kafka, $err, $reason) {
    fprintf(STDERR, "Kafka error: %s (reason: %s)\n", rd_kafka_err2str($err), $reason);
});

$conf->setLogCb(function ($kafka, $level, $fac, $buf) {
    fprintf(STDERR, "Kafka log: level %d, facility %s, message: %s\n", $level, $fac, $buf);
});

$conf->setStatsCb(function ($kafka, $json, $json_len) {
    fprintf(STDERR, "Kafka stats: %s\n", $json);
});

$conf->setDrMsgCb(function ($kafka, $message) {
    if ($message->err) {
        fprintf(STDERR, "Delivery report: %s\n", rd_kafka_err2str($message->err));
    } else {
        fprintf(STDOUT, "Message delivered to topic %s [%d] at offset %d\n",
            $message->topic_name, $message->partition, $message->offset);
    }
});

$producer = new RdKafka\Producer($conf);
$topic = $producer->newTopic($topicName);

for ($i = 0; $i < 10; $i++) {
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, "message ".($i+1)." from 10");
    $producer->poll(0);
}

for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
    $result = $producer->flush(500);
    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
        break;
    }
}

if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
    throw new \RuntimeException('Was unable to flush, messages might be lost!');
}
?>




