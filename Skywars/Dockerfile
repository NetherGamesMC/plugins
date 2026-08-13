#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:skywars --build-arg BUILD_FROM=nethergamesmc/servers:base -f Skywars/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xSkywars.phar             - built by this repo's build workflow
#   assets/Skywars/           - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=SW
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xSkywars.phar /home/plugins/xSkywars.phar

ADD assets/Skywars/arenas /home/plugin_data/xSkywars/arenas
ADD assets/Skywars/arenas.yml /home/plugin_data/xSkywars/arenas.yml
ADD assets/Skywars/WaitingLobby /home/worlds/Hub
