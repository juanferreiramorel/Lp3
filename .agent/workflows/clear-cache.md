---
description: Clear Laravel cache inside the Docker container
---

1. Clear route, config, and application cache inside the container
// turbo
docker exec laravel_app php artisan optimize:clear
