#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:conquests --build-arg BUILD_FROM=nethergamesmc/servers:base -f Conquests/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xConquests.phar          - built by this repo's build workflow
#   assets/Conquests/        - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ADD docker/Chunk_Ticking_pocketmine.yml /home/pocketmine.yml

ARG SERVER_TYPE=CQ
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xConquests.phar /home/plugins/xConquests.phar

ADD assets/Conquests/arenas /home/plugin_data/xConquests/arenas
ADD assets/Conquests/arenas.yml /home/plugin_data/xConquests/arenas.yml
ADD assets/Conquests/WaitingLobby /home/worlds/Hub
