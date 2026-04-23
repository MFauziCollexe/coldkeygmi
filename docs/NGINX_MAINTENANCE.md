# Nginx Maintenance Page

File yang dipakai:

- Static page: `/public/maintenance.html`
- Redirect helper: `/public/502.html`
- Snippet config: `/docs/nginx-maintenance-snippet.conf`

## Kapan dipakai

Pakai konfigurasi ini kalau error muncul dari Nginx/proxy, misalnya:

- `502 Bad Gateway`
- `503 Service Unavailable`
- `504 Gateway Timeout`

Dalam kondisi itu, halaman Laravel sering tidak sempat tampil. Karena itu Nginx harus diarahkan ke file statis di folder `public`.

## Config inti

```nginx
error_page 502 503 504 /maintenance.html;

location = /maintenance.html {
    root /var/www/coldkeygmi/public;
    internal;
}
```

## Kalau pakai `proxy_pass`

```nginx
location / {
    try_files $uri $uri/ @laravel_upstream;
}

location @laravel_upstream {
    proxy_pass http://127.0.0.1:8080;
    proxy_intercept_errors on;
}
```

`proxy_intercept_errors on;` penting supaya error upstream bisa ditangkap lalu diarahkan ke `maintenance.html`.

## Kalau pakai `fastcgi_pass`

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_intercept_errors on;
}
```

## Setelah ubah config

```bash
nginx -t
sudo systemctl reload nginx
```

## Catatan

- Ganti path `root /var/www/coldkeygmi/public;` sesuai lokasi project di server Anda.
- Ganti `proxy_pass` atau `fastcgi_pass` sesuai arsitektur server yang dipakai.
- Kalau ingin maintenance manual walau app masih hidup, Anda juga bisa arahkan sementara route `/` ke `/maintenance.html` dari Nginx.
