#
# PMMP Dockerfile (base)
#
# docker build -t nethergamesmc/servers:base .
#
# Expects the build context to contain, in addition to this repo:
#   bin/                     - PHP 8.4 PM5 binary (fetched from NetherGamesMC/php-build-scripts)
#   PocketMine-MP.phar       - built from NetherGamesMC/PocketMine-MP (build/server-phar.php)
#   NGEssentials.phar        - built by this repo's build workflow
#   quiche-build/            - quiche checkout built with `cargo build --release --features ffi`
#
# quiche/key.pem and quiche/cert.pem are NOT baked into the image - mount them at
# runtime, e.g.:
#   docker run -v /path/to/key.pem:/home/quiche/key.pem -v /path/to/cert.pem:/home/quiche/cert.pem ...
#
FROM ubuntu:noble

EXPOSE 19132

RUN apt update && apt dist-upgrade -y && apt autoremove -y && apt install -y --no-install-recommends ca-certificates wget libffi8 build-essential

WORKDIR /home

ADD bin /home/bin
ADD PocketMine-MP.phar /home/PocketMine-MP.phar
ADD docker/start.sh /home/start.sh
ADD docker/server.properties /home/server.properties
ADD docker/pocketmine.yml /home/pocketmine.yml

ADD NGEssentials.phar /home/plugins/NGEssentials.phar

COPY quiche-build/target/release/libquiche.so /home/quiche/libquiche.so
COPY quiche-build/quiche/include/quiche.h /home/quiche/quiche.h

COPY docker/timerfd.h /home/timerfd/timerfd.h

RUN chmod o+x bin/php7/bin/php start.sh

ENV TERM=xterm
ENV SERVER_ID=1
ENV QUICHE_PATH=/home/quiche/libquiche.so
ENV QUICHE_PHP_FILE=/home/quiche.php
ENV QUICHE_H_FILE=/home/quiche/quiche.h
ENV TIMERFD_PHP_FILE=/home/timerfd.php
ENV TIMERFD_H_FILE=/home/timerfd/timerfd.h
ENV KEY_PATH=/home/quiche/key.pem
ENV CERT_PATH=/home/quiche/cert.pem

ENTRYPOINT ["./start.sh"]
CMD ["--no-log-file"]
