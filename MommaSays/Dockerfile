#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:mommasays --build-arg BUILD_FROM=nethergamesmc/servers:base -f MommaSays/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xMommaSays.phar           - built by this repo's build workflow
#   assets/MommaSays/         - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=MS
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xMommaSays.phar /home/plugins/xMommaSays.phar

ADD assets/MommaSays/arenas /home/plugin_data/xMommaSays/arenas
ADD assets/MommaSays/WaitingLobby /home/worlds/Hub
