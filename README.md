# CINQ WP MCP

A WordPress plugin that turns a site built from **ACF flexible content blocks** into an
[MCP](https://modelcontextprotocol.io) server, so an AI client such as Claude can build
and edit its pages, posts, site-wide settings and menus — with the site's own blocks.

It is written for the way [CINQ](https://agencecinq.com) builds sites (the
[`wp-cinquante-et-un`](https://github.com/agencecinq/wp-cinquante-et-un) starter theme:
ACF field groups declared in PHP, pages assembled from a `blocks` flexible content field),
but nothing in it is specific to one site. **Install it, and it reads the theme**: every
flexible content field, every layout, every field and its constraints are discovered from
ACF at runtime. A layout added to the theme shows up in Claude without touching the plugin.

What a site may want to add — descriptions of its blocks, editorial rules, the page
templates of its design — goes in an optional `mcp.json` at the theme root, modeled on
`theme.json`.

[Version française](README.fr.md)

## Requirements

| Component | Version | Why |
|---|---|---|
| WordPress | 6.9+ | the Abilities API ships with core since 6.9 |
| PHP | 8.1+ | |
| [MCP Adapter](https://github.com/WordPress/mcp-adapter/releases/latest) | 0.6+ | MCP transport and server registration |
| [ACF Pro](https://www.advancedcustomfields.com/pro/) or [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) | | the blocks are ACF flexible content fields |

The plugin **cannot be activated** without these, and deactivates itself if ACF goes away
later. The free ACF does not include flexible content, so it is refused too. (The
`Requires Plugins` header cannot express "ACF Pro *or* SCF", so the check is done on
activation instead.)

## Install

```bash
wp plugin install https://github.com/WordPress/mcp-adapter/releases/latest/download/mcp-adapter.zip --activate
wp plugin install https://github.com/agencecinq/cinq-wp-mcp/releases/latest/download/cinq-wp-mcp.zip --activate
```

Updates come from GitHub releases and install themselves: the plugin's `Update URI` points
at this repository, WordPress sees each new release within twelve hours and installs it with
its own background updates. *Dashboard → Updates → Check again* looks right away. To keep
updates manual on a site, return false from the `cinq_wp_mcp_auto_update` filter. No update
is offered when the plugin folder is a Git working copy, since updating replaces the whole folder.

**Settings → CINQ WP MCP** shows the endpoint, what was found in the theme, where the
configuration comes from, and the connected applications.

## Connect Claude

The MCP endpoint is `https://<site>/wp-json/cinq-wp-mcp/mcp`.

### OAuth (recommended)

In Claude (desktop or claude.ai): *Settings → Connectors → Add custom connector*, a name and
that URL, nothing else. Click *Connect*: Claude discovers the server, registers itself, and
opens the site: sign in with your own WordPress account and allow the connection. Each person connects as themselves, with their
own capabilities, and appears as the author of their changes in the revisions.

Access is revoked per application from *Settings → CINQ WP MCP → Connected apps*, or by
each user from their profile.

The plugin is its own OAuth 2.1 authorization server, as the MCP specification expects:
metadata ([RFC 8414](https://www.rfc-editor.org/rfc/rfc8414),
[RFC 9728](https://www.rfc-editor.org/rfc/rfc9728)), dynamic client registration
([RFC 7591](https://www.rfc-editor.org/rfc/rfc7591)), PKCE S256 mandatory, public clients
only, one-hour access tokens, thirty-day refresh tokens rotated at each use (one replayed
past a one-minute grace window revokes the whole chain), revocation ([RFC 7009](https://www.rfc-editor.org/rfc/rfc7009)).
Tokens are stored as SHA-256 hashes and only open the MCP endpoint.

Registration accepts Claude's callbacks and loopback addresses (desktop and command-line
clients); add others with `settings.oauth.redirectUris`.

> The issuer is the plugin's REST route (`https://<site>/wp-json/cinq-wp-mcp/oauth`), so
> clients find the server metadata at `…/oauth/.well-known/openid-configuration`: discovery
> works wherever the MCP endpoint does, even behind a server that keeps the root
> `/.well-known/` for itself. The root documents are still served when they reach WordPress.
>
> If Claude fails to connect, *Settings → CINQ WP MCP → Check the connection* plays its
> requests from the server and says which step breaks (a cache answering in place of
> WordPress, an `Authorization` header dropped before PHP…).

### Application password

1. In your WordPress profile, create an application password.
2. In Claude, set the connector's authentication to **None** and add **one** request
   header:

   | Header | Value |
   |---|---|
   | `Authorization` | `Basic ` + base64 of `login:application password` |
   | `X-Api-Key` or `X-Auth-Token` | `login:application password` |

These three names are on the list of headers Claude accepts without approval. They are only
read on the MCP endpoint, and checked by WordPress core
(`wp_authenticate_application_password()`). One connector means one shared account.

```bash
curl -H 'X-Api-Key: LOGIN:APPLICATION PASSWORD' -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}' \
  'https://<site>/wp-json/cinq-wp-mcp/mcp'
```

## How the theme is read

**Zones.** Every ACF flexible content field is a *zone*, identified by the dotted path of
field names that leads to it: `blocks` on pages, `archive_posts.blocks` inside the blog
archive's options page. A zone applies where its field group's location rules say. The
*primary* zone of a content type is the one its `blocks` argument writes to — the root zone
named `blocks` when there is one. A zone on a taxonomy's terms is prefixed with the taxonomy,
`destination:blocks`, so that it never shadows the page zone of the same name.

**Layouts reserved for a content type.** When the theme hides a layout on some content type
through `acf/prepare_field` (as the editor would), the plugin asks the theme the same
question and refuses the write with a message saying where the block belongs.

**Page templates.** When a zone's location rules *require* a page template
(`page_template == page-templates/blocks-page.php`), writing blocks to a page assigns it, so
the page does not render empty. When they only *exclude* some (`!= styleguide`, the
starter's case), nothing is touched.

**Validation.** A write is validated whole before anything is saved and every problem comes
back at once with its path (`blocks[2].content.heading: expected one of h1, h2, h3`).
Required fields with a default are filled with it, as the editor would.

## The tools

Names take the configured prefix (`site-` by default).

| Tool | |
|---|---|
| `list-block-types` | zones and their block types, per content type, options page or taxonomy |
| `get-block-schema` | JSON Schema of block types |
| `list-page-templates` | design templates as block stacks (only when the theme declares some) |
| `get-content-schema` | the fields a content type owns outside its zones |
| `list-content` · `get-content` | find and read content, blocks included, in the write shape |
| `create-content` · `update-content` | create or update, blocks and fields included |
| `edit-blocks` | append, insert, replace, update, move, remove one block — on a post, an options page or a term |
| `delete-content` | trash, restore, delete |
| `search-media` · `upload-media` | media library |
| `list-terms` | taxonomy terms |
| `list-options-pages` · `get-options` · `update-options` | ACF options pages (when the theme has some) |
| `get-term` · `update-term` | a term and the ACF fields of its archive page; terms have no draft, a save is live |
| `list-menus` · `get-menu` · `update-menu` · `assign-menu-locations` | navigation menus; item IDs are kept on update |

The SEO title and description are written through Yoast, Rank Math or SEOPress, whichever is
active.

## `mcp.json`

Optional. At the theme root, merged like `theme.json`: **plugin defaults < parent theme <
child theme < `cinq_wp_mcp_config` filter < overrides saved in the admin**. Objects merge
key by key, lists replace each other, templates merge by name. A file that fails validation
is ignored whole and its errors are shown in the admin.

```json
{
	"$schema": "https://raw.githubusercontent.com/agencecinq/cinq-wp-mcp/main/schemas/mcp.schema.json",
	"version": 1,
	"settings": {
		"server": {
			"title": "Acme",
			"toolPrefix": "acme",
			"instructions": "Write in French, formal tone. Create content as drafts."
		},
		"postTypes": { "exclude": ["styleguide"] },
		"zones": { "labels": { "archive_posts.blocks": "Blog archive" } },
		"optionsPages": { "include": ["options-theme", "archive-post"] },
		"taxonomies": { "exclude": ["post_tag"] },
		"menus": { "locations": ["main", "footer"] },
		"pageTemplate": "auto",
		"seo": "auto",
		"oauth": { "redirectUris": [] }
	},
	"blocks": {
		"hero": { "description": "Opening of a page, once, always first." },
		"form": { "hidden": true }
	},
	"templates": [
		{
			"name": "landing",
			"title": "Landing page",
			"source": "https://www.figma.com/design/…",
			"sections": [
				{ "label": "Hero", "block": "hero" },
				{ "label": "Pricing", "block": "cards_grid", "status": "approx" },
				{ "label": "Booking", "status": "missing", "note": "No embed block yet." }
			]
		}
	]
}
```

- `settings.server.instructions` is appended to the server description: it is where the
  editorial rules for the AI go.
- `settings.postTypes` filters the public content types. A private type that has an admin
  screen (a catalog, a list of settings records) is reachable once named in `include`.
- `settings.taxonomies` filters the public taxonomies whose terms `get-term`, `update-term`
  and `edit-blocks` reach. A private taxonomy that has an admin screen is reachable once
  named in `include`.
- `blocks.<layout>` annotates a layout (`description`, `guidance`) or hides it (`hidden`).
- `templates` can also live one per file in `mcp/templates/<name>.json`. Section statuses:
  `exact` (the block is the section), `approx` (nearest block), `theme` (rendered outside
  the stack), `missing` (no block yet: Claude says so instead of approximating). Templates
  are re-checked against the live theme and a block that no longer exists is flagged.

The full format is [`schemas/mcp.schema.json`](schemas/mcp.schema.json); with `$schema` set,
editors autocomplete it.

## Development

```bash
composer install
composer test     # standalone harness, no WordPress needed
composer lint     # WordPress Coding Standards
```

The harness loads real theme ACF registrations from `tests/fixtures/` — the starter theme
and agencecinq.com — into in-memory stubs, and runs discovery, schemas, mapping, editing,
configuration, OAuth and updates against them. Refresh a fixture with
`bin/sync-fixtures <name> <path to the theme>`.

Release: bump `Version` and `VERSION` in `cinq-wp-mcp.php`, then push a `v*` tag. The
workflow tests, builds `cinq-wp-mcp.zip` and the `update.json` the updater reads, and
attaches both to a GitHub release.

## Limits

- Gutenberg blocks (including ACF blocks) are not handled: the plugin works with flexible
  content.
- Polylang and WPML are not handled: content is created in the default language.
- Widgets and the Customizer are out of scope.

## License

MIT © [CINQ](https://agencecinq.com)
