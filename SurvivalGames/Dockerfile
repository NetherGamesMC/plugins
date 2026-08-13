#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:survivalgames --build-arg BUILD_FROM=nethergamesmc/servers:base -f SurvivalGames/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xSurvivalGames.phar       - built by this repo's build workflow
#   assets/SurvivalGames/     - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=SG
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xSurvivalGames.phar /home/plugins/xSurvivalGames.phar

ADD assets/SurvivalGames/arenas /home/plugin_data/xSurvivalGames/arenas
ADD assets/SurvivalGames/arenas.yml /home/plugin_data/xSurvivalGames/arenas.yml
ADD assets/SurvivalGames/WaitingLobby /home/worlds/Hub
