05.05.2022 || v.0.5

Список доменов: data/domains.txt
Список субдоменов: data/subdomains.txt
Список аккаунтов CF: data/cf.txt

Формат записи аккаунтов CF:
login;key
Для удобства можно вот так:
login;key;pass

add_domains.php
Добавляет в указаный аккаунт все домены из domains.txt

add_subdomains.php
Добавляет субдомены по очереди, один за другим.

import_subdomains.php
Добавляет субдомены пакетом (импорт). Это намного быстрее чем add_subdomains.php

change_ip.php
Переберет все аккаунты CF и заменит старый IP на новый.

remove_moved.php
Переберет все аккаунты CF и удалит домены с статусом "Moved".

remove_domains.php
Переберет все аккаунты CF и удалит все домены из domains.txt

purge_cache.php
Переберет все аккаунты CF и сбросит кэш для доменов из domains.txt
Если список доменов оставить пустым, то кэш будет сброшен для всех доменов во всех аккаунтах CF.