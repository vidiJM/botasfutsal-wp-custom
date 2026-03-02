# FS Shortcode Suite

Enterprise modular shortcode system for futsal product rendering.

## Architecture

- PSR-like structured folder architecture
- Namespaced classes
- REST controllers
- Data services layer
- Builder pattern for datasets
- No custom database tables

## Core Components

### Shortcodes
- Product_Detail
- Product_Search
- Product_Grid
- Selector_Wizard
- Player_Types
- Size_Guide

### REST Endpoints
- Search_Controller
- Product_Controller
- Grid_Controller
- Selector_Wizard_Controller

### Data Layer
- Product_Repository
- Filter_Engine
- Grid_Dataset_Builder
- Search_Service

## Rating Engine

Dynamic rating calculation based on:
- Price range
- Brand taxonomy
- Product characteristics

Rendered automatically in Product_Detail.

## Structured Data

- Product schema
- AggregateOffer
- AggregateRating

All generated dynamically from variant data.

## Performance Strategy

- Mobile-first CSS
- Lazy loading
- Eager above-the-fold images
- Dataset caching
- Reduced layout shift
- Conditional hover effects

## Uninstall Policy

Safe uninstall:
- Removes plugin options
- Removes transients
- Flushes cache

Does NOT remove product data.

## Requirements

- PHP 8.0+
- WordPress 6.0+
- Custom post types:
  - fs_producto
  - fs_variante
  - fs_oferta