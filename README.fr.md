# CINQ WP MCP

Plugin WordPress qui transforme un site construit en **blocs ACF flexible content** en
serveur [MCP](https://modelcontextprotocol.io) : un client IA comme Claude peut alors
créer et modifier ses pages, ses articles, ses réglages globaux et ses menus, avec les blocs
du site.

Il suit la manière dont [CINQ](https://agencecinq.com) construit ses sites (le thème de
départ [`wp-cinquante-et-un`](https://github.com/agencecinq/wp-cinquante-et-un) : groupes
ACF déclarés en PHP, pages assemblées dans un champ flexible `blocks`), mais rien n'y est
propre à un site. **On l'installe, il lit le thème** : chaque champ flexible, chaque layout,
chaque champ et ses contraintes sont découverts dans ACF à l'exécution. Un layout ajouté au
thème apparaît dans Claude sans toucher au plugin.

Ce qu'un site veut ajouter (description de ses blocs, règles éditoriales, gabarits de sa
maquette) va dans un `mcp.json` facultatif à la racine du thème, sur le modèle de
`theme.json`.

[English version](README.md)

## Prérequis

| Composant | Version | Pourquoi |
|---|---|---|
| WordPress | 6.9+ | l'Abilities API est dans le cœur depuis 6.9 |
| PHP | 8.1+ | |
| [MCP Adapter](https://github.com/WordPress/mcp-adapter/releases/latest) | 0.6+ | transport MCP et déclaration du serveur |
| [ACF Pro](https://www.advancedcustomfields.com/pro/) ou [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) | | les blocs sont des champs flexible content |

Le plugin **refuse de s'activer** sans eux, et se désactive tout seul si ACF disparaît
ensuite. ACF gratuit n'a pas le flexible content : il est refusé aussi. (L'en-tête
`Requires Plugins` ne sait pas dire « ACF Pro *ou* SCF », d'où une vérification à
l'activation.)

## Installation

```bash
wp plugin install https://github.com/WordPress/mcp-adapter/releases/latest/download/mcp-adapter.zip --activate
wp plugin install https://github.com/agencecinq/cinq-wp-mcp/releases/latest/download/cinq-wp-mcp.zip --activate
```

Les mises à jour arrivent depuis les releases GitHub et s'installent seules : l'en-tête
`Update URI` pointe sur ce dépôt, WordPress voit chaque nouvelle version dans les douze heures
et l'installe avec ses mises à jour automatiques. *Tableau de bord → Mises à jour → Vérifier à
nouveau* regarde tout de suite. Pour garder la main sur un site, renvoyer false au filtre
`cinq_wp_mcp_auto_update`.

**Réglages → CINQ WP MCP** montre l'adresse du serveur, ce que le plugin a trouvé dans le
thème, d'où vient la configuration, et les applications connectées.

## Connecter Claude

Le serveur MCP répond sur `https://<site>/wp-json/cinq-wp-mcp/mcp`.

### OAuth (recommandé)

Dans Claude (desktop ou claude.ai) : *Paramètres → Connecteurs → Ajouter un connecteur
personnalisé*, un nom et cette adresse, rien d'autre. Cliquer *Connecter* : Claude découvre
le serveur, s'enregistre et ouvre le site ; on se connecte avec son propre compte WordPress
et on autorise. Chacun se connecte en son nom, avec ses droits, et apparaît
comme auteur de ses modifications dans les révisions.

Les accès se révoquent par application dans *Réglages → CINQ WP MCP → Applications
connectées*, ou par chaque utilisateur depuis son profil.

Le plugin est son propre serveur d'autorisation OAuth 2.1, comme l'attend la spec MCP :
métadonnées (RFC 8414, RFC 9728), enregistrement dynamique des clients (RFC 7591), PKCE S256
obligatoire, clients publics uniquement, jetons d'accès d'une heure, jetons de
rafraîchissement de trente jours renouvelés à chaque usage (un jeton rejoué au-delà d'une
minute de tolérance révoque toute la chaîne), révocation (RFC 7009). Les jetons sont stockés hachés (SHA-256) et n'ouvrent que
l'endpoint MCP.

L'enregistrement accepte les adresses de retour de Claude et les adresses locales (clients
desktop et ligne de commande) ; en ajouter d'autres avec `settings.oauth.redirectUris`.

> L'émetteur est la route REST du plugin (`https://<site>/wp-json/cinq-wp-mcp/oauth`) : les
> clients trouvent les métadonnées du serveur à `…/oauth/.well-known/openid-configuration`, et
> la découverte marche partout où l'endpoint MCP marche, même derrière un serveur qui garde
> le `/.well-known/` de la racine pour lui. Les documents à la racine restent servis quand ils
> arrivent jusqu'à WordPress.
>
> Si Claude n'arrive pas à se connecter, *Réglages → CINQ WP MCP → Check the connection*
> rejoue ses requêtes depuis le serveur et dit quelle étape casse (un cache qui répond à la
> place de WordPress, un en-tête `Authorization` supprimé avant PHP…).

### Mot de passe d'application

1. Dans son profil WordPress, créer un mot de passe d'application.
2. Dans Claude, authentification **Aucune** et **un** en-tête de requête :

   | En-tête | Valeur |
   |---|---|
   | `Authorization` | `Basic ` + base64 de `identifiant:mot de passe d'application` |
   | `X-Api-Key` ou `X-Auth-Token` | `identifiant:mot de passe d'application` |

Ces trois noms font partie des en-têtes que Claude accepte sans validation préalable. Ils ne
sont lus que sur l'endpoint MCP et vérifiés par le cœur de WordPress. Un connecteur = un
compte partagé.

## Comment le thème est lu

**Zones.** Chaque champ flexible ACF est une *zone*, nommée par le chemin des champs qui y
mène : `blocks` sur les pages, `archive_posts.blocks` dans la page d'options de l'archive du
blog. Une zone s'applique là où les règles d'emplacement de son groupe le disent. La zone
*principale* d'un type de contenu est celle où écrit l'argument `blocks`.

**Blocs réservés.** Quand le thème masque un layout sur un type de contenu via
`acf/prepare_field`, le plugin pose la même question au thème et refuse l'écriture en disant
où le bloc a sa place.

**Template de page.** Quand les règles d'une zone *exigent* un template
(`page_template == page-templates/blocks-page.php`), écrire des blocs sur une page
l'assigne. Quand elles en *excluent* seulement (`!= styleguide`, le cas du starter), rien
n'est touché.

**Validation.** Une écriture est validée en entier avant tout enregistrement, et toutes les
erreurs reviennent d'un coup avec leur chemin. Un champ obligatoire qui a une valeur par
défaut la reçoit, comme dans l'éditeur.

## `mcp.json`

Facultatif. À la racine du thème, fusionné comme `theme.json` : **valeurs par défaut du
plugin < thème parent < thème enfant < filtre `cinq_wp_mcp_config` < surcharges enregistrées
dans l'admin**. Le format complet est dans [`schemas/mcp.schema.json`](schemas/mcp.schema.json)
et l'exemple commenté dans le [README anglais](README.md#mcpjson).

Les gabarits peuvent aussi vivre un par fichier dans `mcp/templates/<nom>.json`. Statuts
d'une section : `exact` (le bloc est la section), `approx` (bloc le plus proche), `theme`
(rendu hors pile de blocs), `missing` (aucun bloc : Claude le dit au lieu d'approximer).

## Développement

```bash
composer install
composer test     # banc d'essai autonome, sans WordPress
composer lint     # WordPress Coding Standards
```

Le banc d'essai charge les vrais enregistrements ACF de `tests/fixtures/` (le starter et
agencecinq.com). Les mettre à jour : `bin/sync-fixtures <nom> <chemin du thème>`.

Publier : monter `Version` et `VERSION` dans `cinq-wp-mcp.php`, pousser un tag `v*`. Le
workflow teste, construit `cinq-wp-mcp.zip` et l'`update.json` que lit l'updater, et les
attache à une release GitHub.

## Limites

- Les blocs Gutenberg (y compris les blocs ACF) ne sont pas gérés : le plugin travaille avec
  le flexible content.
- Polylang et WPML ne sont pas gérés : les contenus sont créés dans la langue par défaut.
- Widgets et Customizer hors périmètre.

## Licence

MIT © [CINQ](https://agencecinq.com)
