Для тех, кто имеет свой сайт.
Многопоточный досер с прокси.

Вариант 1.
Копируем файл к себе на хостинг, ставим на его выполнение по крону каждую минуту коммандой: php -f ddos.php
* На каждом хостинге свое место размещения php и файлов, поэтому может понадобиться прописать полный путь, например /usr/bin/php -f /usr/home/mysite.com/public_html/ddos.php

Вариант 2.
Копируем файл к себе на хостинг, в индексный файл (index.php) вставляем где-нибудь include('./ddos.php');
Не забываем настройки :)

Настройки:
$threads - Колличество потоков, по имолчанию 50 (для сайта от 500 хостов/сутки рекомендуется 2-3).
$showResult - для проверки, например по крону можно отследить работает ли скрипт (должны приходить сообщения на почту)

