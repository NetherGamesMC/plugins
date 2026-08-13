-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mariadb.database.svc
-- Generation Time: Jun 28, 2026 at 08:50 PM
-- Server version: 12.3.2-MariaDB-ubu2404
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ngdata_players`
--

-- --------------------------------------------------------

--
-- Table structure for table `cosmetics`
--

CREATE TABLE `cosmetics` (
  `type` int(2) NOT NULL,
  `id` int(11) NOT NULL,
  `name` varchar(24) NOT NULL,
  `rarity` int(1) NOT NULL,
  `image_type` int(1) DEFAULT NULL,
  `image_source` varchar(64) DEFAULT NULL,
  `crate_available` tinyint(1) NOT NULL,
  `data` mediumblob NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guilds`
--

CREATE TABLE `guilds` (
  `guild_id` int(11) NOT NULL,
  `guild_name` varchar(20) NOT NULL,
  `leader` varchar(16) NOT NULL,
  `tag` varchar(16) NOT NULL DEFAULT '',
  `motd` varchar(50) NOT NULL DEFAULT '',
  `xp` int(16) NOT NULL DEFAULT 0,
  `max_size` int(2) NOT NULL DEFAULT 50,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `discord_invite` varchar(255) DEFAULT NULL,
  `auto_kick_days` int(11) NOT NULL DEFAULT 0,
  `flags` int(11) NOT NULL DEFAULT 0,
  `discord_id` varchar(20) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guild_invites`
--

CREATE TABLE `guild_invites` (
  `id` int(11) NOT NULL,
  `guild_id` int(11) NOT NULL,
  `inviter_xuid` varchar(16) NOT NULL,
  `invitee_xuid` varchar(16) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guild_members`
--

CREATE TABLE `guild_members` (
  `guild_id` int(11) NOT NULL,
  `role` tinyint(4) NOT NULL DEFAULT 0,
  `player` varchar(16) NOT NULL,
  `flags` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_votes`
--

CREATE TABLE `map_votes` (
  `game_mode` varchar(16) NOT NULL,
  `map_name` varchar(24) NOT NULL,
  `votes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_replay`
--

CREATE TABLE `match_replay` (
  `replay_id` int(11) NOT NULL,
  `protocol_id` int(4) NOT NULL,
  `server_type` varchar(8) DEFAULT NULL,
  `game_type` varchar(9) NOT NULL DEFAULT '',
  `map_name` varchar(25) NOT NULL DEFAULT '',
  `players` mediumtext DEFAULT NULL,
  `private` tinyint(1) NOT NULL DEFAULT 0,
  `touch_only` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded` int(1) NOT NULL DEFAULT 0,
  `flag` int(1) NOT NULL DEFAULT 0,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `offline_messages`
--

CREATE TABLE `offline_messages` (
  `id` int(11) NOT NULL,
  `player` varchar(16) NOT NULL,
  `message` longtext NOT NULL,
  `type` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parkour`
--

CREATE TABLE `parkour` (
  `xuid` varchar(16) NOT NULL,
  `record` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_data`
--

CREATE TABLE `player_data` (
  `xuid` varchar(16) NOT NULL,
  `player` varchar(16) NOT NULL,
  `locale` varchar(10) NOT NULL DEFAULT 'en_au',
  `rank` varchar(255) NOT NULL DEFAULT '',
  `permissions` varchar(256) NOT NULL DEFAULT '',
  `staff_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `first_joined` int(11) NOT NULL DEFAULT 0,
  `last_joined` int(11) NOT NULL DEFAULT 0,
  `last_quit` int(11) NOT NULL DEFAULT 0,
  `last_server` varchar(64) NOT NULL DEFAULT '',
  `titan_expire` bigint(20) NOT NULL DEFAULT 0,
  `store_credits` int(11) NOT NULL DEFAULT 0,
  `vote_time` bigint(20) NOT NULL DEFAULT 0,
  `xp` int(11) NOT NULL DEFAULT 0,
  `status_credits` int(11) NOT NULL DEFAULT 0,
  `last_tier_up` int(11) DEFAULT NULL,
  `cosmetics` longtext NOT NULL DEFAULT '',
  `crate_keys` int(11) NOT NULL DEFAULT 0,
  `extra_friends` int(11) NOT NULL DEFAULT 0,
  `hide_players` tinyint(1) NOT NULL DEFAULT 0,
  `hide_rank` tinyint(1) NOT NULL DEFAULT 0,
  `announcements_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `global_chat_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `ranked_chat_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `selected_rank` varchar(16) NOT NULL DEFAULT '',
  `kit` int(2) NOT NULL DEFAULT 0,
  `pet` varchar(30) NOT NULL DEFAULT '',
  `pet_name` varchar(15) NOT NULL DEFAULT '',
  `hide_gtag` tinyint(1) NOT NULL DEFAULT 0,
  `friend_requests` tinyint(1) NOT NULL DEFAULT 1,
  `chat_color` int(2) NOT NULL DEFAULT 0,
  `dms_status` tinyint(1) NOT NULL DEFAULT 0,
  `coins` int(11) NOT NULL DEFAULT 0,
  `store_data` blob NOT NULL DEFAULT '',
  `game_settings` blob NOT NULL DEFAULT '',
  `game_configurations` blob NOT NULL DEFAULT '',
  `low_fps` tinyint(1) NOT NULL DEFAULT 0,
  `nick` varchar(16) DEFAULT NULL,
  `hide_nick_skin` tinyint(1) NOT NULL DEFAULT 1,
  `discovered_tokens` varchar(256) DEFAULT '[]',
  `discovered_zones` mediumtext DEFAULT '[]',
  `skin_hash` varchar(32) DEFAULT NULL,
  `boss_bar_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `party_requests` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_presents`
--

CREATE TABLE `player_presents` (
  `xuid` varchar(16) NOT NULL,
  `presents` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_relations`
--

CREATE TABLE `player_relations` (
  `player_one` varchar(16) NOT NULL,
  `player_two` varchar(16) NOT NULL,
  `status` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_stats`
--

CREATE TABLE `player_stats` (
  `xuid` varchar(16) NOT NULL,
  `type` int(1) NOT NULL DEFAULT 0,
  `kills` int(11) NOT NULL DEFAULT 0,
  `deaths` int(11) NOT NULL DEFAULT 0,
  `kdr` decimal(10,2) DEFAULT 0.00,
  `wins` int(11) NOT NULL DEFAULT 0,
  `losses` int(11) NOT NULL DEFAULT 0,
  `wdr` decimal(10,2) DEFAULT 0.00,
  `bw_kills` int(11) NOT NULL DEFAULT 0,
  `bw_kill_assists` int(11) NOT NULL DEFAULT 0,
  `bw_deaths` int(11) NOT NULL DEFAULT 0,
  `bw_solo_kills` int(11) NOT NULL DEFAULT 0,
  `bw_solo_kill_assists` int(11) NOT NULL DEFAULT 0,
  `bw_solo_deaths` int(11) NOT NULL DEFAULT 0,
  `bw_doubles_kills` int(11) NOT NULL DEFAULT 0,
  `bw_doubles_kill_assists` int(11) NOT NULL DEFAULT 0,
  `bw_doubles_deaths` int(11) NOT NULL DEFAULT 0,
  `bw_squads_kills` int(11) NOT NULL DEFAULT 0,
  `bw_squads_kill_assists` int(11) NOT NULL DEFAULT 0,
  `bw_squads_deaths` int(11) NOT NULL DEFAULT 0,
  `bw_beds_broken` int(11) NOT NULL DEFAULT 0,
  `bw_solo_beds_broken` int(11) NOT NULL DEFAULT 0,
  `bw_doubles_beds_broken` int(11) NOT NULL DEFAULT 0,
  `bw_squads_beds_broken` int(11) NOT NULL DEFAULT 0,
  `bw_diamonds_collected` int(11) NOT NULL DEFAULT 0,
  `bw_emeralds_collected` int(11) NOT NULL DEFAULT 0,
  `bw_gold_collected` int(11) NOT NULL DEFAULT 0,
  `bw_iron_collected` int(11) NOT NULL DEFAULT 0,
  `bw_final_kills` int(11) NOT NULL DEFAULT 0,
  `bw_solo_final_kills` int(11) NOT NULL DEFAULT 0,
  `bw_doubles_final_kills` int(11) NOT NULL DEFAULT 0,
  `bw_squads_final_kills` int(11) NOT NULL DEFAULT 0,
  `bw_streak` int(11) NOT NULL DEFAULT 0,
  `bw_best_streak` int(11) NOT NULL DEFAULT 0,
  `bw_wins` int(11) NOT NULL DEFAULT 0,
  `bw_solo_wins` int(11) NOT NULL DEFAULT 0,
  `bw_doubles_wins` int(11) NOT NULL DEFAULT 0,
  `bw_trios_wins` int(11) NOT NULL DEFAULT 0,
  `bw_squads_wins` int(11) NOT NULL DEFAULT 0,
  `bw_1v1_wins` int(11) NOT NULL DEFAULT 0,
  `bw_1v1_kills` int(11) NOT NULL DEFAULT 0,
  `bw_1v1_kill_assists` int(11) NOT NULL DEFAULT 0,
  `bw_1v1_deaths` int(11) NOT NULL DEFAULT 0,
  `bw_1v1_beds_broken` int(11) NOT NULL DEFAULT 0,
  `bw_1v1_final_kills` int(11) NOT NULL DEFAULT 0,
  `bw_2v2_wins` int(11) NOT NULL DEFAULT 0,
  `bw_2v2_kills` int(11) NOT NULL DEFAULT 0,
  `bw_2v2_kill_assists` int(11) NOT NULL DEFAULT 0,
  `bw_2v2_deaths` int(11) NOT NULL DEFAULT 0,
  `bw_2v2_beds_broken` int(11) NOT NULL DEFAULT 0,
  `bw_2v2_final_kills` int(11) NOT NULL DEFAULT 0,
  `cq_kills` int(11) NOT NULL DEFAULT 0,
  `cq_kill_assists` int(11) NOT NULL DEFAULT 0,
  `cq_deaths` int(11) NOT NULL DEFAULT 0,
  `cq_wins` int(11) NOT NULL DEFAULT 0,
  `cq_flags_collected` int(11) NOT NULL DEFAULT 0,
  `cq_flags_captured` int(11) NOT NULL DEFAULT 0,
  `cq_flags_returned` int(11) NOT NULL DEFAULT 0,
  `cq_diamonds_collected` int(11) NOT NULL DEFAULT 0,
  `cq_emeralds_collected` int(11) NOT NULL DEFAULT 0,
  `cq_gold_collected` int(11) NOT NULL DEFAULT 0,
  `cq_iron_collected` int(11) NOT NULL DEFAULT 0,
  `duels_kills` int(11) NOT NULL DEFAULT 0,
  `duels_kill_assists` int(11) NOT NULL DEFAULT 0,
  `duels_deaths` int(11) NOT NULL DEFAULT 0,
  `duels_streak` int(11) NOT NULL DEFAULT 0,
  `duels_best_streak` int(11) NOT NULL DEFAULT 0,
  `duels_wins` int(11) NOT NULL DEFAULT 0,
  `duels_losses` int(11) NOT NULL DEFAULT 0,
  `md_kills` int(11) NOT NULL DEFAULT 0,
  `md_deaths` int(11) NOT NULL DEFAULT 0,
  `md_wins` int(11) NOT NULL DEFAULT 0,
  `md_losses` int(11) NOT NULL DEFAULT 0,
  `md_powerups_collected` int(11) NOT NULL DEFAULT 0,
  `mm_kills` int(11) NOT NULL DEFAULT 0,
  `mm_deaths` int(11) NOT NULL DEFAULT 0,
  `mm_classic_kills` int(11) NOT NULL DEFAULT 0,
  `mm_classic_deaths` int(11) NOT NULL DEFAULT 0,
  `mm_infection_kills` int(11) NOT NULL DEFAULT 0,
  `mm_infection_deaths` int(11) NOT NULL DEFAULT 0,
  `mm_bow_kills` int(11) NOT NULL DEFAULT 0,
  `mm_knife_kills` int(11) NOT NULL DEFAULT 0,
  `mm_throw_knife_kills` int(11) NOT NULL DEFAULT 0,
  `mm_wins` int(11) NOT NULL DEFAULT 0,
  `mm_classic_wins` int(11) NOT NULL DEFAULT 0,
  `mm_infection_wins` int(11) NOT NULL DEFAULT 0,
  `ms_successes` int(11) NOT NULL DEFAULT 0,
  `ms_fails` int(11) NOT NULL DEFAULT 0,
  `ms_wins` int(11) NOT NULL DEFAULT 0,
  `sc_wins` int(11) NOT NULL DEFAULT 0,
  `sc_goals` int(11) NOT NULL DEFAULT 0,
  `sg_kills` int(11) NOT NULL DEFAULT 0,
  `sg_deaths` int(11) NOT NULL DEFAULT 0,
  `sg_wins` int(11) NOT NULL DEFAULT 0,
  `sw_coins` int(11) NOT NULL DEFAULT 0,
  `sw_kills` int(11) NOT NULL DEFAULT 0,
  `sw_kill_assists` int(11) NOT NULL DEFAULT 0,
  `sw_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_solo_kills` int(11) NOT NULL DEFAULT 0,
  `sw_solo_kill_assists` int(11) NOT NULL DEFAULT 0,
  `sw_solo_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_solo_normal_kills` int(11) NOT NULL DEFAULT 0,
  `sw_solo_normal_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_solo_insane_kills` int(11) NOT NULL DEFAULT 0,
  `sw_solo_insane_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_kills` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_kill_assists` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_normal_kills` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_normal_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_insane_kills` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_insane_deaths` int(11) NOT NULL DEFAULT 0,
  `sw_blocks_broken` int(11) NOT NULL DEFAULT 0,
  `sw_blocks_placed` int(11) NOT NULL DEFAULT 0,
  `sw_eggs_thrown` int(11) NOT NULL DEFAULT 0,
  `sw_epearls_thrown` int(11) NOT NULL DEFAULT 0,
  `sw_wins` int(11) NOT NULL DEFAULT 0,
  `sw_solo_wins` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_wins` int(11) NOT NULL DEFAULT 0,
  `sw_losses` int(11) NOT NULL DEFAULT 0,
  `sw_solo_losses` int(11) NOT NULL DEFAULT 0,
  `sw_doubles_losses` int(11) NOT NULL DEFAULT 0,
  `tb_kills` int(11) NOT NULL DEFAULT 0,
  `tb_kill_assists` int(11) NOT NULL DEFAULT 0,
  `tb_deaths` int(11) NOT NULL DEFAULT 0,
  `tb_goals` int(11) NOT NULL DEFAULT 0,
  `tb_streak` int(11) NOT NULL DEFAULT 0,
  `tb_best_streak` int(11) NOT NULL DEFAULT 0,
  `tb_wins` int(11) NOT NULL DEFAULT 0,
  `tb_losses` int(11) NOT NULL DEFAULT 0,
  `tb_solo_kills` int(11) NOT NULL DEFAULT 0,
  `tb_solo_kill_assists` int(11) NOT NULL DEFAULT 0,
  `tb_solo_deaths` int(11) NOT NULL DEFAULT 0,
  `tb_solo_wins` int(11) NOT NULL DEFAULT 0,
  `tb_solo_losses` int(11) NOT NULL DEFAULT 0,
  `tb_solo_goals` int(11) NOT NULL DEFAULT 0,
  `tb_doubles_kills` int(11) NOT NULL DEFAULT 0,
  `tb_doubles_kill_assists` int(11) NOT NULL DEFAULT 0,
  `tb_doubles_deaths` int(11) NOT NULL DEFAULT 0,
  `tb_doubles_wins` int(11) NOT NULL DEFAULT 0,
  `tb_doubles_losses` int(11) NOT NULL DEFAULT 0,
  `tb_doubles_goals` int(11) NOT NULL DEFAULT 0,
  `uhc_kills` int(11) NOT NULL DEFAULT 0,
  `uhc_deaths` int(11) NOT NULL DEFAULT 0,
  `uhc_wins` int(11) NOT NULL DEFAULT 0,
  `uhc_iron_mined` int(11) NOT NULL DEFAULT 0,
  `uhc_gold_mined` int(11) NOT NULL DEFAULT 0,
  `uhc_lapis_mined` int(11) NOT NULL DEFAULT 0,
  `uhc_diamond_mined` int(11) NOT NULL DEFAULT 0,
  `online_time` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

--
-- Triggers `player_stats`
--
DELIMITER $$
CREATE TRIGGER `update_kdr_before_update` BEFORE UPDATE ON `player_stats` FOR EACH ROW BEGIN
    IF OLD.kills != NEW.kills OR OLD.deaths != NEW.deaths THEN
        SET NEW.kdr = IF(NEW.deaths = 0, NEW.kills, NEW.kills / NEW.deaths);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_wdr_before_update` BEFORE UPDATE ON `player_stats` FOR EACH ROW BEGIN
    IF OLD.duels_wins != NEW.duels_wins OR
       OLD.sw_wins != NEW.sw_wins OR
       OLD.md_wins != NEW.md_wins OR
       OLD.tb_wins != NEW.tb_wins OR
       OLD.losses != NEW.losses THEN

        SET @total_wins = NEW.duels_wins + NEW.sw_wins + NEW.md_wins + NEW.tb_wins;

        SET NEW.wdr = IF(NEW.losses = 0, @total_wins, @total_wins / NEW.losses);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `presents`
--

CREATE TABLE `presents` (
  `id` int(2) NOT NULL,
  `hash` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `win_streaks`
--

CREATE TABLE `win_streaks` (
  `xuid` varchar(64) NOT NULL,
  `gameKey` varchar(64) NOT NULL,
  `current` int(11) NOT NULL,
  `best` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- --------------------------------------------------------

--
-- Table structure for table `win_streak_game_keys`
--

CREATE TABLE `win_streak_game_keys` (
  `game_key` varchar(255) NOT NULL,
  `friendly_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cosmetics`
--
ALTER TABLE `cosmetics`
  ADD PRIMARY KEY (`type`,`id`);

--
-- Indexes for table `guilds`
--
ALTER TABLE `guilds`
  ADD PRIMARY KEY (`guild_id`,`guild_name`,`leader`),
  ADD UNIQUE KEY `leader` (`leader`) USING BTREE,
  ADD UNIQUE KEY `guild_name` (`guild_name`) USING BTREE,
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `xp` (`xp`) USING BTREE,
  ADD KEY `auto_kick_days` (`auto_kick_days`) USING BTREE;
ALTER TABLE `guilds` ADD FULLTEXT KEY `guild_name_ftx` (`guild_name`);
ALTER TABLE `guilds` ADD FULLTEXT KEY `tag_ftx` (`tag`);

--
-- Indexes for table `guild_invites`
--
ALTER TABLE `guild_invites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guild_id` (`guild_id`),
  ADD KEY `inviter_xuid` (`inviter_xuid`),
  ADD KEY `invitee_xuid` (`invitee_xuid`);

--
-- Indexes for table `guild_members`
--
ALTER TABLE `guild_members`
  ADD UNIQUE KEY `idx_player` (`player`),
  ADD KEY `idx_guild` (`guild_id`);

--
-- Indexes for table `map_votes`
--
ALTER TABLE `map_votes`
  ADD PRIMARY KEY (`game_mode`,`map_name`);

--
-- Indexes for table `match_replay`
--
ALTER TABLE `match_replay`
  ADD PRIMARY KEY (`replay_id`),
  ADD KEY `time` (`time`);

--
-- Indexes for table `offline_messages`
--
ALTER TABLE `offline_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player` (`player`);

--
-- Indexes for table `parkour`
--
ALTER TABLE `parkour`
  ADD PRIMARY KEY (`xuid`);

--
-- Indexes for table `player_data`
--
ALTER TABLE `player_data`
  ADD PRIMARY KEY (`xuid`),
  ADD UNIQUE KEY `player` (`player`) USING BTREE,
  ADD KEY `email` (`email`),
  ADD KEY `rank` (`rank`),
  ADD KEY `xp` (`xp`),
  ADD KEY `status_credits` (`status_credits`),
  ADD KEY `discord_id` (`discord_id`),
  ADD KEY `crate_keys` (`crate_keys`) USING BTREE,
  ADD KEY `first_joined` (`first_joined`) USING BTREE,
  ADD KEY `skin_hash` (`skin_hash`),
  ADD KEY `flags` (`flags`) USING BTREE,
  ADD KEY `discord_tag` (`discord_tag`);
ALTER TABLE `player_data` ADD FULLTEXT KEY `ftx_player` (`player`);
ALTER TABLE `player_data` ADD FULLTEXT KEY `ftx_rank` (`rank`);

--
-- Indexes for table `player_presents`
--
ALTER TABLE `player_presents`
  ADD PRIMARY KEY (`xuid`);

--
-- Indexes for table `player_relations`
--
ALTER TABLE `player_relations`
  ADD PRIMARY KEY (`player_one`,`player_two`) USING BTREE,
  ADD KEY `player_two` (`player_two`);

--
-- Indexes for table `player_stats`
--
ALTER TABLE `player_stats`
  ADD PRIMARY KEY (`xuid`,`type`),
  ADD KEY `kills` (`kills`),
  ADD KEY `wins` (`wins`),
  ADD KEY `bw_kills` (`bw_kills`),
  ADD KEY `bw_final_kills` (`bw_final_kills`),
  ADD KEY `bw_solo_final_kills` (`bw_solo_final_kills`),
  ADD KEY `bw_doubles_final_kills` (`bw_doubles_final_kills`),
  ADD KEY `bw_squads_final_kills` (`bw_squads_final_kills`),
  ADD KEY `bw_wins` (`bw_wins`),
  ADD KEY `bw_solo_wins` (`bw_solo_wins`),
  ADD KEY `bw_doubles_wins` (`bw_doubles_wins`),
  ADD KEY `bw_trios_wins` (`bw_trios_wins`),
  ADD KEY `bw_squads_wins` (`bw_squads_wins`),
  ADD KEY `duels_kills` (`duels_kills`),
  ADD KEY `duels_wins` (`duels_wins`),
  ADD KEY `mm_kills` (`mm_kills`),
  ADD KEY `mm_classic_kills` (`mm_classic_kills`),
  ADD KEY `mm_wins` (`mm_wins`),
  ADD KEY `mm_classic_wins` (`mm_classic_wins`),
  ADD KEY `bw_solo_kills` (`bw_solo_kills`),
  ADD KEY `bw_doubles_kills` (`bw_doubles_kills`),
  ADD KEY `bw_squads_kills` (`bw_squads_kills`),
  ADD KEY `ms_wins` (`ms_wins`),
  ADD KEY `sc_wins` (`sc_wins`),
  ADD KEY `sg_kills` (`sg_kills`),
  ADD KEY `sg_wins` (`sg_wins`),
  ADD KEY `sw_kills` (`sw_kills`),
  ADD KEY `sw_solo_kills` (`sw_solo_kills`),
  ADD KEY `sw_solo_normal_kills` (`sw_solo_normal_kills`),
  ADD KEY `sw_doubles_kills` (`sw_doubles_kills`),
  ADD KEY `sw_doubles_normal_kills` (`sw_doubles_normal_kills`),
  ADD KEY `sw_wins` (`sw_wins`),
  ADD KEY `sw_solo_wins` (`sw_solo_wins`),
  ADD KEY `sw_doubles_wins` (`sw_doubles_wins`),
  ADD KEY `tb_kills` (`tb_kills`),
  ADD KEY `tb_wins` (`tb_wins`),
  ADD KEY `deaths` (`deaths`),
  ADD KEY `cq_kills` (`cq_kills`),
  ADD KEY `cq_wins` (`cq_wins`),
  ADD KEY `cq_flags_captured` (`cq_flags_captured`),
  ADD KEY `uhc_kills` (`uhc_kills`),
  ADD KEY `uhc_wins` (`uhc_wins`),
  ADD KEY `online_time` (`online_time`),
  ADD KEY `type` (`type`),
  ADD KEY `xuid` (`xuid`),
  ADD KEY `losses` (`losses`),
  ADD KEY `tb_solo_kills` (`tb_solo_kills`),
  ADD KEY `tb_solo_wins` (`tb_solo_wins`),
  ADD KEY `tb_doubles_kills` (`tb_doubles_kills`),
  ADD KEY `tb_goals` (`tb_goals`),
  ADD KEY `tb_doubles_goals` (`tb_doubles_goals`),
  ADD KEY `tb_solo_goals` (`tb_solo_goals`),
  ADD KEY `tb_doubles_wins` (`tb_doubles_wins`),
  ADD KEY `md_kills` (`md_kills`),
  ADD KEY `md_wins` (`md_wins`),
  ADD KEY `kdr` (`kdr`),
  ADD KEY `wdr` (`wdr`),
  ADD KEY `bw_1v1_kills` (`bw_1v1_kills`),
  ADD KEY `bw_1v1_final_kills` (`bw_1v1_final_kills`),
  ADD KEY `bw_2v2_kills` (`bw_2v2_kills`),
  ADD KEY `bw_2v2_final_kills` (`bw_2v2_final_kills`),
  ADD KEY `bw_1v1_wins` (`bw_1v1_wins`),
  ADD KEY `bw_2v2_wins` (`bw_2v2_wins`);

--
-- Indexes for table `presents`
--
ALTER TABLE `presents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `win_streaks`
--
ALTER TABLE `win_streaks`
  ADD UNIQUE KEY `idx_xuid_gameKey` (`xuid`,`gameKey`),
  ADD KEY `best` (`best` DESC),
  ADD KEY `fk_gameKey` (`gameKey`);

--
-- Indexes for table `win_streak_game_keys`
--
ALTER TABLE `win_streak_game_keys`
  ADD PRIMARY KEY (`game_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guilds`
--
ALTER TABLE `guilds`
  MODIFY `guild_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guild_invites`
--
ALTER TABLE `guild_invites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_replay`
--
ALTER TABLE `match_replay`
  MODIFY `replay_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offline_messages`
--
ALTER TABLE `offline_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `guilds`
--
ALTER TABLE `guilds`
  ADD CONSTRAINT `guilds_ibfk_1` FOREIGN KEY (`leader`) REFERENCES `player_data` (`player`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guild_invites`
--
ALTER TABLE `guild_invites`
  ADD CONSTRAINT `guild_invites_ibfk_6` FOREIGN KEY (`guild_id`) REFERENCES `guilds` (`guild_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `guild_invites_ibfk_7` FOREIGN KEY (`inviter_xuid`) REFERENCES `player_data` (`xuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `guild_invites_ibfk_8` FOREIGN KEY (`invitee_xuid`) REFERENCES `player_data` (`xuid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guild_members`
--
ALTER TABLE `guild_members`
  ADD CONSTRAINT `guild_members_ibfk_1` FOREIGN KEY (`guild_id`) REFERENCES `guilds` (`guild_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `guild_members_ibfk_2` FOREIGN KEY (`player`) REFERENCES `player_data` (`player`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `parkour`
--
ALTER TABLE `parkour`
  ADD CONSTRAINT `parkour_ibfk_1` FOREIGN KEY (`xuid`) REFERENCES `player_data` (`xuid`) ON DELETE CASCADE;

--
-- Constraints for table `player_relations`
--
ALTER TABLE `player_relations`
  ADD CONSTRAINT `player_relations_ibfk_1` FOREIGN KEY (`player_one`) REFERENCES `player_data` (`player`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `player_relations_ibfk_2` FOREIGN KEY (`player_two`) REFERENCES `player_data` (`player`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `win_streaks`
--
ALTER TABLE `win_streaks`
  ADD CONSTRAINT `fk_gameKey` FOREIGN KEY (`gameKey`) REFERENCES `win_streak_game_keys` (`game_key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
