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

