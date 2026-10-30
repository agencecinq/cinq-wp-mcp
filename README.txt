=== CINQ WP MCP ===

Tested up to: 7.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Exposes a site built from ACF flexible content blocks as an MCP server, so AI clients such as Claude can build and edit its pages, settings and menus.

== Description ==

A WordPress plugin that turns a site built from **ACF flexible content blocks** into an [MCP](https://modelcontextprotocol.io) server, so an AI client such as Claude can build and edit its pages, posts, site-wide settings and menus — with the site's own blocks.

It is written for the way [CINQ](https://agencecinq.com) builds sites (the [wp-cinquante-et-un](https://github.com/agencecinq/wp-cinquante-et-un) starter theme: ACF field groups declared in PHP, pages assembled from a `blocks` flexible content field), but nothing in it is specific to one site. **Install it, and it reads the theme**: every flexible content field, every layout, every field and its constraints are discovered from ACF at runtime. A layout added to the theme shows up in Claude without touching the plugin.

What a site may want to add — descriptions of its blocks, editorial rules, the page templates of its design — goes in an optional `mcp.json` at the theme root, modeled on `theme.json`.

= How the theme is read =

**Zones.** Every ACF flexible content field is a zone, identified by the dotted path of field names that leads to it: `blocks` on pages, `archive_posts.blocks` inside the blog archive's options page. A zone applies where its field group's location rules say. The primary zone of a content type is the one its `blocks` argument writes to — the root zone named `blocks` when there is one. A zone on a taxonomy's terms is prefixed with the taxonomy, `destination:blocks`, so that it never shadows the page zone of the same name.

**Layouts reserved for a content type.** When the theme hides a layout on some content type through `acf/prepare_field` (as the editor would), the plugin asks the theme the same question and refuses the write with a message saying where the block belongs.

**Page templates.** When a zone's location rules require a page template (`page_template == page-templates/blocks-page.php`), writing blocks to a page assigns it, so the page does not render empty. When they only exclude some (`!= styleguide`, the starter's case), nothing is touched.

**Validation.** A write is validated whole before anything is saved and every problem comes back at once with its path (`blocks[2].content.heading: expected one of h1, h2, h3`). Required fields with a default are filled with it, as the editor would.

= The tools =

Names take the configured prefix (`site-` by default).

* `list-block-types` – zones and their block types, per content type, options page or taxonomy
* `get-block-schema` – JSON Schema of block types
* `list-page-templates` – design templates as block stacks (only when the theme declares some)
* `get-content-schema` – the fields a content type owns outside its zones
* `list-content` · `get-content` – find and read content, blocks included, in the write shape
* `create-content` · `update-content` – create or update, blocks and fields included
* `edit-blocks` – append, insert, replace, update, move, remove one block, on a post, an options page or a term
* `delete-content` – trash, restore, delete
* `search-media` · `upload-media` – media library
* `list-terms` – taxonomy terms
* `list-options-pages` · `get-options` · `update-options` – ACF options pages (when the theme has some)
* `get-term` · `update-term` – a term and the ACF fields of its archive page; terms have no draft, a save is live
* `list-menus` · `get-menu` · `update-menu` · `assign-menu-locations` – navigation menus; item IDs are kept on update
* `list-forms` · `get-form` · `update-form` – Contact Form 7 forms (when it is active): template, emails, messages, additional settings; `mail`, `mail_2` and `messages` merge key by key, and Contact Form 7's configuration warnings come back with the reply

The SEO title and description are written through Yoast, Rank Math or SEOPress, whichever is active.

= mcp.json =

Optional. At the theme root, merged like `theme.json`: **plugin defaults < parent theme < child theme < `cinq_wp_mcp_config` filter < overrides saved in the admin**. Objects merge key by key, lists replace each other, templates merge by name. A file that fails validation is ignored whole and its errors are shown in the admin.

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
		"forms": { "enabled": true },
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

* `settings.server.instructions` is appended to the server description: it is where the editorial rules for the AI go.
* `settings.postTypes` filters the public content types. A private type that has an admin screen (a catalog, a list of settings records) is reachable once named in `include`.
* `settings.taxonomies` filters the public taxonomies whose terms `get-term`, `update-term` and `edit-blocks` reach. A private taxonomy that has an admin screen is reachable once named in `include`.
* `settings.forms.enabled` turns the Contact Form 7 tools off. Forms are not reached through `settings.postTypes`: their emails and messages are Contact Form 7 properties, not post fields.
* `blocks.<layout>` annotates a layout (`description`, `guidance`) or hides it (`hidden`).
* `templates` can also live one per file in `mcp/templates/<name>.json`. Section statuses: `exact` (the block is the section), `approx` (nearest block), `theme` (rendered outside the stack), `missing` (no block yet: Claude says so instead of approximating). Templates are re-checked against the live theme and a block that no longer exists is flagged.

The full format is [schemas/mcp.schema.json](https://github.com/agencecinq/cinq-wp-mcp/blob/main/schemas/mcp.schema.json); with `$schema` set, editors autocomplete it.

= Limits =

* Gutenberg blocks (including ACF blocks) are not handled: the plugin works with flexible content.
* Polylang and WPML are not handled: content is created in the default language.
* Widgets and the Customizer are out of scope.

== Installation ==

= Requirements =

* WordPress 6.9 or later: the Abilities API ships with core since 6.9
* PHP 8.1 or later
* [MCP Adapter](https://github.com/WordPress/mcp-adapter/releases/latest) 0.6 or later: MCP transport and server registration
* [ACF Pro](https://www.advancedcustomfields.com/pro/) or [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/): the blocks are ACF flexible content fields

The plugin **cannot be activated** without these, and deactivates itself if ACF goes away later. The free ACF does not include flexible content, so it is refused too. (The `Requires Plugins` header cannot express "ACF Pro or SCF", so the check is done on activation instead.)

= Install =

```bash
wp plugin install https://github.com/WordPress/mcp-adapter/releases/latest/download/mcp-adapter.zip --activate
wp plugin install https://github.com/agencecinq/cinq-wp-mcp/releases/latest/download/cinq-wp-mcp.zip --activate
```

**Settings → CINQ WP MCP** shows the endpoint, what was found in the theme, where the configuration comes from, and the connected applications.

= Connect Claude with OAuth (recommended) =

The MCP endpoint is `https://<site>/wp-json/cinq-wp-mcp/mcp`.

In Claude (desktop or claude.ai): **Settings → Connectors → Add custom connector**, a name and that URL, nothing else. Click **Connect**: Claude discovers the server, registers itself, and opens the site: sign in with your own WordPress account and allow the connection. Each person connects as themselves, with their own capabilities, and appears as the author of their changes in the revisions.

Access is revoked per application from **Settings → CINQ WP MCP → Connected apps**, or by each user from their profile.

The plugin is its own OAuth 2.1 authorization server, as the MCP specification expects: metadata ([RFC 8414](https://www.rfc-editor.org/rfc/rfc8414), [RFC 9728](https://www.rfc-editor.org/rfc/rfc9728)), dynamic client registration ([RFC 7591](https://www.rfc-editor.org/rfc/rfc7591)), PKCE S256 mandatory, public clients only, one-hour access tokens, thirty-day refresh tokens rotated at each use (one replayed past a one-minute grace window revokes the whole chain), revocation ([RFC 7009](https://www.rfc-editor.org/rfc/rfc7009)). Tokens are stored as SHA-256 hashes and only open the MCP endpoint.

Registration accepts Claude's callbacks and loopback addresses (desktop and command-line clients); add others with `settings.oauth.redirectUris`.

The issuer is the plugin's REST route (`https://<site>/wp-json/cinq-wp-mcp/oauth`), so clients find the server metadata at `…/oauth/.well-known/openid-configuration`: discovery works wherever the MCP endpoint does, even behind a server that keeps the root `/.well-known/` for itself. The root documents are still served when they reach WordPress.

If Claude fails to connect, **Settings → CINQ WP MCP → Check the connection** plays its requests from the server and says which step breaks (a cache answering in place of WordPress, an `Authorization` header dropped before PHP…).

= Connect Claude with an application password =

1. In your WordPress profile, create an application password.
2. In Claude, set the connector's authentication to **None** and add **one** request header: `Authorization` with `Basic ` and the base64 of `login:application password`, or `X-Api-Key` or `X-Auth-Token` with `login:application password`.

These three names are on the list of headers Claude accepts without approval. They are only read on the MCP endpoint, and checked by WordPress core (`wp_authenticate_application_password()`). One connector means one shared account.

```bash
curl -H 'X-Api-Key: LOGIN:APPLICATION PASSWORD' -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}' \
  'https://<site>/wp-json/cinq-wp-mcp/mcp'
```

= Updates =

Updates come from GitHub releases: the plugin's `Update URI` points at this repository, and WordPress sees each new release within twelve hours. **Dashboard → Updates → Check again** looks right away. Auto-updates are off by default, as for any plugin: **Enable auto-updates** in the Plugins screen turns them on for the site. No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder.

To publish a release:

1. Bump `Version` in `cinq-wp-mcp.php`, and add the version to the changelog below.
2. Commit, push, then push a tag: `git tag v2.1.0 && git push origin v2.1.0`.

The release workflow checks that the tag matches the version and has a changelog entry, runs the tests, builds `cinq-wp-mcp.zip` with `git archive` (`.gitattributes` leaves the development files out) and attaches it to the release with `update.json`. The changelog entry becomes the release notes, and the plugin details show this file as it is in the release.

= Development =

```bash
composer install
composer test     # standalone harness, no WordPress needed
composer lint     # WordPress Coding Standards
```

The harness loads real theme ACF registrations from `tests/fixtures/` — the starter theme and agencecinq.com — into in-memory stubs, and runs discovery, schemas, mapping, editing, configuration, OAuth and updates against them. Refresh a fixture with `bin/sync-fixtures <name> <path to the theme>`.

== Changelog ==

= 2.2.0 =
* Contact Form 7 forms are read and written: `list-forms`, `get-form`, `update-form`. Changing who receives a form's email, its subject or its messages no longer needs the admin. The tools appear when Contact Form 7 is active; `settings.forms.enabled` turns them off.

= 2.1.0 =
* The plugin details show this readme: description, installation and changelog, with the WordPress version it is tested up to and a banner. The Updates screen shows its icon.
* The changelog entry of a release is its release notes, instead of the notes GitHub generates.

= 2.0.0 =
* Breaking: auto-updates are no longer forced on, and the `cinq_wp_mcp_auto_update` filter is removed. They are WordPress's own, off by default, as for any plugin. Migration: turn them on per site with **Enable auto-updates** in the Plugins screen, or `wp plugin auto-updates enable cinq-wp-mcp`; a site returning false from the filter can drop it.
* Breaking: no code is kept for earlier dependencies: WordPress 6.9, the MCP Adapter and ACF Pro 5.7+ or SCF are taken as they are.
* **Check again** on the Updates screen reads the new release in one click.
* No update is offered over a Git working copy.
* The version is the plugin header alone: the `VERSION` constant is removed.
* The updater reads `update.json` only, without the GitHub API fallback for releases older than 1.1.0. The plugin details read the plugin name, author and homepage from the header.

= 1.2.0 =
* Taxonomy terms are read and written, their ACF fields and block zones included: `list-terms`, `get-term`, `update-term`, and `edit-blocks` on a term.

= 1.1.0 =
* The plugin installs its updates on its own, and reads releases from the `update.json` attached to each one. **Check again** drops the cached release.
* The Connect tab walks through adding a custom connector in Claude by name and URL, and checks the connection from the server: OAuth challenge, `Authorization` header reaching PHP, discovery. Application passwords move to a collapsed alternative.
* Fixed: OAuth discovery is served from the REST API, so hosts that keep the root `/.well-known/` for themselves no longer break the connection.
* Fixed: a refresh token presented again within a minute of its rotation buys a new pair instead of revoking the whole chain.

= 1.0.2 =
* Private post types named in `settings.postTypes.include` are reachable.
* Fixed: imported files without an extension are named after their image type.

= 1.0.1 =
* Fixed: OAuth works on a site installed in a subdirectory, the server metadata being served at `{site}/.well-known/openid-configuration`.

= 1.0.0 =
* Initial release.
