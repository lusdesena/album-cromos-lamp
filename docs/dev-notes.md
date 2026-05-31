# Dev notes

## Estat del projecte

`album-cromos-lamp` és una aplicació LAMP procedural per gestionar un àlbum digital de cromos com a sistema d’entregues gamificat.

El projecte s’ha migrat des d’un entorn principalment “en calent” sobre `/var/www` cap a un entorn local de desenvolupament amb Git, VSCode i Codex.

Entorns coneguts:

* repo canònic GitHub: `asanti3/album-cromos-lamp`
* entorn local de desenvolupament
* entorn de producció al servidor de la info1, en desús al maig de 2026 per fi de curs
* entorn de proves al mateix servidor de la info1

Control de versions sanejat:

* `uploads/` fora de Git
* `config.php` fora de Git
* `.gitignore` coherent
* `config.example.php` com a plantilla segura

L’aplicació continua sent procedural expressament. Els canvis han de continuar sent petits, incrementals i reversibles.

## Arquitectura Actual

El projecte ha començat el desacoblament real entre codi, configuració i dades persistents.

Ja existeix:

* runtime comú via `bootstrap.php`
* metadata de stickers a BD
* títols visibles dels stickers llegits des de `stickers`
* comptadors i totals de cromos calculats des de BD
* capa inicial de textos configurables via `app_settings`
* `login.php` com a pantalla de login configurable

Encara es manté:

* PHP procedural
* `config.php` local i no versionat
* constants històriques com `TOTAL_SLOTS` i `REAL_SLOTS` com a fallback temporal
* `login.html` per compatibilitat mentre hi hagi referències locals/no versionades

## Runtime i Bootstrap

S’ha implementat el primer refactor arquitectònic mínim amb:

* `bootstrap.php`

Responsabilitats:

* definir constants globals de runtime
* carregar `config.php`
* actuar com a punt d’entrada comú per als entrypoints PHP

Constants actuals:

* `BASE_URL`
* `BASE_PATH`
* `UPLOADS_DIR`
* `UPLOADS_URL`
* `SESSION_NAME`

Objectiu:
preparar el projecte per a multiinstància sense reescriptura massiva.

## Multiinstància

Direcció arquitectònica decidida:

* mateix codi base
* múltiples BDs separades
* múltiples carpetes `uploads` separades
* múltiples configuracions locals

No es farà, de moment:

* `instance_id`
* multi-tenant dins una mateixa BD

Aquesta opció és més simple, segura i adequada per al cas d’ús docent.

## Base de Dades i Migracions

S’ha generat:

* `sql/schema_current.sql`

Aquest fitxer és un snapshot de l’estat real de la BD restaurada.

Encara falta:

* esquema mínim net oficial
* seed demo coherent
* sistema formal de migracions

Migracions funcionals introduïdes:

* `sql/2026_05_29_create_stickers.sql`
* `sql/2026_05_29_create_app_settings.sql`

Les migracions actuals són incrementals i segures de reaplicar quan fan seed amb `INSERT IGNORE` o `ON DUPLICATE KEY UPDATE`.

## Stickers

S’ha introduït la taula `stickers` per treure del PHP la metadata funcional dels cromos.

Columnes principals:

* `slot`
* `title`
* `description`
* `bloc_id`
* `visible`
* `enabled`
* `required`
* `sort_order`

Objectiu:
convertir `stickers` en la font de veritat de:

* títols
* ordre
* visibilitat
* configuració
* agrupació per blocs

Durant la migració es va detectar que part dels stickers històrics havien desaparegut del codi PHP, probablement per sincronitzacions Git de l’etapa inicial. Les entregues i fitxers associats encara existien a BD i `uploads/`.

S’han restaurat manualment els stickers globals:

* slots 72-87
* associats a `bloc_id = 4`

Aquest episodi reforça la decisió de no mantenir metadata funcional hardcoded.

## Lectura Dinàmica de Stickers

La lectura dinàmica de cromos està completada funcionalment.

Ara:

* els títols visibles es llegeixen des de `stickers`
* els comptadors i percentatges es calculen des de BD
* el màxim de slots visibles es calcula dinàmicament
* `slot_title()` ja no és dependència funcional

Helpers a `helpers.php`:

* `get_stickers_map(mysqli $mysqli): array`
* `get_sticker(mysqli $mysqli, int $slot): ?array`
* `get_visible_enabled_stickers_count(mysqli $mysqli): int`
* `get_max_visible_enabled_sticker_slot(mysqli $mysqli): int`

Característiques:

* lectura una vegada per request quan toca
* fallback segur si la taula encara no existeix
* cache estàtica per lectures puntuals
* compatibilitat temporal amb constants històriques

Validació realitzada:

* càrrega correcta dels títols
* lectura des de BD
* modificació live d’un títol via SQL
* persistència del comportament existent

## Textos Configurables

S’ha introduït una primera capa de configuració persistent d’interfície:

* taula `app_settings`
* migració `sql/2026_05_29_create_app_settings.sql`

Helpers a `helpers.php`:

* `get_app_settings_map(mysqli $mysqli): array`
* `get_app_setting(mysqli $mysqli, string $key, string $default = ''): string`

Característiques:

* fallback segur
* tolerància a errors de BD
* cache estàtica en `get_app_setting()`

Claus actuals:

* `album_title`
* `album_brief`
* `album_subtitle`
* `project_name`
* `institution_name`
* `module_label`
* `login_instructions`

Semàntica actual:

* `album_title`: títol principal visible a la pantalla de login
* `album_brief`: nom curt de l’àlbum usat dins `album.php`

## Login

S’ha introduït:

* `login.php`

Basat en l’antic `login.html`, però ara:

* carrega `bootstrap.php`
* llegeix textos des de `app_settings`
* manté el layout visual pràcticament igual

`login.php` és la pantalla funcional principal.

`login.html` continua existint temporalment per compatibilitat, especialment perquè poden quedar referències locals/no versionades com `config.php`. La seva eliminació s’ha de fer només quan no quedin referències reals.

## Entorn Local i Validació

Clon local funcional:

* VSCode
* Codex
* Apache amb vhost local
* BD restaurada des de producció
* `uploads` restaurats amb la informació de finals de maig de 2026

Validacions manuals fetes durant el procés:

* login
* validació de cromos
* logout
* lectura dinàmica de títols
* canvi live de metadata via SQL

## Fitxers Sensibles

No versionats:

* `config.php`
* `uploads/`
* dumps reals
* configuració local

Fitxers delicats:

* `album.php`
* `upload.php`
* `helpers.php`
* `assets/css/styles.css`
* `config.php`

No tocar `config.php` real ni `uploads/` en refactors normals.

## Workflow

Workflow recomanat:

1. definir arquitectura o estratègia amb ChatGPT
2. usar Codex per canvis locals petits i mecànics
3. revisar diff manualment a VSCode
4. fer proves mínimes
5. crear commits petits

Bones pràctiques:

* evitar refactors grans sense necessitat
* mantenir PHP procedural
* validar sintaxi amb `php -l`
* no introduir frameworks sense decisió explícita
* no fer migracions destructives sense confirmació
* no deixar Codex actuar massivament sense supervisió

## Roadmap

### Fase 4.1 Admin Settings

Objectiu:
crear una interfície mínima de professorat/admin per editar `app_settings`.

Abast previst:

* llistat de claus configurables
* edició simple de valors
* validació bàsica
* sense sistema complex de permisos encara

### Fase 4.2 Admin Stickers

Objectiu:
gestionar stickers des de la pròpia aplicació.

Abast previst:

* editar títols i descripcions
* activar/desactivar stickers
* marcar visibilitat i obligatorietat
* revisar ordre i associació amb blocs

### Fase 4.3 Admin Usuaris i Grups

Objectiu:
facilitar la creació i manteniment d’usuaris, grups i grups-classe.

Abast previst:

* alta i baixa de grups
* gestió d’usuaris professorat
* associació a `grupsclasse`
* possible rol `admin` o ampliació controlada del rol `profe`

## Roadmap Tècnic Posterior

Tasques pendents de fons:

* crear `init_schema.sql` net
* separar schema i seed
* definir dades demo mínimes
* reduir lògica concentrada a `album.php`
* documentar desplegament multiinstància
* revisar redirects i URLs relatives
* continuar reduint dependència directa de `config.php`

Possible estructura futura:

* `public/`
* `src/`
* `templates/`
* `config/`
* `migrations/`

Sense reescriptura massiva immediata.

## Principis Arquitectònics

La direcció del projecte prioritza:

* persistència declarativa
* migracions SQL explícites
* configuració via BD
* refactors incrementals
* compatibilitat amb múltiples instàncies
* estabilitat sobre sofisticació

La combinació de migracions petites, fallbacks temporals i validació incremental està permetent evolucionar el prototip sense reescriure’l.
