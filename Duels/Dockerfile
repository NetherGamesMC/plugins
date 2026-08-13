#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:duels --build-arg BUILD_FROM=nethergamesmc/servers:base -f Duels/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xDuels.phar              - built by this repo's build workflow
#   assets/Duels/            - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=Duels
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xDuels.phar /home/plugins/xDuels.phar

ADD assets/Duels/arenas /home/plugin_data/xDuels/arenas
ADD assets/Duels/arenas.yml /home/plugin_data/xDuels/arenas.yml
ADD assets/Duels/WaitingLobby /home/worlds/Hub
