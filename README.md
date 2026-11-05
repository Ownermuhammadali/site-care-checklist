# Site Care Checklist

A lightweight WordPress plugin that adds a **Site Care Checklist** widget to the WordPress dashboard.

It is written from scratch for WordPress site owners who want a simple, regular site health routine without a heavy plugin.

## What it does

The dashboard widget shows:

- A tickable site health checklist:
  - WordPress, themes and plugins are up to date
  - A recent backup exists and was tested
  - Spam comments have been reviewed
  - Old post revisions have been cleaned up
  - Broken links have been checked
  - Login and security settings were reviewed
- Live counts for updates waiting, spam comments, and stored post revisions
- The last date a backup was marked as done
- A **Clean up revisions** button that deletes stored post revisions
- A **Mark backup done today** button that records today's date
- A broken link note: this plugin does **not** scan links automatically. It reminds you to check the main menu, footer and contact page by hand.

Checklist state is stored in WordPress options. No external services, no tracking, no external dependencies.

## Install

1. Copy the `site-care-checklist` folder into `wp-content/plugins/`.
2. In WordPress, go to **Plugins** and activate **Site Care Checklist**.
3. Go to the **Dashboard**. The Site Care Checklist widget appears there.

## Use

1. Work through the checklist and tick each item when it is done.
2. Click **Save checklist**.
3. Use **Clean up revisions** when the revision count is high.
4. After running a real backup, click **Mark backup done today**.

Only users with the `manage_options` capability can save the checklist, clean up revisions, or mark a backup as done. All three actions are protected with a WordPress nonce.

## Uninstall

Deleting the plugin runs `uninstall.php`, which removes its two options:

- `scc_checklist_items`
- `scc_last_backup_date`

## Requirements

- WordPress 5.8 or later
- PHP 7.4 or later

## License

MIT. See `LICENSE`.
