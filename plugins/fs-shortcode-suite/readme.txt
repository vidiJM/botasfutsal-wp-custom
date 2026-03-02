=== FS Shortcode Suite ===
Contributors: botasfutsal
Tags: shortcode, futsal, products, grid, search, ecommerce
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise shortcode suite for rendering futsal product grids, search, selector wizard, player types, and dynamic product detail components.

== Description ==

FS Shortcode Suite provides a modular, performance-oriented shortcode system for rendering:

- Product Detail (dynamic variant engine)
- Product Search (AJAX REST-driven)
- Product Grid
- Selector Wizard
- Player Types
- Size Guide

Features:

- Dynamic price aggregation
- Automatic technical rating engine
- Unified JSON-LD structured data
- Mobile-first CSS architecture
- REST API endpoints
- Cache-aware dataset building
- Admin dashboard
- No custom database tables

This plugin is designed to work with custom post types:
fs_producto, fs_variante, fs_oferta

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate through the WordPress admin
3. Configure in FS Shortcode Suite admin menu

== Uninstall ==

Upon uninstall, the plugin removes:
- Plugin options
- Transients
- Cache entries

It does NOT remove CPT data by default.

== Changelog ==

= 1.0.0 =
- Initial enterprise architecture release
- Rating engine integrated
- Structured data unified
- Mobile-first UI refactor