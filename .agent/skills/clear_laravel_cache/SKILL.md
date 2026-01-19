---
name: clear_laravel_cache
description: Clears the Laravel application cache (route, config, view) inside the Docker container to ensure changes are reflected immediately.
---

# Clear Laravel Cache (Docker)

Use this skill when changes to routes, configuration, or views are not reflecting in the application. This is common in Docker environments with named volumes for caching.

## Instructions

Run the following command to clear all caches directly inside the `laravel_app` container:

```powershell
docker exec laravel_app php artisan optimize:clear
```
