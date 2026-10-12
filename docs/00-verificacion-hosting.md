# 0. Verificación del hosting (antes de instalar nada)

Objetivo: saber **dónde está alojado** `sudisinstitute.org`, si ofrece **staging**,
cómo están las **copias de seguridad** y quién tiene **permisos**. Hasta completar
esta lista **no se instala ni se cambia nada** en el WordPress publicado.

> Estado: **pendiente**. Desde el entorno de desarrollo de Claude no fue posible
> consultar DNS ni el sitio (la red del entorno no permite resolver dominios
> externos). Los pasos de abajo los puedes hacer tú en unos 15 minutos, todos son
> de **solo lectura**. Alternativa: permitir el dominio `sudisinstitute.org` en la
> configuración de red del entorno para que Claude haga una inspección pasiva
> (cabeceras HTTP y DNS públicos).

## 0.1 Identificar el proveedor (solo lectura)

| # | Dónde | Qué anotar |
|---|-------|------------|
| 1 | Busca en tu correo: "hosting", "cPanel", "factura", "renovación", "welcome", "servidor" | Nombre del proveedor y plan |
| 2 | <https://lookup.icann.org> → `sudisinstitute.org` | Registrador del dominio, fecha de vencimiento, *nameservers* |
| 3 | <https://www.wpbeginner.com/wp-theme-detector/> o <https://hostingchecker.com> | Proveedor de hosting probable, tema |
| 4 | WordPress → *Herramientas → Salud del sitio → Información* → **Copiar la información del sitio al portapapeles** | Versión de WordPress y PHP, servidor, tema, plugins, límites de memoria |
| 5 | WordPress → ¿la URL de administración es `wordpress.com/...`? | Si es **WordPress.com**, ver nota 0.4 |

Comparte el texto del punto 4 (no contiene contraseñas) para que lo analice.

## 0.2 Copias de seguridad

- [ ] ¿El hosting hace copias automáticas? ¿Cada cuánto? ¿Cuántos días conserva?
- [ ] ¿Se pueden **restaurar** desde el panel con un clic?
- [ ] ¿Hay algún plugin de copias en WordPress (UpdraftPlus, Jetpack/VaultPress, BlogVault, All-in-One WP Migration)?
- [ ] Descargar **una copia completa** (archivos + base de datos) y guardarla fuera del servidor **antes** de cualquier cambio.

## 0.3 Accesos y permisos

- [ ] ¿Quién tiene la cuenta del hosting? (usuario, correo, 2FA activado)
- [ ] ¿Quién tiene la cuenta del dominio/DNS? (puede ser otro proveedor, p. ej. Cloudflare o GoDaddy)
- [ ] Usuarios administradores de WordPress: revisar que todos sean conocidos.
- [ ] ¿Hay acceso SFTP/SSH y a phpMyAdmin?

## 0.4 ¿El hosting permite staging?

| Situación | Cómo hacemos el staging |
|-----------|-------------------------|
| Hosting gestionado con staging (SiteGround, WP Engine, Kinsta, Cloudways, Hostinger, Bluehost, etc.) | Botón "Crear staging" en el panel: clona el sitio en `staging.…` sin tocar producción |
| cPanel sin staging | Clonar a un subdominio con *Softaculous → Clonar* o copia manual |
| Sin herramientas en el hosting | Restaurar la copia descargada en un **entorno local** (LocalWP) y trabajar ahí |
| **WordPress.com** | Instalar plugins (LearnDash) requiere plan **Business/Creator** o superior; ese plan incluye sitio de staging |

## 0.5 Resultado esperado de la Fase 0

Un registro en este repositorio con: proveedor, plan, versión de PHP/WordPress,
política de copias, existencia de staging, responsables de accesos. Con eso se
decide cómo crear el staging (Fase 1) y se pide tu autorización.
