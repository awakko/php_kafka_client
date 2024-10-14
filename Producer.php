<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inisialisasi dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$environment = 'DEV';
$user = 'USRDEV1';
$topicName = 'usrDev1_topic'; //Change topic name to produce

$brokers = $_ENV[$environment . "_KAFKA_BROKER"];
$sslCa = $_ENV[$environment . "_" . $user . "_KAFKA_SSL_CA"];
$sslPass = $_ENV[$environment . "_" . $user . "_KAFKA_SSL_PASS"];
$saslUser = $_ENV[$environment . "_" . $user . "_KAFKA_SASL_USERNAME"];
$saslPass = $_ENV[$environment . "_" . $user . "_KAFKA_SASL_PASSWORD"];

//Config
$conf = new RdKafka\Conf();

// Set SSL Config
$conf->set('bootstrap.servers', $brokers);
$conf->set('security.protocol', 'SASL_SSL');
$conf->set('ssl.ca.location', $sslCa);

// Set SASL Config
$conf->set('sasl.mechanisms', 'PLAIN');
$conf->set('sasl.username', $saslUser);
$conf->set('sasl.password', $saslPass);


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
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, "06-09-2024 - usrDev1 - message ".(50+$i+1)." from 10");
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




