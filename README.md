# ARCANA — Moon Beauty Secret
### Plataforma Web Integral de Gestión de Citas, Estética y Cobros Dinámicos

Sistema web completo para salones de belleza, centros de estética y spas. Automatiza la agenda de citas en tiempo real, el cálculo y cobro de señas de reserva (30%), la gestión de especialistas y servicios, métricas comerciales y el acceso seguro con control de roles (RBAC).

---

## 📋 Tabla de Contenidos
1. [Requisitos del Sistema](#-requisitos-del-sistema)
2. [Instalación en Entorno Local (WampServer / XAMPP / PHP CLI)](#-instalación-en-entorno-local)
3. [Instalación y Despliegue en CDMON](#-instalación-y-despliegue-en-cdmon)
4. [Estructura de la Base de Datos](#-estructura-de-la-base-de-datos)
5. [Cuentas de Acceso al Sistema](#-cuentas-de-acceso-al-sistema)
6. [Variables de Entorno (.env)](#-variables-de-entorno-env)
7. [Estructura del Proyecto](#-estructura-del-proyecto)
8. [Seguridad y Buenas Prácticas](#-seguridad-y-buenas-prácticas)

---

## ⚙️ Requisitos del Sistema

- **PHP:** Versión 8.1 o 8.2 (64-bit recomendada).
  - *Extensiones requeridas:* `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `fileinfo`.
- **Servidor Web:** Apache 2.4+ (con módulo `mod_rewrite` habilitado) o Nginx.
- **Base de Datos:** MySQL 8.0+ o MariaDB 10.4+.
- **Gestor local recomendado:** WampServer 3.3+, XAMPP o Laragon.

---

## 🚀 Instalación en Entorno Local

### 1. Clonar o descargar el repositorio
Coloca el proyecto en la carpeta pública de tu servidor local (por ejemplo `C:\wamp64\www\Arcana-Estetica`):
```bash
git clone https://github.com/TU-USUARIO/Arcana-Estetica.git
cd Arcana-Estetica
```

### 2. Configurar el archivo `.env`
Copia el archivo de plantilla `.env.example` y nómbralo como `.env`:
```bash
cp .env.example .env
```
Abre el archivo `.env` y configura los datos de tu conexión local a MySQL (por ejemplo `DB_NAME=arcanaesteticadb`, `DB_USER=root`, `DB_PASS=""`).

### 3. Crear e Importar la Base de Datos
1. Abre tu gestor de base de datos (**phpMyAdmin** en `http://localhost/phpmyadmin/`).
2. Crea una base de datos llamada `arcanaesteticadb` con cotejamiento `utf8mb4_unicode_ci`.
3. Ve a la pestaña **Importar**.
4. Selecciona el archivo oficial del proyecto:
   📁 **`database/arcanaestetica_db.sql`**
5. Haz clic en **Importar / Ejecutar**.

### 4. Permisos de Archivos
Asegúrate de que la carpeta de subida de imágenes tenga permisos de lectura y escritura:
- `assets/images/uploads/`

### 5. Iniciar la Aplicación
- **Con WampServer / XAMPP:** Abre tu navegador y accede a:
  `http://localhost/Arcana-Estetica/`
- **Con el servidor integrado de PHP:**
  ```bash
  php -S localhost:8000
  ```
  Y abre `http://localhost:8000` en tu navegador.

---

## 🌐 Instalación y Despliegue en CDMON

Para publicar el proyecto en el hosting de **CDMON**:

1. **Configurar PHP en CDMON:**  
   En el panel de control de tu dominio, selecciona **PHP 8.2** y verifica que estén activas las extensiones `pdo_mysql`, `curl`, `mbstring`, `openssl` y `fileinfo`.

2. **Crear la Base de Datos:**  
   - En la sección **Bases de datos MySQL** de CDMON, crea una nueva base de datos y un usuario con privilegios completos.
   - Entra a **phpMyAdmin** desde el panel de CDMON.
   - Selecciona tu base de datos e importa el archivo **`database/arcanaestetica_db.sql`**.

3. **Subir los Archivos:**  
   - Mediante Git o el Administrador de Archivos, sube los archivos del proyecto a la carpeta raíz web (`/public_html` o `/web`).

4. **Crear el archivo `.env` en CDMON:**  
   En el Administrador de Archivos de CDMON, crea un archivo llamado `.env` en la raíz con tus credenciales de CDMON:
   ```env
   APP_ENV=production
   APP_DEBUG=false

   DB_DRIVER=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=TU_BASE_DE_DATOS_EN_CDMON
   DB_USER=TU_USUARIO_EN_CDMON
   DB_PASS=TU_CONTRASENA_EN_CDMON
   ```
   *(Copia las credenciales de correo, Mercado Pago y Google desde `.env.example` o `README_CREDENCIALES.md`).*

5. **Activar Certificado SSL:**  
   Habilita el certificado SSL Let's Encrypt gratuito desde el panel de CDMON para que el sitio funcione bajo `https://`.

---

## 🗄️ Estructura de la Base de Datos

El sistema incluye un **único archivo de base de datos oficial y funcional**:
- 📁 **`database/arcanaestetica_db.sql`**

Contiene las 18 tablas normalizadas en 3FN:
- `usuario`, `roles`, `verification_tokens`
- `servicio`, `categoria`, `categoria_servicio`, `precio_servicio`
- `empleado`, `especialidad`, `empleado_especialidad`, `servicio_empleado`
- `citas`, `cita_servicio`, `imagen_cita`, `espacio`, `bloqueo_dia`
- `payment_accounts`, `cambios_dashboard`, `resenas`

---

## 👥 Cuentas de Acceso al Sistema

El acceso al sistema es **directo con correo y contraseña** (sin doble verificación):

| Rol | Usuario / Correo | Contraseña | Destino al Iniciar Sesión |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `anabelaacosta2026@gmail.com` | `Anabela2026*` | [dashboard.php](dashboard.php) (Control total y auditoría) |
| **Administrador** | `fernandocabraldev@gmail.com` | `Admin1234*` | [dashboard.php](dashboard.php) (Agenda y servicios) |
| **Cliente** | `julieta.romero@gmail.com` | `Cliente2026*` | [index.php](index.php) (Reservas y turnos personales) |

*(Para consultar el listado completo de los 28 usuarios registrados, revisa [README_CREDENCIALES.md](README_CREDENCIALES.md)).*

---

## 📁 Estructura del Proyecto

```text
Arcana-Estetica/
├── api/                             # Endpoints REST (JSON)
│   ├── agenda.php                   # Disponibilidad y cálculo de turnos
│   ├── auth.php                     # Login, registro y validaciones
│   ├── backup.php                   # Respaldos de base de datos
│   ├── empleados.php                # CRUD de profesionales
│   ├── payment_accounts.php         # Enrutamiento dinámico de cobros
│   ├── servicios.php                # CRUD de catálogo de tratamientos
│   ├── turnos.php                   # Gestión y cambios de estado de citas
│   └── usuarios.php                 # Administración de usuarios y roles
├── assets/                          # Imágenes, avatares e iconos
│   └── images/uploads/              # Subidas dinámicas del panel
├── config/                          # Configuración centralizada
│   ├── config.php                   # Carga de variables de entorno y constantes
│   ├── database.php                 # Singleton PDO MySQL con Prepared Statements
│   ├── JsonDB.php                   # Capa de abstracción de datos
│   ├── mail.php                     # Motor de envíos de correo SMTP (PHPMailer)
│   ├── mercadopago.php              # Servicio de integración Checkout Pro
│   └── session.php                  # Manejo de sesiones seguras y control RBAC
├── css/                             # Estilos modulares organizados por variables
├── database/
│   └── arcanaestetica_db.sql        # Volcado SQL oficial y completo
├── includes/                        # Componentes PHP modulares
│   ├── auth_check.php               # Middleware de protección por rol
│   ├── header.php                   # Cabecera HTML y metadatos
│   ├── navbar.php                   # Barra de navegación
│   ├── footer.php                   # Pie de página institucional
│   └── dashboard/                   # Pestañas y modales del panel administrativo
├── js/                              # Lógica frontend interactiva (Vanilla JS)
├── .env.example                     # Plantilla pública de variables de entorno
├── .gitignore                       # Exclusiones de Git y seguridad
├── .htaccess                        # Directivas Apache (seguridad y URL rewrite)
├── index.php                        # Portal público y catálogo
├── agenda.php                       # Interfaz de reserva interactiva de turnos
├── login.php                        # Inicio de sesión directo
├── register.php                     # Registro de nuevos clientes
├── dashboard.php                    # Panel general de administración
├── README.md                        # Guía de instalación y configuración
└── README_CREDENCIALES.md           # Credenciales y datos del sistema
```

---

## 🛡️ Seguridad y Buenas Prácticas

1. **Aislamiento de Secretos:** El archivo `.env` nunca se versiona en Git. Las reglas en `.htaccess` bloquean el acceso HTTP directo a archivos `.env`, `.sql`, `.log` y `.json`.
2. **Protección contra Inyección SQL:** Todas las consultas a la base de datos utilizan *Prepared Statements* de PDO con parámetros ligados.
3. **Cifrado de Contraseñas:** Algoritmo nativo `PASSWORD_BCRYPT` (`$2y$10$...`) para todas las cuentas.
4. **Protección contra Fuerza Bruta:** Limitador de tasa de peticiones (*Rate Limiting*) en `login.php` que bloquea intentos repetidos.
5. **Cookies de Sesión Blindadas:** Banderas `HttpOnly`, `SameSite = Lax` y `Secure` habilitadas en `config/session.php`.
6. **Subida Segura de Archivos:** Validación de tipo MIME real en el servidor mediante `finfo_file` en `api/upload.php` antes de aceptar imágenes.
