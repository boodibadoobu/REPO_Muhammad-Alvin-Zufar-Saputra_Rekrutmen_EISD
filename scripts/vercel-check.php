<?php

// The community builder installs with --ignore-platform-reqs internally.
// Explicitly verify requirements before allowing a successful deployment.
$missing = [];
foreach (['gd', 'exif', 'pdo_pgsql', 'mbstring', 'fileinfo'] as $extension) {
    if (! extension_loaded($extension)) {
        $missing[] = $extension;
    }
}
if (PHP_VERSION_ID < 80401 || $missing !== []) {
    fwrite(STDERR, 'PleaseFix requires PHP >=8.4.1 and extensions: '.implode(', ', $missing).PHP_EOL);
    exit(1);
}
$gd = gd_info();
foreach (['JPEG Support', 'PNG Support', 'WebP Support'] as $format) {
    if (empty($gd[$format])) {
        fwrite(STDERR, 'GD missing '.$format.PHP_EOL);
        exit(1);
    }
}
echo 'PleaseFix runtime: PHP '.PHP_VERSION.', GD JPEG/PNG/WebP, EXIF and PostgreSQL OK.'.PHP_EOL;
