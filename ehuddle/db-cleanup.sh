#!/bin/bash

# E-Huddle Selective DB Cleanup Daemon
# This script deletes ONLY data created by players that is older than 35 minutes.
# It preserves all seeded challenge data (Users 1-8, Posts 1-11, etc.)

DB_FILE="/var/www/html/data/ehuddle.db"
MAX_AGE_MINUTES=35

echo "[$(date)] Selective DB Cleanup Daemon started."

while true; do
    if [ -f "$DB_FILE" ]; then
        # Use sqlite3 to run selective deletions
        # Note: We use datetime('now', '-35 minutes') to match the threshold
        sqlite3 "$DB_FILE" <<EOF
-- Delete non-seeded users older than 35m
DELETE FROM users WHERE id > 8 AND created_at < datetime('now', '-$MAX_AGE_MINUTES minutes');

-- Delete non-seeded posts older than 35m
DELETE FROM posts WHERE id > 11 AND created_at < datetime('now', '-$MAX_AGE_MINUTES minutes');

-- Delete non-seeded comments older than 35m
DELETE FROM comments WHERE id > 12 AND created_at < datetime('now', '-$MAX_AGE_MINUTES minutes');

-- Delete non-seeded likes (likes on non-seeded posts or by non-seeded users)
DELETE FROM likes WHERE (user_id > 8 OR post_id > 11) AND created_at < datetime('now', '-$MAX_AGE_MINUTES minutes');

-- Delete non-seeded follows
DELETE FROM follows WHERE (follower_id > 8 OR following_id > 8) AND created_at < datetime('now', '-$MAX_AGE_MINUTES minutes');

-- Delete non-seeded bookmarks
DELETE FROM bookmarks WHERE created_at < datetime('now', '-$MAX_AGE_MINUTES minutes');

-- Vacuum to actually free up disk space
VACUUM;
EOF
        echo "[$(date)] Selective cleanup completed."
    fi
    
    # Sleep for 5 minutes before checking again
    sleep 300
done
