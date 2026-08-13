#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:lobby --build-arg BUILD_FROM=nethergamesmc/servers:base -f Lobby/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xLobby.phar               - built by this repo's build workflow
#   assets/Lobby/             - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=Lobby
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xLobby.phar /home/plugins/xLobby.phar

ADD assets/Lobby/worlds /home/worlds
