# About php_kafka_client
Being thought about


# Prerequisites
- php v7.4 - 8.2
- php_rdkafka extension installed
- composer (Optional)


# How to Running
- git clone https://github.com/awakko/php_kafka_client.git
- cd php_kafka_client


    ## if you have Composer
    - composer install
    - rename the file .env.axample to .env
    - please fill in .env according to your configuration

    now you can run producer and consumer to confluent cloud, run the command in terminal:

    - 'php cloud_producer.php' to run producer
    - 'php cloud_consumer.php' to run consumer


    ## if you don't have composer
    - you have to change the required configuration directly from the <b>cloud producer.php</b> and <b>cloud_consumer.php</b> source code
    
    <b>example : </b>
    $conf->set('bootstrap.servers', $_ENV["CLOUD_KAFKA_BROKER"])
                            change to
    $conf->set('bootstrap.servers', 'youre_confluent_cloud.9092');

    now you can run producer and consumer to confluent cloud, run the command in terminal:
    - 'php cloud_producer.php' to run producer
    - 'php cloud_consumer.php' to run consumer


<b>Note:</b> 
This source code only works if the php_rdkafka extension is installed in your php. 
run 'php -m' in the terminal to make sure "rdkafka" is installed in your php.
