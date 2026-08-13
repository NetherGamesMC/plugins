-- #!mysql
-- #  { cosmetics
-- #    { load
-- #        :status int
SELECT * FROM cosmetics WHERE status = :status;
-- #    }
-- #  }

-- #  { player_presents
-- #    { load
-- #        :xuid string
SELECT presents FROM player_presents WHERE xuid = :xuid;
-- #    }

-- #    { save
-- #        :xuid string
-- #        :presents string
INSERT INTO player_presents (xuid, presents) VALUES (:xuid, :presents) ON DUPLICATE KEY UPDATE presents = VALUES(presents);
-- #    }
-- #  }

-- #  { presents
-- #    { load
SELECT * FROM presents;
-- #    }

-- #    { add
-- #        :id int
-- #        :hash int
INSERT INTO presents (id, hash) VALUES (:id, :hash)  ON DUPLICATE KEY UPDATE hash = VALUES(hash);
-- #    }
-- #  }

-- #  { parkour
-- #    { best
SELECT record FROM parkour ORDER BY record LIMIT 1;
-- #    }

-- #    { load
-- #        :xuid string
SELECT record FROM parkour WHERE xuid = :xuid;
-- #    }

-- #    { save
-- #        :xuid string
-- #        :record float
INSERT INTO parkour (xuid, record) VALUES (:xuid, :record) ON DUPLICATE KEY UPDATE record = VALUES(record);
-- #    }
-- #  }

-- #  { guild
-- #    { delete
-- #        :guild_id int
DELETE FROM guilds WHERE guild_id = :guild_id;
-- #    }

-- #    { load_guild_base
-- #        :guild_id int
SELECT * FROM guilds WHERE guild_id = :guild_id;
-- #    }

-- #    { load_guild_name
-- #        :guild_id int
SELECT guild_name FROM guilds WHERE guild_id = :guild_id;
-- #    }

-- #    { load_guild_members
-- #        :guild_id int
SELECT * FROM guild_members WHERE guild_id = :guild_id;
-- #    }

-- #    { search_player_guild
-- #        :player string
SELECT guild_id FROM guild_members WHERE player = :player;
-- #    }

-- #    { set_motd
-- #        :guild_id int
-- #        :motd string
UPDATE guilds SET motd = :motd WHERE guild_id = :guild_id;
-- #    }

-- #    { add_xp
-- #        :guild_id int
-- #        :xp int
UPDATE guilds SET xp = xp + :xp WHERE guild_id = :guild_id;
-- #    }

-- #    { get_xp
-- #        :guild_id int
SELECT xp FROM guilds WHERE guild_id = :guild_id;
-- #    }

-- #    { set_disabled
-- #        :guild_id int
-- #        :disabled int
UPDATE guilds SET disabled = :disabled WHERE guild_id = :guild_id;
-- #    }

-- #    { set_tag
-- #        :guild_id int
-- #        :tag string
UPDATE guilds SET tag = :tag WHERE guild_id = :guild_id;
-- #    }

-- #    { exists
-- #        :guild_name string
SELECT guild_name FROM guilds WHERE guild_name = :guild_name;
-- #    }

-- #    { rename
-- #        :guild_id int
-- #        :guild_name string
UPDATE guilds SET guild_name = :guild_name WHERE guild_id = :guild_id;
-- #    }

-- #    { create
-- #        :guild_name string
-- #        :leader string
INSERT INTO guilds(guild_name, leader) VALUES (:guild_name, :leader);
-- #    }

-- #    { add_member
-- #        :guild_id int
-- #        :role int
-- #        :player_name string
INSERT INTO guild_members (guild_id, role, player) VALUES (:guild_id, :role, :player_name) ON DUPLICATE KEY UPDATE role = :role;
-- #    }

-- #    { update_leader
-- #        :guild_id int
-- #        :old_leader string
-- #        :new_leader string
UPDATE guilds, guild_members SET guilds.leader = :new_leader, guild_members.role = ( CASE WHEN guild_members.player = :old_leader THEN 1 WHEN guild_members.player = :new_leader THEN 2 END ) WHERE guild_members.guild_id = :guild_id AND guilds.guild_id = :guild_id AND guild_members.player IN (:old_leader, :new_leader);
-- #    }

-- #    { set_leader
-- #        :guild_id int
-- #        :player_name string
UPDATE guilds SET leader = :player_name WHERE guild_id = :guild_id;
-- #    }

-- #    { remove_member
-- #        :player_name string
-- #        :guild_id int
DELETE FROM guild_members WHERE player = :player_name AND guild_id = :guild_id;
-- #    }

-- #    { get_guild_count
-- #        :guild_id int
SELECT COUNT(*) as members FROM guild_members WHERE guild_id = :guild_id;
-- #    }

-- #  }

-- #  { replay
-- #    { load_by_player
-- #        :players string
SELECT replay_id, server_type, game_type, map_name, players, private, touch_only, time FROM match_replay WHERE players LIKE :players AND uploaded = 1 ORDER BY replay_id DESC LIMIT 100;
-- #    }

-- #    { load_by_replay_id
-- #        :replay_id int
SELECT replay_id, protocol_id, server_type, game_type, map_name, players, private, touch_only, time FROM match_replay WHERE replay_id = :replay_id AND uploaded = 1;
-- #    }

-- #    { remove_expired
-- #        :time int
DELETE FROM match_replay WHERE time < :time AND flag = 0;
-- #    }

-- #    { get_expired
-- #        :time int
SELECT replay_id FROM match_replay WHERE time < :time AND flag = 0;
-- #    }

-- #    { set_flag
-- #        :replay_id int
-- #        :flag int
UPDATE match_replay SET flag = :flag WHERE replay_id = :replay_id;
-- #    }

-- #    { uploaded
-- #        :replay_id int
UPDATE match_replay SET uploaded = 1 WHERE replay_id = :replay_id;
-- #    }

-- #    { create
-- #        :server_type string
-- #        :game_type string
-- #        :map_name string
-- #        :players string
-- #        :touch_only int
-- #        :private int
-- #        :protocol_id int
-- #        :time int
INSERT INTO match_replay (server_type, game_type, map_name, players, private, touch_only, protocol_id, time) VALUES (:server_type, :game_type, :map_name, :players, :private, :touch_only, :protocol_id, :time);
-- #    }
-- #  }

-- #  { player_relations
-- #    { get
-- #        :player string
SELECT * FROM player_relations WHERE player_one = :player OR player_two = :player;
-- #    }

-- #    { remove
-- #        :player_one string
-- #        :player_two string
DELETE FROM player_relations WHERE (player_one = :player_one AND player_two = :player_two) OR (player_one = :player_two AND player_two = :player_one);
-- #    }

-- #    { get_count
-- #        :player string
-- #        :status int
SELECT COUNT(*) as count FROM player_relations WHERE (player_one = :player OR player_two = :player) AND status = :status;
-- #    }

-- #    { set
-- #        :player_one string
-- #        :player_two string
-- #        :status int
IF EXISTS (SELECT * FROM player_relations WHERE player_one = :player_two AND player_two = :player_one) THEN
UPDATE player_relations SET status = :status WHERE player_one = :player_two AND player_two = :player_one;
ELSE
INSERT INTO player_relations (player_one, player_two, status) VALUES (:player_one, :player_two, :status) ON DUPLICATE KEY UPDATE status = VALUES(status);
END IF;
-- #    }

-- #    { update1
-- #        :old_player string
-- #        :new_player string
UPDATE player_relations SET player_one = :new_player WHERE player_one = :old_player;
-- #    }

-- #    { update2
-- #        :old_player string
-- #        :new_player string
UPDATE player_relations SET player_two = :new_player WHERE player_two = :old_player
-- #    }
-- #  }

-- #  { offline_messages
-- #    { get
-- #        :player_name string
-- #        :player_xuid string
SELECT * FROM offline_messages WHERE player = :player_name OR player = :player_xuid;
-- #    }

-- #    { delete
-- #        :player_name string
-- #        :player_xuid string
DELETE FROM offline_messages WHERE player = :player_name OR player = :player_xuid;
-- #    }

-- #    { send
-- #        :player string
-- #        :message string
INSERT INTO offline_messages (player, message) VALUES (:player, :message);
-- #    }
-- #  }

-- #  { player_stats
-- #    { get_types
-- #        :xuid string
SELECT type FROM player_stats WHERE xuid = :xuid;
-- #    }

-- #    { increase_online_time
-- #        :xuid string
-- #        :time int
UPDATE player_stats SET online_time = online_time + :time WHERE xuid = :xuid;
-- #    }

-- #    { get_online_time
-- #        :xuid string
-- #        :type int
SELECT online_time FROM player_stats WHERE xuid = :xuid AND type = :type;
-- #    }
-- #  }

-- #  { player
-- #    { send_first_join
-- #      :xuid string
-- #      :player string
-- #      :first_joined int
-- #      :last_joined int
-- #      :last_server string
INSERT INTO player_data (xuid, player, first_joined, last_joined, last_server) VALUES (:xuid, :player, :first_joined, :last_joined, :last_server)
-- #    }

-- #    { send_join
-- #      :xuid string
-- #      :last_joined int
-- #      :last_server string
UPDATE player_data SET last_joined = :last_joined, last_server = :last_server WHERE xuid = :xuid;
-- #    }

-- #    { send_server
-- #        :xuid string
-- #        :last_server string
UPDATE player_data SET last_server = :last_server WHERE xuid = :xuid;
-- #    }

-- #    { name_exists
-- #        :player string
SELECT player FROM player_data WHERE player = :player;
-- #    }

-- #    { get_xuid
-- #        :player string
SELECT player, xuid FROM player_data WHERE player = :player;
-- #    }

-- #    { get_name
-- #        :xuid string
SELECT player FROM player_data WHERE xuid = :xuid;
-- #    }

-- #    { get_cosmetics
-- #        :player_xuid string
SELECT cosmetics FROM player_data WHERE xuid = :player_xuid;
-- #    }

-- #    { update_cosmetics
-- #        :player_xuid string
-- #        :cosmetics string
UPDATE player_data SET cosmetics = :cosmetics WHERE xuid = :player_xuid;
-- #    }

-- #    { load
-- #        :xuid string
-- #        :player_name string
SELECT * FROM (SELECT * FROM player_data WHERE xuid = :xuid) t1 LEFT JOIN (SELECT xuid AS relatedXuid FROM player_data WHERE player = :player_name) t2 ON TRUE
UNION
SELECT * FROM (SELECT * FROM player_data WHERE xuid = :xuid) t1 RIGHT JOIN (SELECT xuid AS relatedXuid FROM player_data WHERE player = :player_name) t2 ON TRUE;
-- #    }

-- #    { change_name
-- #        :xuid string
-- #        :player string
UPDATE player_data SET player = :player WHERE xuid = :xuid;
-- #    }

-- #    { load_dms_status
-- #        :player string
SELECT xuid, dms_status FROM player_data WHERE player = :player;
-- #    }

-- #    { load_permissions_and_extra_friends
-- #        :player string
SELECT permissions, rank, status_credits, titan_expire, vote_time, extra_friends FROM player_data WHERE player = :player;
-- #    }
-- #  }

-- #  { map_votes
-- #    { add_votes
-- #      :game_mode string
-- #      :map_name string
-- #      :votes int
INSERT INTO map_votes (game_mode, map_name, votes) VALUES (:game_mode, :map_name, :votes) ON DUPLICATE KEY UPDATE votes = votes + VALUES(votes);
-- #    }
-- #  }

-- #  { guild_invites
-- #    { add_invite
-- #      :guild_id int
-- #      :inviter_xuid string
-- #      :invitee_xuid string
INSERT INTO guild_invites (guild_id, inviter_xuid, invitee_xuid) VALUES (:guild_id, :inviter_xuid, :invitee_xuid);
-- #    }

-- #    { remove_invite
-- #      :guild_id int
-- #      :invitee_xuid string
DELETE FROM guild_invites WHERE guild_id = :guild_id AND invitee_xuid = :invitee_xuid;
-- #    }

-- #    { remove_invites
-- #      :invitee_xuid string
DELETE FROM guild_invites WHERE invitee_xuid = :invitee_xuid;
-- #    }

-- #    { get_invites
-- #      :invitee_xuid string
SELECT guilds.guild_id, guild_name FROM guild_invites, guilds WHERE invitee_xuid = :invitee_xuid AND guilds.guild_id = guild_invites.guild_id;
-- #    }
-- #  }

-- #  { streaks 
-- #    { increment 
-- #      :xuid string
-- #      :gameKey string
CALL increment_win_streak(:xuid, :gameKey, @r_xuid, @r_gameKey, @r_current, @r_best, @r_bestChanged);
-- #&
SELECT @r_xuid as xuid, @r_gameKey as gameKey, @r_current as current, @r_best as best, @r_bestChanged as bestChanged;
-- #    }

-- #    { reset
-- #      :xuid string
-- #      :gameKey string
UPDATE win_streaks SET current = 0 WHERE xuid = :xuid AND gameKey = :gameKey;
-- #    }

-- #    { get_all
-- #      :xuid string
SELECT * FROM win_streaks WHERE xuid = :xuid;         
-- #    }

-- #    { get_single
-- #      :xuid string
-- #      :gameKey string
SELECT * FROM win_streaks WHERE xuid = :xuid AND gameKey = :gameKey;
-- #    }
-- #  }