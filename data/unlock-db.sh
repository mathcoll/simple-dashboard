echo ".dump" | sqlite3 dashboard.db | sqlite3 dashboard.db.new
chmod -c 777 dashboard.db.new
mv dashboard.db dashboard.db.bak
mv dashboard.db.new dashboard.db
