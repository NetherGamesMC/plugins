# syntax=docker/dockerfile:1
#
# Runnable game server image
#
# Builds a complete, self-contained PocketMine-MP server for one gamemode: PHP and the server phar
# from nethergamesmc/pocketmine-mp, plus the NGEssentials core plugin and the gamemode plugin from source. Nothing needs to be staged into
# the build context beforehand — only Docker and a GitHub token.
#
#   export GITHUB_TOKEN=github_pat_...
#   docker build -f docker/server.Dockerfile \
#     --secret id=github_token,env=GITHUB_TOKEN \
#     --build-arg GAME=Lobby -t nethergamesmc/servers-dev:lobby .
#   docker run --rm -p 19132:19132/udp nethergamesmc/servers-dev:lobby
#
# Images are tagged nethergamesmc/servers-dev:<game> by convention, to keep them distinct from the
# production nethergamesmc/servers:<game> images that CI publishes from prebuilt phars — this build is
# self-contained and defaults to development mode, so the two are not interchangeable.
#
# NGEssentials is always installed; GAME selects the gamemode baked alongside it. The default server
# type is derived from GAME using the same mapping the per-game Dockerfiles use, and can be overridden.
#
# Build arguments:
#   GAME         gamemode directory: Lobby, Bedwars, Skywars, Duels, TheBridge, Conquests,
#                MurderMystery, SurvivalGames, UHC, MommaSays, Soccer, Meltdown, SkyBlock, Factions
#   SERVER_TYPE  NGEssentials server type; derived from GAME when unset
#   PMMP_IMAGE   image providing PHP and PocketMine-MP.phar (default: nethergamesmc/pocketmine-mp:latest)
#
# Assets (arena worlds, arenas.yml, waiting lobbies) live in the separate NetherGamesMC/assets
# repository, which is hundreds of megabytes. They are mounted at runtime rather than baked in — mount
# the checkout read-only at /assets and docker/entrypoint.sh installs the parts this gamemode needs on
# first boot. Without them the server still runs and PocketMine generates a world.
#
#   docker run -p 19132:19132/udp -v /path/to/assets:/assets:ro -v ./worlds:/home/worlds nethergamesmc/servers-dev:lobby
#
# Not included: the quiche shared library. It is only needed for QUIC proxy transport, which is off
# when NGEssentials runs in development mode. See the root Dockerfile for the production layout.
#
ARG PMMP_IMAGE=nethergamesmc/pocketmine-mp:latest
ARG DEVTOOLS_VERSION=1.17.2
ARG VANILLAGEN_REF=NG
# --prefer-dist matches CI and needs the token for GitHub-hosted archives. --prefer-source installs
# over git instead, which works against public repositories without hitting the GitHub API.
ARG COMPOSER_PREFER=--prefer-dist

FROM ${PMMP_IMAGE} AS pocketmine

# --------------------------------------------------------------------------------------------------
# Toolchain: PHP (from PMMP_IMAGE), Composer and DevTools. Shared by every build stage below.
# This mirrors docker/build-plugin.Dockerfile; that file builds a single plugin phar for distribution,
# this one assembles a whole server.
# --------------------------------------------------------------------------------------------------
FROM ubuntu:resolute AS toolchain

ARG DEVTOOLS_VERSION

RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl git openssh-client \
 && rm -rf /var/lib/apt/lists/*

ENV COMPOSER_CACHE_DIR=/root/.cache/composer

WORKDIR /toolchain

COPY --from=pocketmine /opt/pocketmine/bin bin

# The image's php.ini points extension_dir at /opt/pocketmine; repoint it and disable anything
# that is not actually shipped.
RUN EXT_DIR="$(find bin/php7/lib/php/extensions -mindepth 1 -maxdepth 1 -type d 2>/dev/null | head -n 1)" \
 && if [ -n "$EXT_DIR" ]; then \
      find bin -name "*.ini" -exec sed -i "s|^extension_dir[[:space:]]*=.*|extension_dir=\"/toolchain/$EXT_DIR\"|g" {} + ; \
    fi \
 && if [ -z "$EXT_DIR" ] || [ ! -f "$EXT_DIR/opcache.so" ]; then \
      find bin -name "*.ini" -exec sed -i "s|^zend_extension.*opcache.*|;zend_extension=opcache.so|g" {} + ; \
    fi \
 && if [ -z "$EXT_DIR" ] || [ ! -f "$EXT_DIR/ext_quiche.so" ]; then \
      find bin -name "*.ini" -exec sed -i "s|^extension.*ext_quiche.*|;extension=ext_quiche.so|g" {} + ; \
    fi

RUN --mount=type=cache,id=ng-plugin-downloads,target=/downloads,sharing=locked \
    curl -fsSL --time-cond /downloads/composer.phar -o /downloads/composer.phar \
      "https://getcomposer.org/composer-stable.phar" \
 && curl -fsSL --time-cond "/downloads/DevTools-${DEVTOOLS_VERSION}.phar" \
      -o "/downloads/DevTools-${DEVTOOLS_VERSION}.phar" \
      "https://github.com/pmmp/DevTools/releases/download/${DEVTOOLS_VERSION}/DevTools.phar" \
 && cp /downloads/composer.phar /toolchain/composer.phar \
 && cp "/downloads/DevTools-${DEVTOOLS_VERSION}.phar" /toolchain/DevTools.phar

# --------------------------------------------------------------------------------------------------
# VanillaGenerator.phar. Factions generates its "wild" world with the vanilla_overworld generator and
# UHC does the same for match worlds; without this plugin the lookup returns null and they crash on
# enable. It has no Composer dependencies — the heavy lifting is in ext-vanillagenerator, which the
# PM5 PHP build already ships.
# --------------------------------------------------------------------------------------------------
FROM toolchain AS vanillagen

ARG VANILLAGEN_REF

RUN git clone --depth 1 --branch "${VANILLAGEN_REF}" \
      https://github.com/NetherGamesMC/VanillaGenerator.git /vanillagen

WORKDIR /vanillagen

RUN mkdir -p /out \
 && /toolchain/bin/php7/bin/php -dphar.readonly=0 \
      -r 'require "phar:///toolchain/DevTools.phar/src/ConsoleScript.php";' -- \
      --make . --relative . --out /out/VanillaGenerator.phar \
 && ls -lh /out

# --------------------------------------------------------------------------------------------------
# NGEssentials.phar plus the gamemode phar.
# --------------------------------------------------------------------------------------------------
FROM toolchain AS plugins

ARG GAME=Lobby
ARG COMPOSER_PREFER

# Dependency inputs only, so editing plugin source below does not force a reinstall. Every manifest is
# copied because most plugins declare a path repository pointing at a sibling, and Composer treats a
# missing path repository as a hard error.
COPY scripts/ /src/scripts/
COPY shared/ /src/shared/
COPY libraries/ /src/libraries/
COPY --parents */composer*.json /src/

# NGEssentials ships a production-only manifest; most gamemodes have just composer.json.
RUN --mount=type=secret,id=github_token,required=true \
    --mount=type=cache,id=ng-plugin-composer,target=/root/.cache/composer,sharing=locked \
    if [ ! -s /run/secrets/github_token ]; then \
      echo "ERROR: the github_token secret is empty." >&2; exit 1; \
    fi \
 && export COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"$(cat /run/secrets/github_token)\"}}" \
 && for P in NGEssentials "${GAME}"; do \
      cd "/src/$P" || exit 1; \
      if [ -f composer-build.json ]; then M=composer-build.json; else M=composer.json; fi; \
      echo "==> $P using $M"; \
      /toolchain/bin/php7/bin/php /src/scripts/merge-composer.php \
        "$M" /src/shared/repositories.json composer.merged.json || exit 1; \
      COMPOSER_ROOT_VERSION=dev-stable COMPOSER=composer.merged.json COMPOSER_MIRROR_PATH_REPOS=1 \
        /toolchain/bin/php7/bin/php /toolchain/composer.phar install \
          "${COMPOSER_PREFER}" --no-dev --no-interaction --no-progress || exit 1; \
      rm -f composer.merged.json composer.merged.lock; \
    done

# Full source, then pack. DevTools' released stub cannot be executed directly, so the console script is
# pulled through the phar:// stream; --make is resolved against --relative, both "." so the plugin
# directory becomes the archive root.
COPY . /src

RUN mkdir -p /out \
 && for P in NGEssentials "${GAME}"; do \
      cd "/src/$P" || exit 1; \
      find vendor -name .git -prune -exec rm -rf {} + 2>/dev/null || true; \
      if [ "$P" = "NGEssentials" ]; then NAME="NGEssentials.phar"; else NAME="x${P}.phar"; fi; \
      /toolchain/bin/php7/bin/php -dphar.readonly=0 \
        -r 'require "phar:///toolchain/DevTools.phar/src/ConsoleScript.php";' -- \
        --make . --relative . --out "/out/${NAME}" || exit 1; \
    done \
 && ls -lh /out

# --------------------------------------------------------------------------------------------------
# Runtime
# --------------------------------------------------------------------------------------------------
FROM ubuntu:resolute

ARG GAME=Lobby
ARG SERVER_TYPE=
# Development mode makes the container self-contained: NGEssentials reads credentials from its own
# config file (shipped blank) and the proxy is not required, so `docker run` yields a working server.
# Set DEV_MODE=false for a production image, which instead reads VOTE_KEY and DB_HOST/DB_USER/
# DB_PASSWORD/DB_SCHEMA from the environment and expects a WaterdogPE proxy in front of it.
ARG DEV_MODE=true

EXPOSE 19132/udp

RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates libffi8 \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /home

COPY --from=toolchain /toolchain/bin /home/bin
COPY --from=pocketmine /opt/pocketmine/PocketMine-MP.phar /home/PocketMine-MP.phar
COPY --from=plugins /out/ /home/plugins/
COPY --from=vanillagen /out/VanillaGenerator.phar /home/.VanillaGenerator.phar

# extension_dir was pointed at the toolchain stage's path; correct it for the runtime layout.
RUN EXT_DIR="$(find bin/php7/lib/php/extensions -mindepth 1 -maxdepth 1 -type d 2>/dev/null | head -n 1)" \
 && if [ -n "$EXT_DIR" ]; then \
      find bin -name "*.ini" -exec sed -i "s|^extension_dir[[:space:]]*=.*|extension_dir=\"/home/$EXT_DIR\"|g" {} + ; \
    fi

COPY docker/start.sh /home/start.sh
COPY docker/server.properties /home/server.properties
COPY docker/pocketmine.yml /home/pocketmine.yml
COPY docker/Chunk_Ticking_pocketmine.yml /home/Chunk_Ticking_pocketmine.yml
COPY docker/ngessentials-config.yml /home/plugin_data/NGEssentials/config.yml
COPY docker/entrypoint.sh /home/entrypoint.sh
# Per-gamemode server.properties / pocketmine.yml, applied below. See docker/overrides/README.md.
COPY docker/overrides /home/.overrides

RUN set -eu; \
    case "${GAME}" in \
      Bedwars) TYPE=BW ;; Skywars) TYPE=SW ;; MurderMystery) TYPE=MM ;; \
      SurvivalGames) TYPE=SG ;; TheBridge) TYPE=TB ;; Conquests) TYPE=CQ ;; \
      Meltdown) TYPE=MD ;; MommaSays) TYPE=MS ;; Soccer) TYPE=SC ;; \
      SkyBlock) TYPE=SB ;; UHC) TYPE=UHC ;; Duels) TYPE=Duels ;; Lobby) TYPE=Lobby ;; \
      Factions) TYPE=Factions ;; \
      *) TYPE="${GAME}" ;; \
    esac; \
    if [ -n "${SERVER_TYPE}" ]; then TYPE="${SERVER_TYPE}"; fi; \
    sed -i "s/__SERVER_TYPE__/${TYPE}/" /home/plugin_data/NGEssentials/config.yml; \
    echo "server type: ${TYPE}"; \
    if [ "${DEV_MODE}" = "true" ]; then \
      sed -i "s/^developmentMode:.*/developmentMode: true/" /home/plugin_data/NGEssentials/config.yml; \
      printf '\nkafkaEnabled: false\n' >> /home/plugin_data/NGEssentials/config.yml; \
      echo "development mode: proxy and Kafka disabled"; \
    fi; \
    case "${GAME}" in \
      Bedwars|Conquests|Factions) mv /home/Chunk_Ticking_pocketmine.yml /home/pocketmine.yml ;; \
      *) rm -f /home/Chunk_Ticking_pocketmine.yml ;; \
    esac; \
    case "${GAME}" in \
      Factions|UHC) mv /home/.VanillaGenerator.phar /home/plugins/VanillaGenerator.phar; \
                    echo "installed VanillaGenerator for ${GAME}" ;; \
      *) rm -f /home/.VanillaGenerator.phar ;; \
    esac; \
    if [ -d "/home/.overrides/${GAME}" ]; then \
      cp -a "/home/.overrides/${GAME}/." /home/; \
      echo "applied ${GAME} config overrides: $(ls "/home/.overrides/${GAME}" | tr '\n' ' ')"; \
    fi; \
    rm -rf /home/.overrides; \
    mkdir -p /home/worlds; \
    chmod +x /home/bin/php7/bin/php /home/start.sh /home/entrypoint.sh; \
    GAME_PHAR="/home/plugins/x${GAME}.phar"; \
    if [ -f "$GAME_PHAR" ]; then \
      /home/bin/php7/bin/php -r '$y=@file_get_contents("phar://".$argv[1]."/plugin.yml"); if($y!==false && preg_match("/^name:[ \t]*(\S+)/m",$y,$m)) echo $m[1];' "$GAME_PHAR" > /home/.ng-plugin-name; \
      echo "plugin data folder: $(cat /home/.ng-plugin-name)"; \
    fi

ENV TERM=xterm
ENV SERVER_ID=1
# Read by entrypoint.sh to pick the right subtree out of a mounted assets checkout.
ENV NG_GAME=${GAME}
ENV NG_ASSETS=/assets

ENTRYPOINT ["/home/entrypoint.sh"]
