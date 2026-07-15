# AGENTS.md

## Projecte

`album-cromos-lamp` és una aplicació web LAMP procedural per gestionar un àlbum de “cromos” com a sistema d’entregues gamificat per a alumnat de SMIX.

Cada cromo és una evidència d’aprenentatge, normalment una imatge o PDF, associada a un slot. L’alumnat puja cromos i el professorat els valida, rebutja o comenta.

## Stack

* PHP procedural
* Apache
* MariaDB/MySQL
* mysqli
* HTML/CSS sense framework
* Sessions PHP
* CSRF bàsic
* Git

No hi ha MVC ni framework. Els canvis han de ser incrementals i prudents.

## Rols

Hi ha dos rols principals:

* `group`: grup d’alumnes
* `profe`: professorat

Els usuaris són a la taula `groups`, que conté tant grups d’alumnes com usuaris professorat.

## Fitxers principals

* `config.php`: configuració local, credencials BD, sessions, auth helpers, CSRF, càrrega de helpers. No s’ha de versionar.
* `config.example.php`: plantilla segura de configuració.
* `helpers.php`: funcions comunes.
* `album.php`: vista principal de l’àlbum. Fitxer delicat, concentra molta lògica.
* `upload.php`: pujada/reemplaç de cromos i validació del professorat.
* `delete.php`: eliminació de cromos.
* `uploads.php`: serveix fitxers pujats.
* `groups.php`: vista de professorat amb grups, progrés i pendents.
* `assets/css/styles.css`: CSS principal.
* `sql/`: esquemes, migracions i dades inicials.

## Fitxers i directoris que NO s’han de versionar

* `config.php`
* `.env`
* `uploads/`
* fitxers temporals `*.swp`, `*.swo`
* dumps SQL reals
* dades locals d’instància

`uploads/` conté fitxers generats per l’ús real de l’aplicació i no forma part del codi.

## Base de dades

Taules conegudes:

* `groups`

  * usuaris i grups
  * camps importants: `id`, `name`, `username`, `password_hash`, `role`, `active`, `class_id`

* `uploads`

  * entregues de cromos
  * un upload per `group_id` + `slot`
  * camps importants: `group_id`, `slot`, `filename`, `original_name`, `status`, `profe_comment`

* `blocs`

  * defineix blocs d’entrega
  * camps: `nom`, `slot_inici`, `slot_final`, `visible`, `editable`, `ordre`

* `grupsclasse`

  * defineix grups-classe com 1A, 1B, Comp

* `bloc_calendari`

  * relaciona blocs i grups-classe amb dates d’obertura i tancament

## Estats dels cromos

* `pendent_validacio`: pujat per alumnat, pendent de revisió
* `validat`: acceptat pel professorat
* `rebutjat`: rebutjat pel professorat
* `pendent`: estat conceptual; normalment no hi ha fila a `uploads`

Quan l’alumnat reemplaça un cromo:

* `status = 'pendent_validacio'`
* `profe_comment = NULL`

## Blocs i editabilitat

La regla conceptual d’editabilitat és:

```text
bloc.visible == true
AND bloc.editable == true
AND groups.active == 1
AND now BETWEEN data_obertura AND data_tancament
```

La funció clau és:

```php
bloc_editable_per_slot(mysqli $mysqli, int $group_id, int $slot): bool
```

S’ha d’aplicar com a defensa backend a:

* `upload.php`
* `delete.php`

La UI pot ocultar o bloquejar accions, però el backend ha de validar sempre.

## Professorat

El professorat pot:

* veure àlbums dels grups
* validar cromos
* rebutjar cromos
* tornar cromos a pendent de validació
* escriure o modificar comentaris

Actualment el professorat pot validar encara que el bloc estigui tancat, llevat que es decideixi explícitament el contrari.

## Paginació i retorns

Les redireccions després de validar han de preservar el context actual:

* `group_id`
* `bloc_id`
* `page`
* mode àlbum complet
* futurs paràmetres

Patró recomanat:

```php
$_SERVER['REQUEST_URI']
```

Evitar construir URLs incompletes manualment.

## Àlbum complet

Quan no hi ha `bloc_id`, l’aplicació pot funcionar en mode “Àlbum complet”.

Compte amb:

* càlcul de progrés global
* paginació
* missatges de bloc
* editabilitat slot a slot
* absència de `$bloc` concret

No assumir que sempre hi ha un bloc actiu.

## UI i CSS

Hi ha barres de progrés amb quatre estats:

* validat
* pendent de validació
* rebutjat
* no entregat

Classes conegudes:

* `.mini-ok`
* `.mini-wait`
* `.mini-bad`
* `.mini-none`
* `.sticker-status.status-validat`
* `.sticker-status.status-rebutjat`
* `.sticker-status.status-pendent_validacio`
* `.profe-comment.status-validat`
* `.profe-comment.status-rebutjat`
* `.profe-comment.status-pendent_validacio`

Vigilar especialment la cascada CSS i la caché del navegador.

## Convencions de desenvolupament

* Mantenir PHP procedural per ara.
* Evitar refactors grans sense necessitat.
* Fer commits petits i reversibles.
* Validar sintaxi PHP abans de commitar:

```bash
php -l fitxer.php
```

* No introduir frameworks sense decisió explícita.
* No moure massivament estructura sense pla previ.
* Preferir helpers petits i reutilitzables.
* Evitar duplicar SQL complex.
* No incrustar credencials ni dades d’instància al repo.

## Objectiu arquitectònic proper

Fer que el projecte sigui multiinstància:

* mateix codi
* diferents BDs
* diferents `config.php` o `.env`
* diferents carpetes `uploads`
* desplegament senzill

La BD hauria de definir progressivament l’àlbum, blocs, calendaris i grups.

## Millores futures previstes

* `.env` o configuració externa neta
* migracions SQL versionades
* instal·lador o script d’inicialització
* millor separació entre codi, configuració, uploads i dades
* possible estructura `public/`, `src/`, `templates/`, `migrations/`
* reducció de lògica concentrada a `album.php`
* millora de `groups.php` amb filtres i pendents
* documentació de desplegament
* docker-compose opcional, no prioritari

## Precaucions per a agents

Abans de modificar codi:

1. identificar fitxers afectats
2. fer canvi mínim
3. no tocar `config.php` real
4. no tocar `uploads/`
5. executar `php -l` als PHP modificats
6. revisar flux alumnat i flux professorat
7. proposar commit petit

Fitxers especialment delicats:

* `album.php`
* `upload.php`
* `delete.php`
* `config.php`
* `helpers.php`
* `assets/css/styles.css`

No fer `git reset --hard`, `git clean -fd`, migracions destructives ni canvis massius sense confirmació explícita.
