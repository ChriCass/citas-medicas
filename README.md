# Conexión de Laravel a Microsoft SQL Server (XAMPP / Windows)

Guía breve para conectar un proyecto **Laravel** a **Microsoft SQL Server** en Windows usando XAMPP.

---

## 1) Video a seguir (obligatorio)
Sigue **este video en concreto** para la configuración general:
- 🎥 https://www.youtube.com/watch?v=ZvVFJG_TftE

---

## 2) Drivers obligatorios para PHP (XAMPP)
Es **forzoso** descargar e instalar los drivers de Microsoft para PHP (SQLSRV/PDO_SQLSRV) y copiarlos en el folder `ext` de tu PHP en XAMPP.

- 📥 Descarga oficial: https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server?view=sql-server-ver17

**Pasos:**  
1. Descarga los DLL que coincidan con tu versión de PHP (por ejemplo, `php_pdo_sqlsrv_82_ts_x64.dll` y `php_sqlsrv_82_ts_x64.dll` si usas PHP 8.2 TS x64).  
2. Copia los DLL en: `C:\xampp\php\ext\`  
3. (Opcional pero recomendado) Verifica/habilita en tu `php.ini`:
   ```ini
   
   ; Ajusta el nombre de archivo a tu versión (81/82/83) y TS/NTS según corresponda:
   extension=php_pdo_sqlsrv_82_ts_x64
   extension=php_sqlsrv_82_ts_x64
   ```

 
---

## 3) Configurar el archivo de entorno
1. **Usar la plantilla**: toma el archivo **`.env.sqlsrv`**, **quítale la extensión adicional** y déjalo como **`.env`**.  
2. **Modificar el host**: cambia **solo** la línea `DB_HOST` por tu nombre local de servidor/instancia. Ejemplo:
   ```env
   DB_HOST=LAPTOP-7B6GIHB6\SQLEXPRESS   # reemplázalo por tu nombre local
   ```
3. **Deja todo lo demás tal como está** en el `.env`.

> Si usas instancia nombrada (p. ej. `SQLEXPRESS`), normalmente deja `DB_PORT` vacío. Si usas puerto fijo **1433**, puedes optar por `DB_HOST=127.0.0.1` y `DB_PORT=1433`.

---

## 4) Ejecutar comandos (en este orden)
Después de completar la configuración anterior, corre los siguientes comandos desde la raíz del proyecto:

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

- `key:generate`: crea la clave de aplicación en `.env`.
- `migrate`: crea/actualiza las tablas base.
- `serve`: levanta el servidor de desarrollo en `http://127.0.0.1:8000`.

---

## 5) Verificación rápida / Solución de problemas
- **No encuentra el driver (`could not find driver`)**: revisa que copiaste **los DLL correctos** en `ext`, que coinciden con tu versión de PHP (8.1/8.2/8.3), **x64**, y **TS/NTS**; y que están habilitados en `php.ini`.
- **Mensajes de “Unable to initialize module / Module API mismatch”**: estás usando DLL de **otra versión** de PHP. Descarga los que correspondan **exactamente** a tu versión.
- **La instancia no conecta**: habilita **TCP/IP** en *SQL Server Configuration Manager*; si usas `SQLEXPRESS`, prueba `EQUIPO\SQLEXPRESS` con `DB_PORT` vacío o fija `1433`.

---

### Notas
- Si utilizas **Windows Authentication** (sin usuario/clave en `.env`), la cuenta de Windows bajo la que corre PHP/Apache/IIS debe tener **login y permisos** sobre la base de datos en SQL Server.
- Si utilizas usuario SQL (p. ej. `sa`), mantén `DB_USERNAME` y `DB_PASSWORD` configurados en `.env`.
