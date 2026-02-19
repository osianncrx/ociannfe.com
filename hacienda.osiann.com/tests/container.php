<?php

use Defuse\Crypto\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require "db.php";

$config = [
    'dominio' => 'localhost',
    'base_datos' => 'hacienda',
    'usuario' => 'hacienda',
    'contraseña' => 'kjmc15981598',
    'cryptoKey' => 'def0000062feacc74abef4a7714ac48a5e1e1ec2f01c8be6b1a005d84d53911f0d13aa069b30cf408419eeedd2de8abf3610e92341fb548319df21f6d1e521bf8936b65f'
];

$db = new \mysqli(
    $config['dominio'],
    $config['usuario'],
    $config['contraseña'],
    $config['base_datos']
);

$log = new Logger('facturador');
$log->pushHandler(new StreamHandler(__DIR__ . '/facturador.log', Logger::DEBUG));

$container = [
    'crypto_key' => Key::loadFromAsciiSafeString($config['cryptoKey']), // corregido nombre clave
    'db' => $db,
    'log' => $log
];

return $container;
