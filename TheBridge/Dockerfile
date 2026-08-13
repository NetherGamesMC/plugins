#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:thebridge --build-arg BUILD_FROM=nethergamesmc/servers:base -f TheBridge/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xTheBridge.phar           - built by this repo's build workflow
#   assets/TheBridge/         - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=TB
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xTheBridge.phar /home/plugins/xTheBridge.phar

ADD assets/TheBridge/arenas /home/plugin_data/xTheBridge/arenas
ADD assets/TheBridge/arenas.yml /home/plugin_data/xTheBridge/arenas.yml
ADD assets/TheBridge/WaitingLobby /home/worlds/Hub
