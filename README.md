wechat crawler

dependency : node, php, php-swoole, redis

1. cd php && composer install && cd ../js && npm install

2. ./node_modules/anyproxy/bin/anyproxy-ca 

    then install ca on your phone and configure proxy to yourhost:8001

3. redis-server 

4. nohup ./node_modules/anyproxy/bin/anyproxy -i --rule ./proxy.js & && cd ../php && php crawler.php
