<?php

// Generate a private import artifact without printing credentials.
require __DIR__.'/../vendor/autoload.php';

$root = dirname(__DIR__);
$destination = $root.'/.env.production';
if (file_exists($destination)) {
    fwrite(STDERR, '.env.production already exists; refusing to overwrite.'.PHP_EOL);
    exit(1);
}
$source = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
$values = [
    'APP_NAME' => 'PleaseFix',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://repo-muhammad-alvin-zufar-saputra-r-one.vercel.app',
    'APP_KEY' => $source['APP_KEY'] ?? '',
    'APP_LOCALE' => 'id',
    'APP_FALLBACK_LOCALE' => 'id',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'warning',
    'DB_CONNECTION' => 'pgsql',
];
foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_SSLMODE'] as $key) {
    $values[$key] = $source[$key] ?? '';
}
foreach (['APP_KEY', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
    if ($values[$key] === '') {
        fwrite(STDERR, 'Missing local variable: '.$key.PHP_EOL);
        exit(1);
    }
}
$projectRef = '';
if (preg_match('/^postgres\.([a-z0-9]+)$/', $values['DB_USERNAME'], $match)) {
    $projectRef = $match[1];
}
$region = 'FILL_SUPABASE_STORAGE_REGION';
if (preg_match('/^aws-\d+-([a-z]+-[a-z]+-\d+)\.pooler\.supabase\.com$/', $values['DB_HOST'], $match)) {
    $region = $match[1];
}
$bucket = $source['AWS_BUCKET'] ?: 'pleasefix';
$values += [
    'SESSION_DRIVER' => 'database',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_LIFETIME' => '120',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'log',
    'FILESYSTEM_DISK' => 'public',
    'PUBLIC_DISK_DRIVER' => 's3',
    'AWS_ACCESS_KEY_ID' => ($source['AWS_ACCESS_KEY_ID'] ?? '') ?: 'FILL_SUPABASE_S3_ACCESS_KEY_ID',
    'AWS_SECRET_ACCESS_KEY' => ($source['AWS_SECRET_ACCESS_KEY'] ?? '') ?: 'FILL_SUPABASE_S3_SECRET_ACCESS_KEY',
    'AWS_DEFAULT_REGION' => $region,
    'AWS_BUCKET' => $bucket,
    'AWS_ENDPOINT' => ($source['AWS_ENDPOINT'] ?? '') ?: ($projectRef ? 'https://'.$projectRef.'.storage.supabase.co/storage/v1/s3' : 'FILL_SUPABASE_S3_ENDPOINT'),
    'AWS_URL' => ($source['AWS_URL'] ?? '') ?: ($projectRef ? 'https://'.$projectRef.'.supabase.co/storage/v1/object/public/'.$bucket : 'FILL_SUPABASE_PUBLIC_BUCKET_URL'),
    'AWS_USE_PATH_STYLE_ENDPOINT' => 'true',
];
$output = '# Private Vercel import. Do not commit or share.'.PHP_EOL
    .'# Fill FILL_* values before import. Bucket assumed pleasefix; confirm in Supabase.'.PHP_EOL
    .'# APP_URL comes from deployment screenshot; confirm your production domain.'.PHP_EOL
    .'# Storage endpoint and region inferred from database; confirm in Storage Settings.'.PHP_EOL;
foreach ($values as $key => $value) {
    $quoted = str_replace(['\\', '"', '$', "\r", "\n"], ['\\\\', '\\"', '\\$', '\\r', '\\n'], $value);
    $output .= $key.'="'.$quoted.'"'.PHP_EOL;
}
// Verify round-trip parsing before saving any credential-bearing artifact.
if (Dotenv\Dotenv::parse($output) !== $values) {
    throw new RuntimeException('Environment serialization verification failed.');
}
file_put_contents($destination, $output, LOCK_EX);
echo 'Created .env.production; local .env unchanged.'.PHP_EOL;
foreach ($values as $key => $value) {
    if (str_starts_with($value, 'FILL_')) {
        echo 'Needs input: '.$key.PHP_EOL;
    }
}
