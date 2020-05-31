# LineageOTA

A simple implementation of the OTA server for custom Lineage OS builds. No dependencies, no database. Written in (dirty) PHP. Not designed for large infrastructures.

## Installation

### nginx

```
server {
  listen 0.0.0.0:80; # Warning! Configure nginx to use TLS or put it behind a reverse proxy: the app is designed to be used with https only (protocol is hardcoded)
  root /var/www/html;

  index index.php;

  gzip off;

  location ~ \.php$ {
    fastcgi_split_path_info   ^(.+.php)(/.*)$;
    include                   fastcgi_params;
    fastcgi_param             SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param             PATH_INFO $fastcgi_path_info;
    fastcgi_param             modHeadersAvailable true;
    fastcgi_param             front_controller_active true;
    fastcgi_pass              unix:/var/run/php/php7.3-fpm.sock;
    fastcgi_intercept_errors  on;
    fastcgi_request_buffering off;
    fastcgi_read_timeout      300;
  }

  location / {
    try_files $uri /index.php;
  }

}
```

### ROM integration

Fork https://github.com/LineageOS/android_packages_apps_Updater and change the server host name like in https://github.com/BLeQuerrec/android_packages_apps_Updater/commit/88ec53422a41f3436c8f881e911c5f29ad8df929 (**just change the host name**).

Then edit your `roomservice.xml` file to add this:

```
<remove-project name="LineageOS/android_packages_apps_Updater" />
<project path="packages/apps/Updater" name="BLeQuerrec/android_packages_apps_Updater/" remote="github" revision="lineage-16.0" />
```

(Use the URI of your fork.)

Alternatively, you can edit the `build.prop` file to change the value of `lineage.updater.uri`, but I didn't test it.

## Add a new device

Just create a directory in `builds/` with the codename of the device (ie. `builds/daisy/`). Upload your `.zip` files from `out/target/product/<device>/` **and** the `.md5sum` files in this directory. Keep the filename in the form of `lineage-VERSION-YYYYMMDD-ROMTYPE-DEVICENAME.zip` Make sure the server can write in the directory.

You can write an empty `lineage-VERSION-YYYYMMDD-ROMTYPE-DEVICENAME.zip.ignore` while sending the file. This will prevent the updater from displaying an update not fully uploaded.

### Warning about timestamps

LOS's updater expects the build's timestamp while the generated `json` response returns the timestamp of the lastest file modification. You can fix this after a successful build with:

`touch -d @$(grep 'ro.build.date.utc' $OUT/system/build.prop | sed 's/ro\.build\.date\.utc\=//i') $OUT/lineage-VERSION-YYYYMMDD-ROMTYPE-DEVICENAME.zip`

If you upload your builds via `scp`, do not forget the `-p` option.

## How it works

The app will parse the client's request to find potential files. Files are firstly filtered by date.

Then, the app checks if the md5 sum is correct (by calculating it then comparing it to the according `.md5sum` file). If it's correct, a `.checked` file is written to avoid to recalculate the sum at each request (if the sum is incorrect, the file is removed).

Finally, a `json` response is made.
