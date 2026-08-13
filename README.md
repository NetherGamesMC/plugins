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
| **PHP Runtime** | `PHP 8.4` (64-bit, binaries via [NetherGamesMC/php-build-scripts](https://github.com/NetherGamesMC/php-build-scripts)) |

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

> [!NOTE]
> Skyblock and Factions are coming soon.

### Shared Libraries (`libraries/`)

- **[`libminigames`](libraries/libminigames)**: Core state machine and lifecycle manager for all minigames.
- **[`libforms`](libraries/libforms)**: Fluent object-oriented builders for Bedrock form dialogs.
- **[`libasyncio`](libraries/libasyncio)**: Asynchronous task execution and event-loop primitives.
- **[`libPhysX`](libraries/libPhysX)**: Custom physics engine, collision detection, and raycasting.
- **[`libReplay`](libraries/libReplay)**: Packet recording and match replay playback system.
- **[`libVanilla`](libraries/libVanilla)**: Bedrock mechanics, entity behaviors, particles, and converters.
- **[`libDiscord`](libraries/libDiscord)**: Discord webhook and bot integration.

---

## Maps & World Assets

All map worlds, waiting lobbies, and coordinate configs (`arenas.yml`) are hosted in the **[NetherGamesMC/assets](https://github.com/NetherGamesMC/assets)** repository.

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

---

## License

This project is licensed under the **[GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE)**.

### Why GNU AGPLv3?

Unlike traditional desktop software where distribution triggers open-source requirements, Minecraft servers are operated over a network. Standard licenses (such as GPLv3 or MIT) allow server operators to run modified code privately without contributing improvements back to the community.

We chose the **GNU AGPLv3** (Section 13) because it closes this network loophole: anyone running or hosting a modified version of this software over a network must provide their modified source code to players and the community. This ensures the codebase remains free, open, and collaborative for everyone in the Bedrock ecosystem.
