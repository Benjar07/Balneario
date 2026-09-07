# Sistema de Administración de Reservas de Carpas

Aplicación web educativa desarrollada con:

- PHP 8+
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- XAMPP

## 1. Instalación en XAMPP

1. Copiá la carpeta `balneario_reservas` dentro de:

```text
C:\xampp\htdocs\
```

2. Abrí el panel de XAMPP y encendé:
   - Apache
   - MySQL

3. Entrá a phpMyAdmin:

```text
http://localhost/phpmyadmin
```

4. Importá el archivo:

```text
schema.sql
```

5. Verificá `conexion.php`:

```php
$host = "localhost";
$usuario = "root";
$password = "";
$bd = "balneario_reservas";
```

Si tu MySQL tiene otra contraseña, reemplazala allí.

## 2. Acceso

Abrí:

```text
http://localhost/balneario_reservas/login.php
```

Usuario inicial:

```text
admin
```

Contraseña:

```text
admin123
```

En una instalación real se recomienda cambiar inmediatamente estas credenciales.

## 3. Módulos

### Dashboard
Muestra:
- cantidad total de carpas
- carpas libres para hoy
- porcentaje de ocupación
- ingresos estimados del mes
- últimas reservas

### Carpas
CRUD completo:
- crear
- listar
- editar
- eliminar
- numeración
- sector
- fila / columna
- posición X/Y
- activar / desactivar

### Reservas
Permite:
- asociar cliente y carpa
- seleccionar fecha inicial y final
- seleccionar modalidad diaria, quincenal, mensual o temporada
- calcular el precio automáticamente
- estado pendiente, confirmada, cancelada o finalizada
- evitar superposición de reservas activas
- consultar historial

### Clientes
CRUD de datos básicos:
- nombre
- DNI
- teléfono
- email

### Mapa
El mapa consulta `map_data.php` mediante `fetch()` y dibuja las carpas en pantalla.

Colores:
- verde = disponible
- amarillo = reservado / pendiente de pago
- rojo = ocupado

Al hacer clic se abre un modal con datos del cliente, fechas y costo.

### Configurador
Permite arrastrar las carpas para adaptar el plano a cualquier balneario. Las posiciones se guardan en la base de datos.

## 4. Estructura

```text
balneario_reservas/
├── index.php
├── login.php
├── logout.php
├── conexion.php
├── auth.php
├── header.php
├── footer.php
├── carpas.php
├── reservas.php
├── clientes.php
├── mapa.php
├── mapa.js
├── map_data.php
├── configurador.php
├── estilos.css
├── schema.sql
└── README.md
```

## 5. Idea para la defensa

La aplicación está separada por responsabilidades:

- `conexion.php`: conexión con MySQL.
- `auth.php`: protección de páginas.
- `login.php`: autenticación.
- `index.php`: dashboard.
- `carpas.php`: CRUD de carpas.
- `clientes.php`: CRUD de clientes.
- `reservas.php`: lógica de reservas y cálculo de costos.
- `mapa.php`: interfaz visual.
- `map_data.php`: entrega datos en JSON.
- `mapa.js`: interacción dinámica del plano.
- `configurador.php`: personalización de las posiciones.
- `schema.sql`: estructura relacional de la base.

El sistema utiliza consultas preparadas (`prepare` / `bind_param`) para reducir riesgos de inyección SQL y `password_verify()` para validar contraseñas almacenadas de forma segura.
