# Certificados .p12 y OpenSSL 3 (Hacienda CR)

Los archivos .p12 emitidos por Hacienda suelen usar el algoritmo **RC2-40-CBC**. En **OpenSSL 3.x** ese algoritmo está deshabilitado por defecto, por lo que PHP puede fallar al verificar o firmar con el mensaje:

- `error:0308010C:digital envelope routines::unsupported`

## Solución: activar el proveedor legacy

El proyecto incluye `config/openssl-legacy.cnf`. El servidor debe cargar esta configuración **antes** de iniciar PHP (no sirve ponerlo en `.env`).

### Apache

En el VirtualHost o en `.htaccess` (si permite `SetEnv`):

```apache
SetEnv OPENSSL_CONF /var/www/fe_ociannpayments/config/openssl-legacy.cnf
```

O en el bloque `<Directory>` del proyecto.

### PHP-FPM (pool)

En el archivo del pool (ej. `www.conf`):

```ini
env[OPENSSL_CONF] = /var/www/fe_ociannpayments/config/openssl-legacy.cnf
```

Luego reiniciar PHP-FPM y Apache (o nginx).

### Comprobación

Tras configurar, la verificación de credenciales y certificado en una empresa (.p12 + PIN) debería pasar si el PIN es correcto. El archivo `docs/310293428520o.p12` con PIN **1598** se ha comprobado que es válido cuando OpenSSL usa el proveedor legacy.
