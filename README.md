# Wallet Squad1 - API Billetera Virtual

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

API RESTful para la gestión de una billetera virtual, desarrollada con Laravel y PHP. Permite a los usuarios realizar transacciones, depósitos, inversiones simuladas (plazo fijo), y administrar sus cuentas. Incluye un sistema de roles (Usuario/Administrador) con permisos diferenciados.

Este repositorio contiene la base del proyecto. Los modelos, controladores y la lógica de negocio se desarrollarán a partir de este punto de partida.

## 📋 Requisitos Previos

Asegúrate de tener instalado lo siguiente en tu entorno de desarrollo:

- **PHP**: ^8.2
- **Composer**: Última versión estable
- **Base de datos**: MySQL (o MariaDB) / PostgreSQL / SQLite
- **Node.js y NPM**: (Opcional, para compilar assets, aunque no es crítico para la API)
- **Git**

## 🚀 Instalación y Configuración

Sigue estos pasos para poner el proyecto en funcionamiento en tu máquina local.

### 1. Clonar el repositorio

```bash
git clone https://github.com/hectordsol/wallet-squad1-php.git
cd wallet-squad1-php
```

### 2. Instalar dependencias de PHP

Instala las dependencias del proyecto usando Composer. Esto incluye el framework Laravel y los paquetes necesarios.

```bash
composer install
```

### 3. Configurar variables de entorno (.env)

```bash
php artisan key:generate
```

#### Configuración de la base de datos:

Abre el archivo `.env` y ajusta los parámetros de conexión a tu base de datos. Por ejemplo, para MySQL:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallet_principal
DB_USERNAME=admin o tu_usuario
DB_PASSWORD=tu_contraseña

```

### 4. Ejecutar migraciones y seeders

Las migraciones cuando sean creadas armarán la estructura de tablas en tu base de datos. Los seeders (si están definidos) poblarán la base con datos de prueba, como un usuario administrador por defecto.

```bash
php artisan migrate --seed
```

### 5. Iniciar el servidor de desarrollo

Para levantar la API y comenzar a probarla, ejecuta el siguiente comando:

```bash
php artisan serve
```

La API estará disponible en `http://127.0.0.1:8000.`

## 📦 Dependencias Principales

- Laravel Framework: v11.x. Proporciona la estructura base, el ORM Eloquent, el sistema de rutas y la capa de seguridad.

- JWT Auth: ^2.0. Maneja la autenticación basada en tokens JWT para las peticiones a la API.

## 📦 Base de datos

La base de datos por el momento solo tiene el modelo de usuarios que viene con Laravel.

## 🔐 Registro de Usuarios

La API incluye un endpoint para el registro de nuevos usuarios en la billetera virtual.

### Endpoint: POST /api/v1/auth/register

Cuerpo de la Petición (JSON):
{
"nombre": "ale",
"email": "usuario@usuario.com",
"password": "1234",
"password_confirmation": "1234",
"edad": 18
}

Campos:
nombre: string, obligatorio. Nombre del usuario.
email: string, obligatorio. Correo electrónico único.
password: string, obligatorio. Contraseña del usuario.
password_confirmation: string, obligatorio. Confirmación de la contraseña (debe coincidir con password).
edad: integer, obligatorio. Edad del usuario.
rol: string, opcional. Rol del usuario. Por defecto se asigna "usuario".

### Respuesta Exitosa (201 Created):

    {
    "id": 1,
    "nombre": "ale",
    "email": "usuario@usuario.com",
    "edad": 18,
    "rol": "usuario",
    "created_at": "2026-01-01T00:00:00.000000Z"
    }

Respuestas de Error (422 Unprocessable Entity):
El endpoint retorna un error 422 en los siguientes casos:

Campos faltantes: Cuando algún campo obligatorio no es enviado.

Email duplicado: Si el correo electrónico ya está registrado en el sistema.

Validación de contraseña: Cuando password y password_confirmation no coinciden.

Ejemplo de Respuesta de Error:
{
"message": "El email ya ha sido registrado.",
"errors": {
"email": ["El email ya ha sido registrado."]
}
}

Notas:

Por defecto, todos los usuarios nuevos se registran con el rol "usuario".

El rol "administrador" solo puede ser asignado manualmente desde la base de datos o mediante un proceso específico de administración.

## 🧪 Pruebas (Tests)

El proyecto de momento no incluye pruebas básica. Para ejecutarlas, utiliza el siguiente comando:

```bash
php artisan test
```
## WAL-005 — Consultar el perfil propio

Permite obtener los datos personales del usuario autenticado.

### Endpoint

```http
GET /api/v1/profile
```

### Autenticación

Requiere un JWT válido mediante Bearer Token.

```http
Authorization: Bearer {token}
```

El usuario se obtiene directamente desde el token JWT, por lo que no es necesario enviar `user_id`. De esta forma, cada usuario solamente puede consultar su propio perfil.

### Respuesta exitosa

**HTTP 200 OK**

```json
{
    "id": 2,
    "name": "Julio",
    "email": "julio2@test.com"
}
```

La respuesta no incluye la contraseña ni otros datos privados del usuario.

### Sin token o token inválido

**HTTP 401 Unauthorized**

La API rechaza el acceso cuando no se proporciona un JWT válido.

---

## WAL-006 — Consultar la cuenta y el saldo propios

Permite obtener el CBU y saldo de la cuenta perteneciente al usuario autenticado.

### Endpoint

```http
GET /api/v1/account
```

### Autenticación

Requiere un JWT válido mediante Bearer Token.

```http
Authorization: Bearer {token}
```

La cuenta se obtiene a través del usuario identificado por el JWT. No es necesario enviar `user_id` ni `account_id`, evitando que un usuario pueda seleccionar o consultar la cuenta de otro usuario.

### Respuesta exitosa

**HTTP 200 OK**

```json
{
    "cbu": "0000009341854172124306",
    "balance": "0.00"
}
```

El campo `balance` se devuelve siempre con dos decimales.

### Cuenta inexistente

**HTTP 404 Not Found**

```json
{
    "message": "Cuenta no encontrada"
}
```

### Sin token o token inválido

**HTTP 401 Unauthorized**

La API rechaza el acceso cuando no se proporciona un JWT válido.

---

## Ejemplo de uso

Primero se inicia sesión para obtener el JWT:

```http
POST /api/v1/auth/login
```

```json
{
    "email": "julio2@test.com",
    "password": "12345678"
}
```

Una vez obtenido el `access_token`, se utiliza como Bearer Token para consultar los endpoints privados:

```http
GET /api/v1/profile
Authorization: Bearer {access_token}
```

```http
GET /api/v1/account
Authorization: Bearer {access_token}
```

## Implementación

Para estos endpoints no se utilizan Form Requests ni DTOs porque las operaciones son consultas `GET` que no reciben datos de entrada.

Se utilizan API Resources para controlar los campos expuestos por la API:

- `ProfileResource`: transforma los datos del usuario y expone `id`, `name` y `email`.
- `AccountResource`: transforma los datos de la cuenta y expone `cbu` y `balance`.

Ambos endpoints se encuentran protegidos por el middleware:

```php
auth:api
```

El usuario autenticado se obtiene mediante el guard JWT:

```php
Auth::guard('api')->user();
```

Para consultar la cuenta se utiliza la relación entre el usuario autenticado y su cuenta, sin aceptar identificadores enviados por el cliente.

### Tests WAL-005 y WAL-006

Los endpoints de perfil y cuenta cuentan con tests de integración.

```bash
php artisan test


## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la Licencia MIT.

## 👥 Integrantes del Squad 1 Laravel

- **Héctor Darío Sol** - [@hectordsol](https://github.com/hectordsol)
- **Alejandro Ramirez** - [@ramirezaed](https://github.com/ramirezaed)
- **Julio Andres** - [@JulioAndres2021](https://github.com/JulioAndres2021)
- **David Mach** - [@dav-mach](https://github.com/dav-mach)
- **Manuel Coria** - [@coriawork](https://github.com/coriawork)

## 👥 Mentor

- **Jean Paul Ferreira** - [@JePaFe](https://github.com/JePaFe)
