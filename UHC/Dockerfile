#
# PMMP Dockerfile
#
# docker build -t nethergamesmc/servers:uhc --build-arg BUILD_FROM=nethergamesmc/servers:base -f UHC/Dockerfile .
#
# Expects the build context to contain, in addition to this repo:
#   xUHC.phar                 - built by this repo's build workflow
#   VanillaGenerator.phar     - built from the VanillaGenerator repo
#   assets/UHC/               - checked out from the assets repo
#
ARG BUILD_FROM=nethergamesmc/servers:base
FROM $BUILD_FROM

ARG SERVER_TYPE=UHC
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
RUN sed -i "s/__SERVER_TYPE__/${SERVER_TYPE}/" /home/plugin_data/NGEssentials/config.yml
ADD xUHC.phar /home/plugins/xUHC.phar
ADD VanillaGenerator.phar /home/plugins/VanillaGenerator.phar

ADD assets/UHC/WaitingLobby /home/worlds/Hub
