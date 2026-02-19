# Ociann Facturación Electrónica C.R.

Plataforma SaaS de facturación electrónica para Costa Rica. Permite a múltiples empresas emitir, recibir y gestionar comprobantes electrónicos conforme a la normativa del Ministerio de Hacienda, todo desde una interfaz web moderna y una API REST completa.

---

## Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Requisitos](#requisitos)
4. [Instalación](#instalación)
5. [Configuración](#configuración)
6. [Multi-Tenancy y Planes](#multi-tenancy-y-planes)
7. [Gestión de Empresas](#gestión-de-empresas)
8. [Facturación Electrónica](#facturación-electrónica)
9. [Panel de Administración](#panel-de-administración)
10. [API REST v1](#api-rest-v1)
11. [Autenticación y Autorización](#autenticación-y-autorización)
12. [Cola de Procesamiento](#cola-de-procesamiento)
13. [Certificados .p12 y OpenSSL](#certificados-p12-y-openssl)
14. [Estructura del Proyecto](#estructura-del-proyecto)
15. [Comandos Útiles](#comandos-útiles)

---

## Descripción General

**Ociann Facturación Electrónica C.R.** es un sistema completo para la emisión y recepción de comprobantes electrónicos en Costa Rica, diseñado como plataforma SaaS multi-tenant. Cada tenant (organización) puede registrar múltiples empresas, cada una con sus propias credenciales del Ministerio de Hacienda, y emitir facturas electrónicas tanto desde la interfaz web como desde cualquier sistema externo vía API.

### Funcionalidades principales

- **Emisión de comprobantes**: facturas electrónicas, notas de crédito, notas de débito, tiquetes electrónicos y facturas de compra/exportación.
- **Recepción de comprobantes**: aceptación y rechazo de documentos recibidos.
- **Consulta de estado**: verificación del estado de cada comprobante en Hacienda.
- **Descarga de XML**: acceso a los XML firmados enviados y las respuestas de Hacienda.
- **Multi-empresa**: un mismo tenant puede gestionar varias empresas, incluso con las mismas credenciales de Hacienda pero diferentes sucursales.
- **Verificación de credenciales**: validación en tiempo real del usuario/contraseña MH y del certificado .p12 con su PIN.
- **Consulta de contribuyentes**: búsqueda automática de datos de empresas en el API de Hacienda por número de cédula.
- **API REST completa**: autenticación vía tokens Sanctum o API Keys para integración con sistemas externos.
- **Panel de administración**: gestión de tenants, usuarios, planes y logs del sistema.

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                    Cliente (Navegador)                   │
│              Bootstrap 5 + Blade + JavaScript            │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│                     Apache 2 + mod_php                   │
│                   https://ociannfe.com                    │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│                    Laravel 12 (PHP 8.2)                   │
│                                                          │
│  ┌─────────┐  ┌──────────────┐  ┌─────────────────┐     │
│  │ Web     │  │ API REST v1  │  │ Webhook         │     │
│  │ Routes  │  │ /api/v1/...  │  │ /api/v1/webhook │     │
│  └────┬────┘  └──────┬───────┘  └───────┬─────────┘     │
│       │              │                  │                │
│  ┌────▼──────────────▼──────────────────▼──────────┐     │
│  │              Middleware Pipeline                  │     │
│  │  Auth → TenantScope → CheckSubscription →        │     │
│  │  CheckPlanLimits                                 │     │
│  └──────────────────────┬───────────────────────────┘     │
│                         │                                │
│  ┌──────────────────────▼───────────────────────────┐     │
│  │             FacturacionService                    │     │
│  │  - Emisión y firma XML (XAdES)                    │     │
│  │  - Envío a Hacienda (OAuth2)                      │     │
│  │  - Consulta de estados                            │     │
│  │  - Verificación de credenciales                   │     │
│  └──────────────────────┬───────────────────────────┘     │
│                         │                                │
│  ┌──────────┐  ┌────────▼────────┐  ┌───────────────┐    │
│  │ Eloquent │  │ contica/        │  │ Queue Jobs    │    │
│  │ ORM      │  │ facturacion-cr  │  │ (async)       │    │
│  └────┬─────┘  └────────┬────────┘  └───────┬───────┘    │
└───────┼─────────────────┼───────────────────┼────────────┘
        │                 │                   │
┌───────▼─────────────────▼───────────────────▼────────────┐
│                      MySQL 8.x                            │
│                                                          │
│  users, tenants, plans, subscriptions, api_keys          │
│  fe_empresas, fe_emisiones, fe_emision_lineas            │
│  fe_recepciones, fe_cola, fe_ambientes, fe_tokens        │
│  fe_monolog, fe_ratelimiting, fe_settings                │
└──────────────────────────────────────────────────────────┘
        │
        ▼
┌──────────────────────────────────────────────────────────┐
│              Ministerio de Hacienda CR                    │
│  - IDP (autenticación OAuth2)                            │
│  - API Recepción de Comprobantes                         │
│  - API Consulta de Contribuyentes                        │
└──────────────────────────────────────────────────────────┘
```

### Stack tecnológico

| Componente | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade + Bootstrap 5 |
| Base de datos | MySQL 8.x |
| Servidor web | Apache 2 con mod_php |
| Autenticación API | Laravel Sanctum + API Keys |
| Roles y permisos | Spatie Laravel Permission |
| Encriptación | Defuse PHP Encryption |
| XML | Sabre/XML |
| Firma digital | XAdES (vía contica/facturador-electronico-cr) |
| Almacenamiento | Local o Amazon S3 (League/Flysystem) |
| HTTP Client | Guzzle / Laravel Http |
| Colas | Laravel Queue (database driver) |

---

## Requisitos

- PHP 8.2 o superior con extensiones: `openssl`, `mbstring`, `pdo_mysql`, `xml`, `curl`, `gd`
- MySQL 8.0 o superior
- Apache 2.4 con `mod_rewrite` y `mod_php`
- Composer 2.x
- Node.js 18+ y npm (para compilar assets con Vite)
- Certificado SSL (Let's Encrypt recomendado)

---

## Instalación

### 1. Clonar el repositorio

```bash
cd /var/www
git clone <repositorio> fe_ociannpayments
cd fe_ociannpayments
```

### 2. Instalar dependencias

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de la base de datos y demás configuraciones (ver sección [Configuración](#configuración)).

### 4. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Esto crea todas las tablas, los roles (`super_admin`, `admin`, `user`), los planes y el usuario administrador inicial.

### 5. Configurar Apache

Crear el VirtualHost apuntando a `/var/www/fe_ociannpayments/public`:

```apache
<VirtualHost *:443>
    ServerName ociannfe.com
    DocumentRoot /var/www/fe_ociannpayments/public

    SetEnv OPENSSL_CONF /var/www/fe_ociannpayments/config/openssl-legacy.cnf

    <Directory /var/www/fe_ociannpayments/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLCertificateFile /etc/letsencrypt/live/ociannfe.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/ociannfe.com/privkey.pem
</VirtualHost>
```

Agregar la variable de entorno de OpenSSL en `/etc/apache2/envvars`:

```bash
export OPENSSL_CONF=/var/www/fe_ociannpayments/config/openssl-legacy.cnf
```

### 6. Permisos

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 7. Reiniciar Apache

```bash
systemctl restart apache2
```

---

## Configuración

### Variables de entorno principales (.env)

| Variable | Descripción | Ejemplo |
|---|---|---|
| `APP_NAME` | Nombre de la aplicación | `"Ociann Facturacion Electronica C.R."` |
| `APP_URL` | URL base del sistema | `https://ociannfe.com` |
| `DB_DATABASE` | Nombre de la base de datos | `fe_ociannpayments` |
| `DB_USERNAME` | Usuario MySQL | `root` |
| `DB_PASSWORD` | Contraseña MySQL | `****` |
| `FE_STORAGE_PATH` | Ruta para almacenar XML | `storage/app/comprobantes` |
| `FE_STORAGE_TYPE` | Tipo de almacenamiento (`local` o `s3`) | `local` |
| `FE_CRYPTO_KEY` | Clave de encriptación Defuse para credenciales MH | (generada) |
| `FE_CALLBACK_URL` | URL de callback para webhooks de Hacienda | `https://ociannfe.com/api/v1/webhook/hacienda` |
| `FE_PROVEEDOR_SISTEMAS` | Cédula del proveedor de sistemas | `3102877461` |
| `QUEUE_CONNECTION` | Driver de colas | `database` |

### Encriptación de credenciales

Las credenciales sensibles de Hacienda (usuario, contraseña, PIN) se almacenan encriptadas en la base de datos usando Defuse PHP Encryption. Si `FE_CRYPTO_KEY` está configurada, el sistema encripta/desencripta automáticamente estos campos.

---

## Multi-Tenancy y Planes

### Estructura multi-tenant

El sistema implementa aislamiento de datos por **tenant** (organización):

- Cada usuario pertenece a un tenant.
- Cada empresa, emisión, recepción y API key pertenecen a un tenant.
- El middleware `TenantScope` filtra automáticamente los datos por el tenant del usuario autenticado.
- El middleware `CheckSubscription` verifica que el tenant tenga una suscripción activa.
- El middleware `CheckPlanLimits` valida que no se excedan los límites del plan contratado.

### Planes disponibles

| Plan | Precio/mes | Empresas | Comprobantes/mes |
|---|---|---|---|
| Básico | $9.99 | 1 | 100 |
| Profesional | $29.99 | 5 | 1,000 |
| Enterprise | $99.99 | Ilimitadas | Ilimitados |

Los planes y sus límites son configurables desde el panel de administración.

### Registro de nuevos tenants

Al registrarse, un usuario crea automáticamente un nuevo tenant con una suscripción al plan seleccionado.

---

## Gestión de Empresas

### Crear una empresa

Ruta web: `https://ociannfe.com/empresas/create`

Al ingresar el **número de cédula** (física o jurídica), el sistema consulta automáticamente el API de Hacienda (`https://api.hacienda.go.cr/fe/ae?identificacion=XXXXXXXXX`) y rellena los campos:

- Nombre de la empresa
- Tipo de identificación (01 = Física, 02 = Jurídica, etc.)
- Número de identificación
- Código de actividad económica (CIIU)
- Situación del contribuyente (estado, moroso, omiso)

### Datos requeridos

- **Credenciales MH**: usuario y contraseña del portal de Comprobantes Electrónicos de Hacienda.
- **Certificado .p12**: llave criptográfica emitida por el BCCR para firmar documentos.
- **PIN de la llave**: contraseña del certificado .p12.
- **Ambiente**: Staging (pruebas) o Producción.

### Sucursales automáticas

Cuando se crean múltiples empresas con la **misma cédula** y las **mismas credenciales de Hacienda**, el sistema asigna automáticamente un número de sucursal incremental (`001`, `002`, `003`...). Este número se refleja en la generación de la **clave** y el **NumeroConsecutivo** de los comprobantes electrónicos.

Estructura del NumeroConsecutivo (20 dígitos):
```
[Sucursal 3d][Terminal 5d][TipoDoc 2d][Consecutivo 10d]
   001         00001         01        0000000001
```

### Verificación de credenciales

Desde la vista de detalle de una empresa (`/empresas/{id}`), el botón **"Verificar credenciales y certificado"** ejecuta dos validaciones:

1. **Certificado .p12 + PIN**: intenta abrir el archivo PKCS#12 con el PIN proporcionado.
2. **Usuario/Contraseña MH**: intenta autenticarse contra el IDP de Hacienda (OAuth2) con las credenciales almacenadas.

El resultado indica claramente qué verificación pasó y cuál falló.

---

## Facturación Electrónica

### Flujo de emisión

```
1. Usuario crea comprobante (web o API)
        │
2. Sistema genera NumeroConsecutivo
        │
3. Sistema construye XML según esquema MH
        │
4. XML se firma digitalmente (XAdES + .p12)
        │
5. Se obtiene token OAuth2 del IDP de Hacienda
        │
6. XML firmado se envía al API de Recepción
        │
7. Hacienda responde con estado inicial
        │
8. Documento queda en cola para seguimiento
        │
9. Job periódico consulta estado final
        │
10. Hacienda responde: Aceptado / Rechazado
```

### Tipos de comprobantes soportados

| Código | Tipo |
|---|---|
| 01 | Factura Electrónica |
| 02 | Nota de Débito Electrónica |
| 03 | Nota de Crédito Electrónica |
| 04 | Tiquete Electrónico |
| 08 | Factura Electrónica de Compra |
| 09 | Factura Electrónica de Exportación |

### Estados de los comprobantes

| Estado | Descripción |
|---|---|
| Pendiente | Creado, pendiente de envío |
| Enviado | Enviado a Hacienda, esperando respuesta |
| Aceptado | Aceptado por Hacienda |
| Rechazado | Rechazado por Hacienda |
| Error | Error en el procesamiento |

### Ambientes de Hacienda

| ID | Nombre | Uso |
|---|---|---|
| 1 | Staging | Pruebas y desarrollo |
| 2 | Producción | Comprobantes reales |

Cada ambiente tiene sus propias URLs para el IDP (autenticación) y el API de recepción de comprobantes.

---

## Panel de Administración

Accesible para usuarios con rol `super_admin` o `admin` en `https://ociannfe.com/admin/dashboard`.

### Funcionalidades

- **Dashboard**: estadísticas generales del sistema (tenants, usuarios, empresas, comprobantes).
- **Tenants**: crear, editar, ver y eliminar organizaciones.
- **Planes**: gestionar los planes de suscripción y sus límites.
- **Usuarios**: administrar todos los usuarios del sistema, asignar roles y tenants.
- **Logs**: visualizar los logs del sistema para diagnóstico.

### Roles del sistema

| Rol | Permisos |
|---|---|
| `super_admin` | Acceso total al sistema. No puede ser eliminado ni desactivado. |
| `admin` | Acceso al panel de administración y gestión de recursos. |
| `user` | Acceso a su dashboard, empresas, comprobantes y API keys. |

### Protección del Super Admin

El usuario `pablo@ociann.com` tiene protecciones especiales:
- No puede ser eliminado por ningún usuario.
- No se le puede revocar el rol `super_admin`.
- No puede ser desactivado.
- Siempre tiene prioridad sobre cualquier otro usuario del sistema.

---

## API REST v1

La API permite integrar la facturación electrónica con cualquier sistema externo. Soporta dos métodos de autenticación.

### Base URL

```
https://ociannfe.com/api/v1
```

### Autenticación por Token (Sanctum)

```bash
# Obtener token
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "usuario@ejemplo.com",
  "password": "contraseña"
}

# Usar token en requests
Authorization: Bearer {token}
```

### Autenticación por API Key

```bash
# Usar API key en el header
X-API-Key: {api_key}
```

Las API Keys se crean desde `https://ociannfe.com/api-keys/create`.

### Endpoints principales

#### Autenticación

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/auth/login` | Obtener token |
| POST | `/auth/logout` | Revocar token |
| GET | `/auth/me` | Datos del usuario autenticado |

#### Empresas

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/empresas` | Listar empresas del tenant |
| POST | `/empresas` | Crear empresa |
| GET | `/empresas/{id}` | Ver empresa |
| PUT | `/empresas/{id}` | Actualizar empresa |
| DELETE | `/empresas/{id}` | Eliminar empresa |

#### Comprobantes

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/comprobantes` | Listar comprobantes (con filtros) |
| POST | `/comprobantes/emitir` | Emitir nuevo comprobante |
| GET | `/comprobantes/{clave}` | Ver comprobante por clave |
| GET | `/comprobantes/{clave}/estado` | Consultar estado en Hacienda |
| GET | `/comprobantes/{clave}/xml` | Descargar XML |

#### Recepciones

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/recepciones` | Listar recepciones |
| GET | `/recepciones/{clave}` | Ver recepción |

#### Cola de procesamiento

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/cola` | Estado de la cola |
| POST | `/cola/procesar` | Procesar cola manualmente |

#### Webhook

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/webhook/hacienda` | Recibir notificaciones de Hacienda |

### Endpoints con API Key

Los mismos endpoints de comprobantes y empresas están disponibles bajo el prefijo `/key/`:

```
GET  /api/v1/key/empresas
POST /api/v1/key/comprobantes/emitir
GET  /api/v1/key/comprobantes/{clave}
GET  /api/v1/key/comprobantes/{clave}/estado
GET  /api/v1/key/comprobantes/{clave}/xml
```

### Ejemplo: emitir una factura electrónica

```bash
curl -X POST https://ociannfe.com/api/v1/comprobantes/emitir \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "id_empresa": 1,
    "TipoDoc": "01",
    "Receptor": {
      "Nombre": "Cliente Ejemplo S.A.",
      "Identificacion": {
        "Tipo": "02",
        "Numero": "3101234567"
      },
      "CorreoElectronico": "cliente@ejemplo.com"
    },
    "CondicionVenta": "01",
    "MedioPago": ["01"],
    "DetalleServicio": {
      "LineaDetalle": [
        {
          "NumeroLinea": 1,
          "CodigoCABYS": "4321100000100",
          "Cantidad": 1,
          "UnidadMedida": "Unid",
          "Detalle": "Servicio de consultoría",
          "PrecioUnitario": 100000,
          "MontoTotal": 100000,
          "SubTotal": 100000,
          "Impuesto": {
            "Codigo": "01",
            "CodigoTarifa": "08",
            "Tarifa": 13,
            "Monto": 13000
          },
          "MontoTotalLinea": 113000
        }
      ]
    },
    "ResumenFactura": {
      "TotalVenta": 100000,
      "TotalImpuesto": 13000,
      "TotalComprobante": 113000
    }
  }'
```

---

## Autenticación y Autorización

### Web (Blade)

- Login en `https://ociannfe.com/login`
- Registro en `https://ociannfe.com/register` (crea tenant + suscripción)
- Recuperación de contraseña vía email
- Sesiones gestionadas por Laravel

### API

Dos mecanismos disponibles:

1. **Sanctum Tokens**: ideales para aplicaciones propias. Se obtienen con `POST /api/v1/auth/login`.
2. **API Keys**: ideales para integraciones de terceros. Se crean desde la interfaz web y se envían en el header `X-API-Key`.

### Middleware personalizado

| Middleware | Función |
|---|---|
| `TenantScope` | Inyecta el `tenant_id` del usuario autenticado en cada request |
| `CheckSubscription` | Verifica que el tenant tenga una suscripción activa o en trial |
| `CheckPlanLimits` | Valida límites del plan (máx. empresas, comprobantes/mes) |
| `ApiKeyAuth` | Autentica requests con el header `X-API-Key` |

---

## Cola de Procesamiento

El sistema utiliza colas de Laravel para procesar comprobantes de forma asíncrona.

### Jobs del sistema

| Job | Función | Reintentos | Backoff |
|---|---|---|---|
| `ProcessColaJob` | Procesa la cola general de comprobantes | 1 | — |
| `SendComprobanteJob` | Envía un comprobante a Hacienda | 3 | 60s, 300s, 900s |
| `CheckComprobanteStatusJob` | Consulta estado de un comprobante | 3 | 30s, 120s, 600s |

### Ejecución automática

El scheduler de Laravel ejecuta `ProcessColaJob` cada 5 minutos. Configurar el cron del sistema:

```bash
* * * * * cd /var/www/fe_ociannpayments && php artisan schedule:run >> /dev/null 2>&1
```

### Procesamiento manual

Desde la API: `POST /api/v1/cola/procesar`

Desde la terminal:

```bash
php artisan queue:work --timeout=120
```

---

## Certificados .p12 y OpenSSL

### Problema con OpenSSL 3.x

Los certificados `.p12` emitidos por el BCCR de Costa Rica utilizan el algoritmo de cifrado **RC2-40-CBC**, que fue desactivado por defecto en OpenSSL 3.0+. Esto provoca que `openssl_pkcs12_read()` falle con el error:

```
error:0308010C:digital envelope routines::unsupported
```

### Solución implementada

El sistema incluye un archivo de configuración de OpenSSL en `config/openssl-legacy.cnf` que activa el proveedor legacy:

```ini
openssl_conf = openssl_init

[openssl_init]
providers = provider_sect

[provider_sect]
default = default_sect
legacy = legacy_sect

[default_sect]
activate = 1

[legacy_sect]
activate = 1
```

### Configuración del servidor

La variable `OPENSSL_CONF` debe estar disponible para el proceso de Apache **antes** de que arranque:

1. **`/etc/apache2/envvars`**:
   ```bash
   export OPENSSL_CONF=/var/www/fe_ociannpayments/config/openssl-legacy.cnf
   ```

2. **`/etc/environment`** (para CLI y cron):
   ```
   OPENSSL_CONF=/var/www/fe_ociannpayments/config/openssl-legacy.cnf
   ```

3. Reiniciar Apache:
   ```bash
   systemctl restart apache2
   ```

### Fallback automático

Si la variable de entorno no está configurada, el `FacturacionService` detecta el error de "unsupported algorithm" y recurre automáticamente al binario `openssl` del sistema con la configuración legacy, escribiendo el .p12 en un archivo temporal para validarlo.

---

## Estructura del Proyecto

```
fe_ociannpayments/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # Login, Register, Password Reset
│   │   │   ├── User/           # Dashboard, Empresas, Comprobantes,
│   │   │   │                   # Recepciones, ApiKeys, Profile
│   │   │   ├── Admin/          # Dashboard, Tenants, Planes,
│   │   │   │                   # Usuarios, Logs
│   │   │   └── Api/V1/         # Auth, Empresas, Comprobantes,
│   │   │                       # Recepciones, Cola, Webhook
│   │   ├── Middleware/         # TenantScope, CheckSubscription,
│   │   │                       # CheckPlanLimits, ApiKeyAuth
│   │   ├── Requests/           # Form Requests de validación
│   │   └── Resources/          # API Resources (transformadores)
│   ├── Models/                 # Eloquent Models
│   │   ├── User.php
│   │   ├── Tenant.php
│   │   ├── Empresa.php         # fe_empresas
│   │   ├── Emision.php         # fe_emisiones
│   │   ├── EmisionLinea.php    # fe_emision_lineas
│   │   ├── Recepcion.php       # fe_recepciones
│   │   ├── Cola.php            # fe_cola
│   │   ├── Plan.php
│   │   ├── Subscription.php
│   │   ├── ApiKey.php
│   │   ├── Ambiente.php        # fe_ambientes
│   │   └── TokenHacienda.php   # fe_tokens
│   ├── Services/
│   │   ├── FacturacionService.php   # Lógica principal de FE
│   │   └── ColaService.php          # Gestión de cola
│   └── Jobs/
│       ├── ProcessColaJob.php
│       ├── SendComprobanteJob.php
│       └── CheckComprobanteStatusJob.php
├── config/
│   ├── facturacion.php          # Config de facturación electrónica
│   └── openssl-legacy.cnf      # Config OpenSSL para .p12 legacy
├── database/
│   ├── migrations/              # 16 migraciones
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       ├── PlansSeeder.php
│       └── AdminUserSeeder.php
├── resources/views/
│   ├── layouts/                 # app.blade.php, guest.blade.php
│   ├── auth/                    # login, register, forgot/reset password
│   ├── user/                    # dashboard, empresas, comprobantes,
│   │                            # recepciones, api-keys, profile
│   └── admin/                   # dashboard, tenants, planes,
│                                # usuarios, logs
├── routes/
│   ├── web.php                  # Rutas web
│   ├── api.php                  # Rutas API v1
│   └── console.php              # Scheduler (ProcessColaJob c/5min)
├── docs/
│   └── OPENSSL-P12-LEGACY.md   # Documentación OpenSSL legacy
└── public/                      # Document root de Apache
```

### Tablas de la base de datos

| Tabla | Descripción |
|---|---|
| `users` | Usuarios del sistema |
| `tenants` | Organizaciones (multi-tenant) |
| `plans` | Planes de suscripción |
| `subscriptions` | Suscripciones activas |
| `api_keys` | Llaves de API |
| `fe_ambientes` | Ambientes de Hacienda (Staging/Producción) |
| `fe_empresas` | Empresas registradas con credenciales MH |
| `fe_emisiones` | Comprobantes emitidos |
| `fe_emision_lineas` | Líneas de detalle de cada emisión |
| `fe_recepciones` | Comprobantes recibidos |
| `fe_cola` | Cola de procesamiento |
| `fe_tokens` | Tokens OAuth2 de Hacienda (caché) |
| `fe_monolog` | Logs de la librería de facturación |
| `fe_ratelimiting` | Control de rate limiting hacia Hacienda |
| `fe_settings` | Configuraciones adicionales |

---

## Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Crear usuario super admin
php artisan tinker --execute="
\$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@ejemplo.com',
    'password' => bcrypt('contraseña'),
    'tenant_id' => 1,
]);
\$user->assignRole('super_admin');
"

# Procesar cola manualmente
php artisan queue:work --timeout=120

# Ver logs en tiempo real
php artisan pail

# Limpiar caché
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Verificar estado de la aplicación
php artisan about
```

---

## Licencia

Software propietario de Ociann. Todos los derechos reservados.
