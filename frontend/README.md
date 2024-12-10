# Těrlicko.cz

## Development
Simply run `docker compose up`

Application runs at http://localhost:8080

## Quick start

To run these commands, containers must be running.

Create your admin user run (replace email+password placeholders):
```bash
docker compose exec web bin/console app:user:register admin@admin.com admin
```

### Adminer (Database)

http://localhost:8000

Driver: `postgres`  
User: `postgres`  
Password: `postgres`  
Database: `terlicko`

### Mail catcher

http://localhost:8025
