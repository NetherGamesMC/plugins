DELIMITER &&
CREATE OR REPLACE PROCEDURE increment_win_streak
(
    IN p_xuid varchar(128),
    IN p_gameKey VARCHAR(255),
    OUT r_xuid varchar(128),
    OUT r_gameKey VARCHAR(255),
    OUT r_current INT,
    OUT r_best INT,
    OUT r_bestChanged BOOLEAN
)
BEGIN
    DECLARE v_xuid varchar(128);
    DECLARE v_gameKey VARCHAR(255);
    DECLARE v_current INT;
    DECLARE v_best INT;
    DECLARE v_prev_best INT;
    SELECT best INTO v_prev_best FROM win_streaks WHERE xuid = p_xuid and gameKey=p_gameKey;

    INSERT INTO win_streaks (xuid, gameKey, current, best)
    VALUES (p_xuid, p_gameKey, 1, 1)
    ON DUPLICATE KEY UPDATE current = current + 1, best = IF(current > best, current, best);
    
    -- Retrieve the updated row data
    SELECT xuid, gameKey, current, best
    INTO v_xuid, v_gameKey, v_current, v_best
    FROM win_streaks
    WHERE xuid = p_xuid AND gameKey = p_gameKey;
    
    -- Set the output parameters to the updated row data
    SET r_xuid = v_xuid;
    SET r_gameKey = v_gameKey;
    SET r_current = v_current;
    SET r_best = v_best;
    SET r_bestChanged = v_best != v_prev_best;
END &&
DELIMITER ;