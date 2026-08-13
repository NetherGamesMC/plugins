#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:bedwars --build-arg BUILD_FROM=nethergamesmc/servers:base -f Bedwars/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xBedwars.phar            - built by this repo's build workflow
#   assets/Bedwars/          - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ADD docker/Chunk_Ticking_pocketmine.yml /home/pocketmine.yml

ARG SERVER_TYPE=BW
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xBedwars.phar /home/plugins/xBedwars.phar

ADD assets/Bedwars/arenas /home/plugin_data/xBedwars/arenas
ADD assets/Bedwars/arenas.yml /home/plugin_data/xBedwars/arenas.yml
ADD assets/Bedwars/WaitingLobby /home/worlds/Hub
