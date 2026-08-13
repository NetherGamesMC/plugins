#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:soccer --build-arg BUILD_FROM=nethergamesmc/servers:base -f Soccer/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xSoccer.phar              - built by this repo's build workflow
#   assets/Soccer/            - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=SC
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xSoccer.phar /home/plugins/xSoccer.phar

ADD assets/Soccer/arenas /home/plugin_data/xSoccer/arenas
ADD assets/Soccer/WaitingLobby /home/worlds/Hub
