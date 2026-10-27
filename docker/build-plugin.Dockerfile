# syntax=docker/dockerfile:1
#
# Reproducible plugin build
#
# Builds any plugin in this monorepo into a .phar inside a container, so a build needs no local PHP,
# Composer or toolchain — only Docker. It mirrors what .github/workflows/build.yml does: merge the
# shared repository list, install production dependencies, then pack the plugin.
#
# A GitHub token is REQUIRED. Composer reads repository metadata through the GitHub API, which allows
# only 60 requests an hour unauthenticated — shared across everything on your address, so a cold
# build reliably fails without one. Pass it as a BuildKit secret, never as a build argument: build
# arguments are recorded in the image and recoverable with `docker history`, whereas a secret is
# mounted only for the length of one RUN and never reaches a layer.
#
#   export GITHUB_TOKEN=github_pat_...
#   docker build -f docker/build-plugin.Dockerfile \
#     --secret id=github_token,env=GITHUB_TOKEN \
#     --build-arg PLUGIN=SkyBlock \
#     --target export --output type=local,dest=./build .
#
# Produces ./build/xSkyBlock.phar.
#
# Build arguments:
#   PLUGIN         directory of the plugin to build (SkyBlock, Bedwars, NGEssentials, ...)
#   COMPOSER_FILE  manifest to install from. Detected automatically: composer-build.json when the
#                  plugin ships one (NGEssentials, SkyBlock), otherwise composer.json. Override only
#                  to force a specific manifest.
#   PHAR_NAME      output filename; defaults to x<PLUGIN>.phar, matching the build workflow.
#                  NGEssentials is the exception and needs PHAR_NAME=NGEssentials.phar
#
# Nothing is re-downloaded between builds. The PHP release, Composer and DevTools live in a shared
# /downloads cache and are revalidated with a conditional request rather than refetched; Composer's
# own package cache is mounted at /root/.cache/composer. Dependencies are also installed before the
# plugin source is copied, so editing source does not invalidate the install layer — only the pack
# step re-runs. Building a second plugin reuses the whole toolchain.
#
# CI packs with the private pharbuilder-rs. This image uses pmmp/DevTools instead so the build works
# without private access; the resulting phar is equivalent for running a server.
#
FROM ubuntu:resolute AS build

ARG PLUGIN=SkyBlock
ARG COMPOSER_FILE=
ARG PHAR_NAME=
ARG PHP_RELEASE_TAG=pm5-php-8.5-latest
ARG DEVTOOLS_VERSION=1.17.2
# --prefer-dist matches CI. Switch to --prefer-source to install over git instead, which fetches from
# the git remotes rather than pulling dist archives through the GitHub API.
ARG COMPOSER_PREFER=--prefer-dist

RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl git openssh-client \
 && rm -rf /var/lib/apt/lists/*

# Composer otherwise caches to /root/.composer/cache, which would not match the cache mount below.
ENV COMPOSER_CACHE_DIR=/root/.cache/composer

WORKDIR /toolchain

# --time-cond turns each fetch into a conditional request: the file is re-downloaded only when the
# remote copy is newer. That keeps moving tags such as pm5-php-8.5-latest current without pulling
# ~100 MB on every build.
RUN --mount=type=cache,id=ng-plugin-downloads,target=/downloads,sharing=locked \
    PHP_TARBALL="/downloads/PHP-8.5-Linux-x86_64-PM5.tar.gz" \
 && curl -fsSL --time-cond "$PHP_TARBALL" -o "$PHP_TARBALL" \
      "https://github.com/NetherGamesMC/php-build-scripts/releases/download/${PHP_RELEASE_TAG}/PHP-8.5-Linux-x86_64-PM5.tar.gz" \
 && tar -xzf "$PHP_TARBALL"

# The published PHP release hardcodes the build machine's extension_dir, and bundles ini entries for
# extensions that are not always present. Repoint it and disable what is missing, mirroring the
# "Configure PHP" step in .github/workflows/build.yml.
RUN EXT_DIR="$(find bin/php7/lib/php/extensions -mindepth 1 -maxdepth 1 -type d 2>/dev/null | head -n 1)" \
 && if [ -n "$EXT_DIR" ]; then \
      ABS_EXT_DIR="/toolchain/$EXT_DIR"; \
      find bin -name "*.ini" -exec sed -i "s|^extension_dir[[:space:]]*=.*|extension_dir=\"$ABS_EXT_DIR\"|g" {} + ; \
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

# Dependency layer. Copy only what Composer actually reads — the shared repository list, the merge
# script, the vendored libraries reached through path repositories, and this plugin's manifests.
# Plugin source is copied further down, so editing it leaves the install below cached.
COPY scripts/ /src/scripts/
COPY shared/ /src/shared/
COPY libraries/ /src/libraries/
# Every plugin's manifest, not just this one: most declare a path repository pointing at a sibling
# (../NGEssentials, and NGEssentials itself points at ../Lobby and ../SkyBlock). Composer treats a path
# repository whose directory is absent as a hard error, not a warning, so the manifests have to be
# present even when --no-dev means the package is never installed. These files are tiny and change
# rarely, so the install layer below still caches well.
COPY --parents */composer*.json /src/

WORKDIR /src/${PLUGIN}

# Most plugins ship only composer.json; NGEssentials and SkyBlock add a composer-build.json holding the
# production-only dependency set. Pick whichever is present unless COMPOSER_FILE forces one.
RUN MANIFEST="${COMPOSER_FILE}" \
 && if [ -z "$MANIFEST" ]; then \
      if [ -f composer-build.json ]; then MANIFEST=composer-build.json; else MANIFEST=composer.json; fi; \
    fi \
 && if [ ! -f "$MANIFEST" ]; then echo "ERROR: ${PLUGIN}/$MANIFEST does not exist." >&2; exit 1; fi \
 && echo "Using manifest: $MANIFEST" \
 && /toolchain/bin/php7/bin/php /src/scripts/merge-composer.php \
      "$MANIFEST" /src/shared/repositories.json composer.merged.json

# Path repositories are symlinked by default, which a phar cannot follow — mirror them into vendor/
# instead so the vendored libraries end up inside the archive.
RUN --mount=type=secret,id=github_token,required=true \
    --mount=type=cache,id=ng-plugin-composer,target=/root/.cache/composer,sharing=locked \
    if [ ! -s /run/secrets/github_token ]; then \
      echo "ERROR: the github_token secret is empty." >&2; \
      echo "Pass it with: --secret id=github_token,env=GITHUB_TOKEN (with GITHUB_TOKEN exported)." >&2; \
      exit 1; \
    fi \
 && COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"$(cat /run/secrets/github_token)\"}}" \
    COMPOSER_ROOT_VERSION=dev-stable \
    COMPOSER=composer.merged.json \
    COMPOSER_MIRROR_PATH_REPOS=1 \
    /toolchain/bin/php7/bin/php /toolchain/composer.phar install \
      "${COMPOSER_PREFER}" --no-dev --no-interaction --no-progress \
 && rm -f composer.merged.json composer.merged.lock

# --prefer-source leaves git checkouts behind; drop them so they do not land in the archive.
RUN find vendor -name .git -prune -exec rm -rf {} + 2>/dev/null || true

# Plugin source. vendor/ is excluded by .dockerignore, so this merges over the installed tree without
# disturbing it.
COPY . /src

# DevTools' released stub requires "src/ConsoleScript.php" relative to the include path, which only
# resolves when PocketMine loads it as a plugin — running the phar directly fails. Pull the console
# script through the phar:// stream instead. --make is resolved against --relative, and both are "."
# so that the plugin directory itself becomes the archive root.
RUN mkdir -p /out \
 && NAME="${PHAR_NAME:-x${PLUGIN}.phar}" \
 && /toolchain/bin/php7/bin/php -dphar.readonly=0 \
      -r 'require "phar:///toolchain/DevTools.phar/src/ConsoleScript.php";' -- \
      --make . --relative . --out "/out/${NAME}" \
 && ls -lh /out

# Export stage: `--target export --output type=local,dest=./build` writes the phar to the host
# without producing an image.
FROM scratch AS export
COPY --from=build /out/ /
