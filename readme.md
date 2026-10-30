# 📸 Àlbum de Cromos – Aplicació LAMP

Aplicació web **LAMP** (Linux · Apache · MariaDB · PHP) per a la gestió d’un **àlbum de cromos digital** orientat a entorns educatius (ABP).

Cada **grup** disposa del seu àlbum privat amb **slots fixes (tasques)** on pot pujar captures.  
El **professorat** té accés de **lectura** a tots els àlbums.

---

## ✨ Funcionalitats principals

- Autenticació amb rols (`group` / `profe`)
- Àlbum per **slots fixos** (1 cromo per tasca)
- Pujada, reemplaç i eliminació de fitxers
- Vista prèvia d’imatges
- Accés segur als fitxers (sense accés directe a `/uploads`)
- Control d’accés per rol
- Preparat per desplegar en qualsevol Debian

---

## 🧱 Requisits

- Debian 12 / 13
- Apache 2
- PHP 8.2+
- MariaDB 10.11+ o MySQL 8+
- Accés `sudo`

---

## 🚀 Desplegament ràpid

### 1️⃣ Instal·lar stack LAMP

```bash
sudo apt update
sudo apt install -y apache2 mariadb-server php php-mysql
```

---

### 2️⃣ Copiar l’aplicació

Clona el repositori i col·loca el codi dins del web root:

```bash
git clone https://github.com/USUARI/album-cromos-lamp.git
sudo cp -r album-cromos-lamp/ /var/www/album
```

Permisos bàsics:

```bash
sudo chown -R www-data:www-data /var/www/album
sudo chmod -R 755 /var/www/album
```

---

### 3️⃣ Configurar Apache (VirtualHost)

Exemple mínim (`/etc/apache2/sites-available/album.conf`):

```apache
<VirtualHost *:80>
    ServerName album.test
    DocumentRoot /var/www/album

    <Directory /var/www/album>
        AllowOverride All
        Require all granted
    </Directory>

    # Bloquejar accés directe als uploads
    <Directory /var/www/album/uploads>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/album_error.log
    CustomLog ${APACHE_LOG_DIR}/album_access.log combined
</VirtualHost>
```

Activar el lloc:

```bash
sudo a2ensite album
sudo systemctl reload apache2
```

Afegeix al `/etc/hosts` (client o servidor):

```text
IP_DEL_SERVIDOR   album.test
```

---

## 🗄️ Inicialització de la base de dades

### 4️⃣ Executar `init_schema.sql`

Aquest script:

* crea la base de dades
* crea l’usuari `album_user`
* crea les taules (`groups`, `uploads`)

```bash
mysql -u root -p < sql/init_schema.sql
```

---

### 5️⃣ Preparar i executar `init_data.sql`

Aquest script insereix:

* usuari `profe`
* usuaris de grup

#### 🔐 Generar hashes de contrasenya

```bash
php -r 'echo password_hash("PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
```

Substitueix els placeholders a `sql/init_data.sql`:

```text
__HASH_PROFE__
__HASH_GRUP1__
...
```

Executa:

```bash
mysql -u root -p < sql/init_data.sql
```

---

## ⚙️ Configuració de l’aplicació

Edita `config.php` i ajusta:

* credencials BD (`album_user`)
* nom de la base de dades

---

## ✅ Accés a l’aplicació

* URL: `http://album.test`
* Login com a:

  * **grup** → àlbum propi
  * **profe** → vista global (lectura)

---

## 🔐 Notes de seguretat

* L’aplicació **no fa servir root de MariaDB**
* Els fitxers no són accessibles directament
* Eliminació amb POST + token CSRF
* Cada grup només pot accedir al seu àlbum

---

## 📁 Estructura del projecte

```text
assets/        → img + css
sql/         → scripts SQL (init_schema, init_data)
```

---

## 🧪 Context educatiu

Projecte pensat per:

* CFGM SMX
* Aprenentatge Basat en Projectes (ABP)
