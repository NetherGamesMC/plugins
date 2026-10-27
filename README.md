# NetherGames Network - Plugin Monorepo

<div align="center">

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://cdn.nethergames.org/img/logo/one-line-non-flush-light.png">
  <source media="(prefers-color-scheme: light)" srcset="https://cdn.nethergames.org/img/logo/one-line-non-flush-dark.png">
  <img alt="NetherGames" src="https://cdn.nethergames.org/img/logo/one-line-non-flush-dark.png" width="450">
</picture>

<br><br>

[![PHPStan CI](https://github.com/NetherGamesMC/plugins/actions/workflows/phpstan.yml/badge.svg)](https://github.com/NetherGamesMC/plugins/actions/workflows/phpstan.yml)
[![Build](https://github.com/NetherGamesMC/plugins/actions/workflows/build.yml/badge.svg)](https://github.com/NetherGamesMC/plugins/actions/workflows/build.yml)
[![Docker](https://github.com/NetherGamesMC/plugins/actions/workflows/docker.yml/badge.svg)](https://github.com/NetherGamesMC/plugins/actions/workflows/docker.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](LICENSE)
[![Target API](https://img.shields.io/badge/PocketMine--MP-API%205.0.0+-orange.svg)](https://github.com/NetherGamesMC/PocketMine-MP)
[![Minecraft Bedrock](https://img.shields.io/badge/Minecraft%20Bedrock-v1.20.0--v1.26.30-brightgreen.svg)](https://minecraft.net)

**The complete collection of server plugins, minigames, and internal libraries powering the NetherGames Network.**

[Closure Announcement](https://support.nethergames.org/closure-announcement) • [Closure FAQ & Info](https://support.nethergames.org/closure-info) • [Maps & Assets](https://github.com/NetherGamesMC/assets) • [License](LICENSE)

</div>

---

## Background & History

NetherGames was founded on **January 18th, 2016** by Callum for his 11th birthday as a small server for friends called *NetherPvP*, running on public PocketMine forum plugins. Over time, the server expanded into minigames and rebranded to **NetherGames**.

In 2017, the *Better Together* update required Xbox logins, impacting player counts across the Bedrock community. NetherGames merged with *GameCraftPE* (run by Dries).

During 2020 and 2021, NetherGames grew substantially during the pandemic, reaching a peak concurrent player count of **3,314 players**.

## Why We Made This Open Source

On **June 28th, 2026**, NetherGames closed its doors after more than 10 years of operation.

Running an independent, non-featured Bedrock server became an increasingly resource-intensive battle over time due to ecosystem changes and restricted access to developer technical information from Mojang. On a personal level, founders Callum and Dries grew up alongside the server for nearly a decade. With Dries finishing university, both were ready to close this chapter and move on to what comes next.

Rather than letting NetherGames disappear, we open-sourced our entire codebase so that anyone can host their own matches, run events, and keep playing their favourite NetherGames games with friends long after the server is gone.

For the full farewell post, read our [Closure Announcement](https://support.nethergames.org/closure-announcement) and [Closure FAQ and Information](https://support.nethergames.org/closure-info).

---

## Supported Versions & Requirements

| Component | Supported Version / Detail |
| :--- | :--- |
| **Minecraft Bedrock** | **v1.20.0 - v1.26.30** |
| **Proxy** | [WaterdogPE](https://github.com/WaterdogPE/WaterdogPE) |
| **Server Software** | [PocketMine-MP API 5.0.0+](https://github.com/NetherGamesMC/PocketMine-MP) (PM5) |
| **PHP Runtime** | `PHP 8.5` (64-bit, binaries via [NetherGamesMC/php-build-scripts](https://github.com/NetherGamesMC/php-build-scripts)) |
| **Base Image** | [`nethergamesmc/pocketmine-mp`](https://hub.docker.com/r/nethergamesmc/pocketmine-mp) (PHP + `PocketMine-MP.phar`) |

---

## Repository Structure

### Core & Infrastructure

- **[NGEssentials](NGEssentials)**: Core backbone plugin handling authentication, proxy communication, player profiles, ranks, matchmaking, and anticheat hooks.
- **[Lobby](Lobby)**: Hub server plugin managing server selectors, cosmetics, parkour, NPCs, and the player guestbook.

### Minigames

- **[Bedwars](Bedwars)**: Solo, Doubles, 3v3v3v3, 4v4v4v4 Bedwars with resource generators, shops, upgrades, bedbugs, dream defenders, and dragon sudden death.
- **[Skywars](Skywars)**: Solo, Doubles, and Mega Skywars with custom kits, chests, cages, and refill events.
- **[Duels](Duels)**: 1v1 and 2v2 PvP duels across NoDebuff, Sumo, Classic, Bridge, Gapple, Combo, and OP modes.
- **[TheBridge](TheBridge)**: 1v1, 2v2, and 4v4 bridge battles with cage spawns, goal scoring, and instant respawns.
- **[Conquests](Conquests)**: Territory control and capture-the-flag game mode with tactical kits and base control.
- **[MurderMystery](MurderMystery)**: Murder Mystery with Innocents, Detective, and Murderer roles, secret weapons, and gold collection.
- **[SurvivalGames](SurvivalGames)**: Classic Survival Games with tiered crates, grace periods, supply drops, and deathmatch.
- **[UHC](UHC)**: Ultra Hardcore survival with shrinking borders, golden heads, and custom scenarios.
- **[MommaSays](MommaSays)**: Micro-challenge Simon-Says party game.
- **[Soccer](Soccer)**: Real-time football/soccer minigame with physics ball handling and goal tracking.
- **[Meltdown](Meltdown)**: Reactor room survival game with temperature mechanics and hazard parkour.
- **[Factions](Factions)**: Faction warfare with land claiming, power and raiding, faction vaults, bounties, and King of the Hill. Runs as two game types — *Farlands*, the persistent overworld where land is claimed, and *Badlands*, a PvP arena sharing the same faction data. See **[Factions/README.md](Factions/README.md)** for the architecture: how regions scope claims, and how vault locking survives a crash.
- **[SkyBlock](SkyBlock)**: Persistent island survival with challenges, crates, auction house, custom enchantments, spawner stacking, and island bosses. Runs as a stateless pair of server roles — *Agora* (social hub and PvP) and *Skyland* (island hosting) — with islands migrating between them on demand. See **[SkyBlock/README.md](SkyBlock/README.md)** for the architecture: what it expects, and how state moves between servers.

### Shared Libraries (`libraries/`)

- **[`libminigames`](libraries/libminigames)**: Core state machine and lifecycle manager for all minigames.
- **[`libMMO`](libraries/libMMO)**: Persistent-progression toolkit — economy, auction house, challenges, crates, kits, vaults, custom enchantments, and entity stacking. Shared by SkyBlock and Factions.
- **[`libforms`](libraries/libforms)**: Fluent object-oriented builders for Bedrock form dialogs.
- **[`libasyncio`](libraries/libasyncio)**: Asynchronous task execution and event-loop primitives.
- **[`libPhysX`](libraries/libPhysX)**: Custom physics engine, collision detection, and raycasting.
- **[`libReplay`](libraries/libReplay)**: Packet recording and match replay playback system.
- **[`libVanilla`](libraries/libVanilla)**: Bedrock mechanics, entity behaviors, particles, and converters.
- **[`libDiscord`](libraries/libDiscord)**: Discord webhook and bot integration.

---

## Maps & World Assets

All map worlds, waiting lobbies, and coordinate configs (`arenas.yml`) are hosted in the **[NetherGamesMC/assets](https://github.com/NetherGamesMC/assets)** repository. That includes SkyBlock's persistent worlds and its starter island templates; only live player islands are excluded, since those are stored in object storage at runtime.

SkyBlock and Factions load worlds by name and behave differently depending on which one is present, so both document their world layout in full — what each server type loads, what happens when a world is missing, and where the spawn point comes from. See [SkyBlock — Worlds](SkyBlock/README.md#worlds) and [Factions — Worlds](Factions/README.md#worlds), which are also what to read if you would rather run maps of your own.

Factions has one extra directory, `Factions/archive`, holding the three worlds its Farlands servers originally started from — a built spawn and the terrain immediately around it, one per region, with the vanilla generator extending each outward from there as players explore. They are documented in the assets repository under [The archived Farlands worlds](https://github.com/NetherGamesMC/assets#the-archived-farlands-worlds).

Point `ASSETS_PATH` at a checkout and the [Quick Start](#quick-start) stack installs whatever the chosen gamemode needs on first boot.

---

## Quick Start

The fastest way to a running server. `docker compose` builds a gamemode server from source and brings it up alongside a MariaDB that already has the schemas loaded, plus phpMyAdmin for inspecting it — and, for SkyBlock, a local S3 service for island storage. Nothing else needs installing — no PHP, no Composer, no database setup.

> [!WARNING]
> This stack is for local testing and development, not for production. It boots with `xbox-auth=off` and no whitelist, so anyone who can reach the port can join under any name; MariaDB and phpMyAdmin come up with a known root password; and the credentials in [docker/credentials.yml](docker/credentials.yml) are committed to this repository. NetherGames ran these plugins behind a WaterdogPE proxy that handled authentication and routing, and none of that is included here — treat anything you expose beyond your own machine as your own responsibility to secure.

You need Docker and a GitHub token. Composer reads package metadata through the GitHub API, which allows only 60 requests an hour unauthenticated, so a build without one will fail partway. A fine-grained token with public read access is enough.

```bash
export GITHUB_TOKEN=github_pat_...
```

```bash
docker compose up --build
```

That gives you the **Lobby** gamemode on `127.0.0.1:19132` — add it as a server in Minecraft Bedrock and connect. phpMyAdmin is at [http://localhost:8080](http://localhost:8080), signed in as `root` with the password `nethergames`.

Choose a different gamemode with `GAME`, naming any plugin directory (`Bedwars`, `Skywars`, `Duels`, `TheBridge`, `Conquests`, `MurderMystery`, `SurvivalGames`, `UHC`, `MommaSays`, `Soccer`, `Meltdown`, `SkyBlock`, `Factions`):

```bash
GAME=Bedwars docker compose up --build
```

Worlds live on the host so they survive rebuilds and can be edited in place. `WORLDS_PATH` is mounted at `/home/worlds`; point it at your own collection, otherwise `./worlds` is used and PocketMine generates a world on first boot:

```bash
WORLDS_PATH=~/NetherGames/worlds GAME=Lobby docker compose up --build
```

Most gamemodes need arena worlds and an `arenas.yml` to be playable. Clone the [assets](https://github.com/NetherGamesMC/assets) repository anywhere and point `ASSETS_PATH` at it — the server installs what the chosen gamemode needs on first boot:

```bash
ASSETS_PATH=~/NetherGames/assets GAME=Bedwars docker compose up --build
```

That gives Bedwars its 104 arenas and the real waiting lobby, and Lobby its `Hub` and `ArcadeLobby` worlds. The checkout is mounted read-only and never written to; anything already in your worlds directory is left alone, so a restart never overwrites what the server has saved. Set `NG_ASSETS_FORCE=1` to replace existing copies.

Without `ASSETS_PATH` the server still builds, boots and accepts connections on a generated world, which is enough to develop against.

### SkyBlock

SkyBlock stores player islands in S3-compatible object storage rather than on disk, so it needs one more service. Enabling the `skyblock` profile starts a local [RustFS](https://github.com/rustfs/rustfs) instance alongside the server, creates its bucket, and points island storage at it — no cloud account required:

```bash
GAME=SkyBlock GAME_TYPE=Skyland COMPOSE_PROFILES=skyblock docker compose up --build
```

`GAME_TYPE` picks which half of SkyBlock the server runs as. **Skyland** hosts islands and is the one to use on its own; **Agora** is the social hub that normally sits in front of it. A standalone Skyland server offers the island creation form to players who do not have an island yet, so it is usable without an Agora server beside it.

Without `COMPOSE_PROFILES=skyblock` the stack still comes up, but nothing provides object storage and island loading will fail.

| Variable              | Default         | Purpose                                                                                   |
|:----------------------|:----------------|:------------------------------------------------------------------------------------------|
| `GITHUB_TOKEN`        | *(required)*    | Passed to the build as a secret; never stored in an image layer                           |
| `COMPOSE_PROFILES`    | *(none)*        | Set to `skyblock` to start the local S3 service; required for SkyBlock, ignored otherwise |
| `GAME`                | `Lobby`         | Gamemode plugin to bake alongside NGEssentials                                            |
| `GAME_TYPE`           | *(none)*        | SkyBlock: `Skyland` or `Agora`. Factions: `Farlands` or `Badlands`                        |
| `WORLDS_PATH`         | `./worlds`      | Host directory mounted at `/home/worlds`; worlds persist here                             |
| `ASSETS_PATH`         | `./assets`      | Checkout of the assets repository, mounted read-only at `/assets`                         |
| `SERVER_PORT`         | `19132`         | Host UDP port for the server                                                              |
| `PHPMYADMIN_PORT`     | `8080`          | Host port for phpMyAdmin                                                                  |
| `RUSTFS_CONSOLE_PORT` | `9001`          | Host port for the RustFS console                                                          |
| `DB_PASSWORD`         | `nethergames`   | MariaDB root password                                                                     |
| `S3_BUCKET`           | `skyblock`      | Bucket created for island storage                                                         |
| `S3_ACCESS_KEY`       | `nethergames`   | RustFS access key                                                                         |
| `S3_SECRET_KEY`       | `nethergames`   | RustFS secret key                                                                         |
| `S3_REGION`           | `us-east-1`     | Region used when signing S3 requests                                                      |
| `COMPOSER_PREFER`     | `--prefer-dist` | Set to `--prefer-source` to install over git instead of the GitHub API                    |

Stop the stack with `docker compose down`, or `docker compose down -v` to discard the database as well.

---

## Database

NGEssentials stores player profiles, guilds and social data in **MariaDB**, and SkyBlock keeps islands and economy in a second schema. The compose stack provisions both automatically; this section is for running your own.

> [!IMPORTANT]
> It must be MariaDB, not MySQL. The NGEssentials dump uses the MariaDB-only `utf8mb4_uca1400_as_ci` collation, and MySQL 8 additionally rejects the SkyBlock schema's `BLOB UNIQUE` and bare `BLOB DEFAULT ''` declarations. MariaDB 11.4 is what these are tested against.

Two schemas are created and loaded by [docker/mariadb-init/01-load-schemas.sh](docker/mariadb-init/01-load-schemas.sh) when the database volume is first initialised:

| Schema     | Source                                                                   | Contents                                                |
|:-----------|:-------------------------------------------------------------------------|:--------------------------------------------------------|
| `ngdata`   | [NGEssentials/ngdata_players.sql](NGEssentials/ngdata_players.sql)       | Player data, guilds, stats, cosmetics, offline messages |
| `skyblock` | [SkyBlock/resources/table_mysql.sql](SkyBlock/resources/table_mysql.sql) | Islands, island placement, economy, auctions            |
| `factions` | [Factions/resources/table_mysql.sql](Factions/resources/table_mysql.sql) | Factions, claims, power, vaults, bounties               |

Stored procedures are loaded alongside the tables: [NGEssentials/resources/stored_procedures.sql](NGEssentials/resources/stored_procedures.sql) into `ngdata`, and every file in [Factions/resources/procedures](Factions/resources/procedures) into `factions`. The plugins `CALL` these at runtime rather than at startup, so a database missing them boots normally and then fails the first time a faction vault is opened or money changes hands.

How credentials reach the plugins depends on the mode the server image was built in:

- **Development mode** (`DEV_MODE=true`, the default) reads [docker/credentials.yml](docker/credentials.yml), mounted over the plugin's own blank copy. The `database` block is NGEssentials; `sb_database` is SkyBlock and `fc_database` is Factions, each keeping a separate connection.
- **Production mode** (`DEV_MODE=false`) ignores that file and reads `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_SCHEMA` and `VOTE_KEY` from the environment. All five must be set — NGEssentials fails to start if any is missing.

To re-run the schema load after changing the SQL, discard the volume so the entrypoint initialises again:

```bash
docker compose down -v
```

---

## Building a Server Image

[docker/server.Dockerfile](docker/server.Dockerfile) builds a complete, runnable server in one command: the PocketMine-MP server phar from the [NetherGames fork](https://github.com/NetherGamesMC/PocketMine-MP), the NGEssentials core plugin, and one gamemode plugin, all compiled from source. Compose uses it, but it stands alone:

```bash
docker build -f docker/server.Dockerfile --secret id=github_token,env=GITHUB_TOKEN --build-arg GAME=Bedwars -t nethergamesmc/servers-dev:bedwars .
```

```bash
docker run --rm -p 19132:19132/udp -v ~/Documents/Personal/worlds:/home/worlds nethergamesmc/servers-dev:bedwars
```

NGEssentials is always installed — it is the core every gamemode depends on — and `GAME` selects the plugin layered on top of it.

| Build argument    | Default         | Purpose                                                                                                                            |
|:------------------|:----------------|:-----------------------------------------------------------------------------------------------------------------------------------|
| `GAME`            | `Lobby`         | Gamemode plugin directory to bake in                                                                                               |
| `DEV_MODE`        | `true`          | Self-contained server: no proxy, credentials from file. `false` builds a production image reading credentials from the environment |
| `SERVER_TYPE`     | *(from `GAME`)* | NGEssentials server type, e.g. `BW`, `SW`, `SB`; derived from `GAME` unless set                                                    |
| `PMMP_IMAGE`      | `nethergamesmc/pocketmine-mp:latest` | Image providing PHP and `PocketMine-MP.phar`                                                                  |
| `COMPOSER_PREFER` | `--prefer-dist` | `--prefer-source` installs over git instead of the GitHub API                                                                      |

Assets are mounted, not baked. The [assets](https://github.com/NetherGamesMC/assets) repository is several hundred megabytes, so instead of copying it into every image, [docker/entrypoint.sh](docker/entrypoint.sh) installs the parts the gamemode needs when the container starts:

```bash
docker run --rm -p 19132:19132/udp -v ~/PhpstormProjects/assets:/assets:ro -v ./worlds:/home/worlds nethergamesmc/servers-dev:bedwars
```

| Source                | Destination                  |                                                                   |
|:----------------------|:-----------------------------|:------------------------------------------------------------------|
| `<Game>/*`            | `/home/plugin_data/x<Game>/` | arenas, `arenas.yml`, `WaitingLobby`, and any per-gamemode extras |
| `<Game>/WaitingLobby` | `/home/worlds/Hub`           | additionally, as the default world                                |
| `<Game>/worlds/*`     | `/home/worlds/*`             | for gamemodes shipping whole worlds, such as Lobby                |

This mirrors the layout of the production images. `WaitingLobby` deliberately appears twice: `libminigames\Arena` copies it out of the plugin data folder into a fresh world for every arena it creates, so a server missing it there would start matches with no waiting lobby.

Existing files are never replaced, so the server's own saves survive restarts. `NG_ASSETS_FORCE=1` overrides that.

### Per-gamemode configuration

Most gamemodes run on the shared `docker/server.properties` and `docker/pocketmine.yml`. A gamemode that needs different settings drops just the changed files into `docker/overrides/<Game>/`, and they are copied over the shared ones when the image is built — the same split production uses.

SkyBlock ships both: `gamemode=0` so players can build on their islands (the shared default is adventure mode), `difficulty=3`, and memory and chunk-GC tuning suited to streaming many island worlds at once. See [docker/overrides/README.md](docker/overrides/README.md).

> [!NOTE]
> These images omit the quiche shared library, which is only needed for QUIC transport between a WaterdogPE proxy and the server. That path is inactive in development mode. The root [Dockerfile](Dockerfile) documents the production layout, which expects quiche and the server phar to be staged into the build context beforehand.

---

## Running with Docker

Server images can be run with Docker:

```bash
docker run -d \
  --name nethergames-bedwars \
  -p 19132:19132/udp \
  -v /path/to/key.pem:/home/quiche/key.pem \
  -v /path/to/cert.pem:/home/quiche/cert.pem \
  nethergamesmc/servers:bedwars
```

## Building a Plugin with Docker

Individual plugins can be built into a `.phar` without installing PHP or Composer locally. A GitHub token is required — Composer reads repository metadata through the GitHub API, which is limited to 60 requests an hour unauthenticated. Export it as `GITHUB_TOKEN`, then:

```bash
docker build -f docker/build-plugin.Dockerfile --secret id=github_token,env=GITHUB_TOKEN --build-arg PLUGIN=SkyBlock --target export --output type=local,dest=./build .
```

The token is passed as a BuildKit secret rather than a build argument, so it is mounted only for the one step that needs it and never reaches an image layer or `docker history`. The build fails immediately if it is missing.

The image mirrors the build workflow — merge the shared repository list, install production dependencies, then pack — and writes the phar to `./build`. Set `PLUGIN` to any plugin directory; NGEssentials additionally needs `--build-arg PHAR_NAME=NGEssentials.phar`.

Downloads are cached across builds. The PHP release, Composer and DevTools are held in a shared cache and revalidated rather than refetched, and Composer's package cache is reused, so building a second plugin — or the same one after a source edit — pulls nothing over the network. Dependencies are installed before plugin source is copied, so editing source does not trigger a reinstall.

---

## License

This project is licensed under the **[GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE)**.

### Why GNU AGPLv3?

Unlike traditional desktop software where distribution triggers open-source requirements, Minecraft servers are operated over a network. Standard licenses (such as GPLv3 or MIT) allow server operators to run modified code privately without contributing improvements back to the community.

We chose the **GNU AGPLv3** (Section 13) because it closes this network loophole: anyone running or hosting a modified version of this software over a network must provide their modified source code to players and the community. This ensures the codebase remains free, open, and collaborative for everyone in the Bedrock ecosystem.
