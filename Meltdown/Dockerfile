#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:meltdown --build-arg BUILD_FROM=nethergamesmc/servers:base -f Meltdown/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xMeltdown.phar            - built by this repo's build workflow
#   assets/Meltdown/          - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=MD
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xMeltdown.phar /home/plugins/xMeltdown.phar

ADD assets/Meltdown/arenas /home/plugin_data/xMeltdown/arenas
ADD assets/Meltdown/arenas.yml /home/plugin_data/xMeltdown/arenas.yml
ADD assets/Meltdown/WaitingLobby /home/worlds/Hub
