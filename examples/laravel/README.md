# Laravel example

The files here show where the SDK goes in a Laravel 10, 11 or 12 app. Copy them into your app;
they are not a complete project.

1. Install the SDK:

   ```bash
   composer require lifeastroapi/lifeastroapi
   ```

2. Put the key in `.env` (never in code or in git):

   ```dotenv
   LIFEASTRO_API_KEY=dv_live_...
   ```

3. Add the `lifeastro` entry of [`config/services.php`](config/services.php) to your
   `config/services.php`.

4. Register the client once, in [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php).
   Laravel then hands the same `LifeAstro` instance to every controller, job and command that
   asks for it, so the HTTP connection is reused.

5. Use it in a controller: [`app/Http/Controllers/PanchangController.php`](app/Http/Controllers/PanchangController.php),
   with the route in [`routes/web.php`](routes/web.php).

```bash
curl "http://localhost:8000/panchang?lat=23.0225&lon=72.5714&lang=gu"
```

Run `php artisan config:cache` in production as usual: the key is read through `config()`, so it
still works with a cached config.
