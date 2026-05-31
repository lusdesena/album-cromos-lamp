# Dev notes

## Estat actual del projecte

El projecte `album-cromos-lamp` s’ha migrat des d’un entorn principalment “en calent” sobre `/var/www` a un entorn de desenvolupament local net amb Git, VSCode i Codex.

Ara existeixen:

* repo canònic GitHub: `asanti3/album-cromos-lamp`
* entorn local de desenvolupament
* entorn de producció al servidor de la info1, tot i que al maig 2026 està en desús (fi de curs)
* entorn de proves al mateix servidor de la info1

S’ha sanejat el control de versions:

* `uploads/` fora de Git
* `config.php` fora de Git
* `.gitignore` coherent
* `config.example.php` com a plantilla segura

## Refactor bootstrap

S’ha implementat el primer refactor arquitectònic mínim.

Nou fitxer:

* `bootstrap.php`

Responsabilitats:

* definir constants globals de runtime
* carregar `config.php`
* actuar com a punt d’entrada comú

Constants actuals:

* `BASE_URL`
* `BASE_PATH`
* `UPLOADS_DIR`
* `SESSION_NAME`

Tots els entrypoints PHP carreguen ara `bootstrap.php`.

Objectiu:
preparar el projecte per a multiinstància sense refactor massiu.

## Multiinstància

Direcció arquitectònica decidida:

* mateix codi
* múltiples BDs separades
* múltiples carpetes uploads separades
* múltiples configuracions locals

De moment NO es farà:

* `instance_id`
* multi-tenant dins la mateixa BD

Es considera més simple, segur i adequat pel cas d’ús docent.

## Entorn local

Clon local funcional:

* VSCode
* Codex
* Apache amb vhost local
* BD restaurada des de producció
* uploads restaurats amb la informació de finals de maig de 2026

S’han validat manualment:

* login
* validació de cromos
* logout

## Git i workflow

Workflow actual recomanat:

1. parlar arquitectura/estratègia amb ChatGPT
2. usar Codex per canvis locals petits i mecànics
3. revisar diff manualment a VSCode
4. proves mínimes
5. commits petits

No deixar Codex actuar massivament sense supervisió.

## Fitxers sensibles

No versionats:

* `config.php`
* `uploads/`
* dumps reals
* configuració local

Fitxers delicats:

* `album.php`
* `upload.php`
* `helpers.php`
* `styles.css`

## Estat BD

S’ha generat:

* `sql/schema_current.sql`

Aquest fitxer és snapshot de l’estat real actual de la BD restaurada.

Encara NO existeix:

* esquema mínim net oficial
* seed demo coherent
* sistema de migracions

## Properes tasques prioritàries

### Curt termini

1. desacoblar completament paths/configuració
2. introduir `UPLOADS_URL`
3. eliminar paths hardcoded
4. revisar redirects i URLs relatives
5. continuar reduint dependència directa de `config.php`

### Mitjà termini

1. crear `init_schema.sql` net
2. separar schema i seed
3. definir dades demo mínimes
4. començar a modularitzar `album.php`
4b. cal treure del codi els cromos hardcoded. S'han de poder tenir a BD per encarar-nos a projecte multiinstància
5. interfície amable per a creació d'usuaris (potser a partir d'un nou rol "admin" amb els privilegis de "profe" + "gestió d'usuaris"
6. Personalització d'interfície per a multiinstància (textos hardcoded com "àlbum de captures del projecte")

### Llarg termini

Possible estructura:

* `public/`
* `src/`
* `templates/`
* `config/`
* `migrations/`

Però sense reescriptura massiva immediata.

## Notes importants

* el projecte continua sent procedural expressament
* els canvis han de ser incrementals
* prioritzar estabilitat i simplicitat
* evitar sobreenginyeria
* no introduir frameworks sense decisió explícita

## Observacions sobre agents

Codex funciona molt bé per:

* canvis repetitius
* refactors petits
* actualitzacions globals
* revisió de paths/imports

Però necessita:

* prompts molt concrets
* supervisió arquitectònica
* commits petits
* context via `AGENTS.md`

ChatGPT s’està fent servir com a capa d’arquitectura, memòria i direcció tècnica.

## Estat actual BD / stickers

S’ha introduït la primera migració orientada a desacoblar els cromos del codi PHP:

* `sql/2026_05_29_create_stickers.sql`

La migració:

* crea la nova taula `stickers`
* introdueix metadata configurable per sticker
* afegeix FK cap a `blocs`
* pobla la taula amb els títols actuals

La migració és:

* incremental
* reversible
* segura de reaplicar (`INSERT IGNORE`)

### Taula stickers

Columnes actuals:

* `id`
* `slot`
* `title`
* `description`
* `bloc_id`
* `visible`
* `enabled`
* `required`
* `sort_order`
* timestamps

Objectiu:
convertir `stickers` en la futura font de veritat de:

* títols
* ordre
* visibilitat
* configuració
* agrupació per blocs

## Recuperació de stickers perduts

Durant la migració s’ha detectat que part dels stickers històrics havien desaparegut del codi PHP, probablement degut a sincronitzacions Git fetes durant l’etapa inicial del projecte (“a salto de mata”).

Tanmateix:

* encara existien entregues associades a aquests slots
* les dades es conservaven a la BD i uploads

S’han restaurat manualment els stickers globals:

* slots 72–87
* associats a `bloc_id = 4`

Això reforça la necessitat de:

* eliminar metadata hardcoded del codi
* utilitzar la BD com a font persistent de configuració

## Proper pas previst

El següent refactor previst és:

1. introduir helpers:

   * `get_stickers_map()`
   * `get_sticker($slot)`

2. carregar metadata de stickers des de BD

3. substituir gradualment:

   * `slot_title()`
     per:
   * lectura dinàmica des de `stickers`

Inicialment:

* es mantindrà `slot_title()` com a fallback
* es mantindran constants com `TOTAL_SLOTS`
* el canvi ha de ser incremental i reversible

## Objectiu funcional proper

L’objectiu immediat és poder desplegar noves instàncies de l’aplicació sense modificar PHP manualment, configurant:

* BD
* usuaris
* blocs
* stickers

directament des de dades persistides.

## Notes arquitectòniques

El descobriment dels stickers perduts ha confirmat un problema estructural important de l’etapa inicial del projecte:

* dependència excessiva de dades hardcoded
* absència de migracions/versionat de dades funcionals
* divergència temporal entre producció i repositoris Git

La nova direcció del projecte prioritza:

* persistència declarativa
* migracions SQL explícites
* configuració via BD
* refactors incrementals
* compatibilitat amb múltiples instàncies

## Lectura dinàmica de stickers

S’ha completat la primera fase funcional de desacoblament dels cromos respecte del codi PHP.

Ara:

* els títols visibles dels stickers es llegeixen des de la BD (`stickers`)
* `album.php` carrega metadata dinàmica amb:

  * `get_stickers_map()`
  * `get_sticker()`

La càrrega es fa:

* una sola vegada per request
* amb cache estàtica a helper
* amb fallback segur

Inicialment es va mantenir:

* `slot_title()`
* constants històriques (`TOTAL_SLOTS`, `REAL_SLOTS`)

per garantir:

* reversibilitat
* estabilitat
* compatibilitat temporal

## Situació arquitectònica actual

Els stickers ja no depenen funcionalment de dades hardcoded.

Això permet:

* modificar títols des de SQL
* afegir nous stickers sense tocar PHP
* preparar backend admin
* preparar configuració multiinstància

Aquest és probablement el primer gran desacoblament arquitectònic real del projecte.

## Helpers introduïts

A `helpers.php`:

* `get_stickers_map(mysqli $mysqli): array`
* `get_sticker(mysqli $mysqli, int $slot): ?array`

Objectius:

* centralitzar lectura de metadata
* evitar múltiples queries repetides
* preparar futures capes de configuració

## Validació funcional realitzada

S’ha validat:

* càrrega correcta dels títols
* lectura des de BD
* modificació live d’un títol via SQL
* persistència correcta del comportament existent

No s’han detectat regressions visibles.

## Proper pas previst

El següent pas previst és:

* eliminar definitivament `slot_title()`
* substituir fallback per text genèric
* completar el desacoblament de metadata hardcoded

Després:

* textos configurables
* backend admin mínim
* generació neta de noves instàncies

## Reflexió arquitectònica

La migració progressiva cap a metadata persistent està demostrant ser molt menys arriscada del que semblava inicialment.

La combinació:

* migracions SQL explícites
* refactors petits
* fallback temporal
* validació incremental

està permetent evolucionar el prototip sense reescriptures massives.

## Punt 2 completat — lectura dinàmica de cromos

S’ha completat la migració funcional dels cromos cap a metadata persistent en BD.

Ja no existeixen:

* mappings hardcoded de títols
* dependència funcional de `slot_title()`
* dependència principal de `REAL_SLOTS` per al progrés

Ara:

* els títols es llegeixen des de `stickers`
* els comptadors i percentatges es calculen des de BD
* el màxim de slots visibles es calcula dinàmicament

Helpers nous:

* `get_visible_enabled_stickers_count()`
* `get_max_visible_enabled_sticker_slot()`

El projecte ja no depèn estructuralment de constants hardcoded per representar els cromos.

Això permet:

* múltiples àlbums diferents
* ampliació de stickers sense tocar PHP
* configuració persistent
* preparació per backend admin

## Situació arquitectònica actual

En aquest punt:

* runtime/configuració desacoblats
* metadata de stickers desacoblada
* comptadors desacoblats
* base multiinstància funcional

L’aplicació continua sent procedural expressament, però ja amb una separació molt més clara entre:

* configuració
* dades persistents
* lògica de presentació

## Proper objectiu

Punt 3:

* textos configurables (`app_settings`)

Objectiu:
eliminar textos UI hardcoded importants i preparar personalització per instància.

Primers candidats:

* títol de l’àlbum
* subtítol
* nom del projecte
* nom de la institució

## Punt 3 iniciat — textos configurables

S’ha introduït una primera capa de configuració persistent d’interfície via BD.

Nova taula:

* `app_settings`

Migració:

* `sql/2026_05_29_create_app_settings.sql`

Objectiu:
desacoblar textos visibles del codi PHP i preparar personalització per instància.

## Helpers introduïts

A `helpers.php`:

* `get_app_settings_map(mysqli $mysqli): array`
* `get_app_setting(mysqli $mysqli, string $key, string $default = ''): string`

Característiques:

* cache intern estàtic
* fallback segur
* tolerància a errors de BD

## Pantalla de login configurable

S’ha introduït:

* `login.php`

basat en l’antic `login.html`, però ara:

* carregant `bootstrap.php`
* llegint textos des de `app_settings`

Textos actualment configurables:

* `album_title`
* `album_brief`
* `album_subtitle`
* `project_name`
* `institution_name`
* `module_label`
* `login_instructions`

## Canvi conceptual important

Durant el desenvolupament s’ha detectat una diferència conceptual entre:

* el nom curt de l’àlbum visible dins l’aplicació
* el títol principal/promocional visible a la pantalla de login

Per això:

* `album_title` passa a representar el títol principal
* `album_brief` representa el nom curt usat a `album.php`

## Situació actual de login.html

`login.html` continua existint temporalment només per compatibilitat.

Actualment:

* `login.php` és la pantalla funcional principal
* alguns redirects locals/no versionats encara apunten a `login.html`
* la seva eliminació definitiva es farà més endavant durant la neteja d’autenticació/configuració

## Capacitats ja assolides

En aquest punt ja és possible:

* desplegar múltiples instàncies
* personalitzar textos principals via SQL
* canviar branding sense modificar PHP
* reutilitzar el mateix codi base per diferents projectes/mòduls

Aquest és un altre desacoblament arquitectònic important del projecte.
