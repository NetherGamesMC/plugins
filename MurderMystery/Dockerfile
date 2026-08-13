#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:murdermystery --build-arg BUILD_FROM=nethergamesmc/servers:base -f MurderMystery/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xMurderMystery.phar       - built by this repo's build workflow
#   assets/MurderMystery/     - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=MM
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xMurderMystery.phar /home/plugins/xMurderMystery.phar

ADD assets/MurderMystery/arenas /home/plugin_data/xMurderMystery/arenas
ADD assets/MurderMystery/arenas.yml /home/plugin_data/xMurderMystery/arenas.yml
ADD assets/MurderMystery/WaitingLobby /home/worlds/Hub
