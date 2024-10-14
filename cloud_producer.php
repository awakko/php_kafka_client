<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inisialisasi dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$conf = new RdKafka\Conf();

// Set SSL Config
$conf->set('bootstrap.servers', $_ENV["CLOUD_KAFKA_BROKER"]);
$conf->set('security.protocol', 'SASL_SSL');
$conf->set('sasl.mechanisms', 'PLAIN');
$conf->set('sasl.username', $_ENV["CLOUD_KAFKA_SASL_USERNAME"]);
$conf->set('sasl.password', $_ENV["CLOUD_KAFKA_SASL_PASSWORD"]);

// Topic name
$topicName = $_ENV["CLOUD_KAFKA_TOPIC"];

// Message to be sent
$message = [
    'driver_id' => 1,
    'driver_name' => 'azmi',
    'latitude' => 'asas',
    'longitude' => 'asaasas',
];

// Convert the array into a JSON string
$jsonMessage = json_encode($message);

// Create a producer instance
$producer = new RdKafka\Producer($conf);

// Define the topic name
$topic = $producer->newTopic($topicName);

// Produce the message
$topic->produce(
    RD_KAFKA_PARTITION_UA, // Automatically assign partition
    0, // Message flags, 0 for normal message
    $jsonMessage // The message payload
);

// Poll for events (e.g., delivery reports)
$producer->poll(0);

// Flush to ensure all messages are sent
$result = $producer->flush(5000); // Wait up to 5 seconds for messages to be sent

if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
    throw new \RuntimeException('Was unable to flush, messages might be lost!');
} else {
    echo "Message sent successfully!";
}
?>




