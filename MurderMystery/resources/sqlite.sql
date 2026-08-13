-- #!sqlite
-- #  { init
CREATE TABLE IF NOT EXISTS PlayerChances (player TEXT PRIMARY KEY, murder_chance INTEGER, detective_chance INTEGER, infected_chance INTEGER)
-- #  }

-- #  { load
-- #    :player string
SELECT * FROM PlayerChances WHERE player = :player
-- #  }

-- #  { save
-- #    :player string
-- #    :murder_chance int
-- #    :detective_chance int
-- #    :infected_chance int
INSERT or REPLACE INTO PlayerChances (player, murder_chance, detective_chance, infected_chance) VALUES (:player, :murder_chance, :detective_chance, :infected_chance)
-- #  }
