#Console MQAPI Client
---
It is a demo console client for MQAPI program which converts audio files.

Only "mp3" format is now supported. MQAPI for now converts only MP3 to WAV format.

>Author [Mushkin Vitaliy].  
>Thank you.

### Version
Demo version (not for professional use).

#####Console MQAPI Client can:

1. Upload your audio file to MQAPI program for conversion and returns your session Id.

> Example of console command:  
>  ./console upload ./web/uploads/audo.mp3

2. Get status of conversion for your file from MQAPI program.

> Example of console command:  
>  ./console status yourSessionId1234567

3. Download your converted file from MQAPI program.

> Example of console command:  
>  ./console download yourSessionId1234567 ./path_to_download_file

#####Config:
Your can change different options of MQClient in:
>app/config/config.ini

### Tech

MQClient uses a number of open source projects to work properly:

* [RabbitMQ] - Robust messaging for applications.
* [Guzzle] - Guzzle is a PHP HTTP client that makes it easy to send HTTP requests and trivial to integrate with web services.
* [Symfony/Console] - The Console component eases the creation of beautiful and testable command line interfaces.

License
----

MIT

[RabbitMQ]:https://www.rabbitmq.com
[Guzzle]: http://docs.guzzlephp.org
[Symfony/Console]: http://symfony.com/doc/current/components/console/introduction.html
[Mushkin Vitaliy]:https://github.com/mushkin-v