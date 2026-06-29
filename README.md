<p align="center">
  <img src="assets/wp-org/librebite-logo.png" alt="Libre Bite Logo" width="200">
</p>

# Libre Bite

**Die WooCommerce-Erweiterung für Restaurants, Take-Aways, Cafés und Bars.**

[![Stable Tag](https://img.shields.io/badge/stable-2.1.0-blue.svg)](https://github.com/simurech/libre-bite/releases)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-8892be.svg)](https://php.net/)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

> Stressfreier Service, volle Teller, glückliche Gäste — Libre Bite ist das WooCommerce-Plugin für Restaurants, Take-Aways und Cafés, die ihre Bestellungen direkt und provisionsfrei abwickeln wollen. Deine Daten bleiben in deiner eigenen WordPress-Installation. Keine Plattformgebühren. Kein Vendor-Lock-in.

**→ [Plugin auf WordPress.org](https://wordpress.org/plugins/libre-bite/)** — vollständige Beschreibung, Screenshots, FAQ und Installation.

---

## Architektur

Libre Bite verwendet eine modulare, klassenbasierte Architektur. Jeder Funktionsbereich ist ein eigenes Modul, das basierend auf den Admin-Einstellungen bedingt geladen wird.

```
libre-bite/
├── libre-bite.php          # Plugin-Bootstrap, Freemius-Init
├── includes/
│   ├── core/               # Installer, Loader, Feature-Toggles, Konstanten
│   ├── admin/              # Admin-Menüs, Einstellungsseiten, Rollen
│   └── modules/            # Funktionsmodule (Kanban, POS, Standorte, Checkout …)
├── templates/
│   ├── admin/              # Admin-Views (Kanban, POS, Settings, Help)
│   └── *.php               # Frontend-Templates (Checkout, Standort-Selector)
├── assets/
│   ├── css/                # Admin- und Frontend-Stylesheets
│   └── js/                 # Kanban, POS-Oberfläche, Checkout-Skripte
└── vendor/
    └── freemius/           # Freemius SDK (Lizenz- und Abonnementverwaltung)
```

---

## Systemanforderungen

| Komponente | Minimum | Empfohlen |
|---|---|---|
| WordPress | 6.0 | Aktuellste Version |
| WooCommerce | 8.0 | Aktuellste Version |
| PHP | 8.1 | 8.2+ |
| MySQL | 5.6 | 8.0+ / MariaDB 10.4+ |

---

## Installation

**Über WordPress.org (empfohlen):**
1. Im WordPress-Dashboard zu **Plugins → Installieren** navigieren.
2. Nach „Libre Bite" suchen und installieren.

**Manuelle Installation via GitHub:**
1. Die neueste Release-`.zip` von der [Releases-Seite](https://github.com/simurech/libre-bite/releases) herunterladen.
2. In WordPress zu **Plugins → Installieren → Plugin hochladen** navigieren.

**Nach der Aktivierung:**
1. Sicherstellen, dass WooCommerce aktiv ist.
2. Im Admin-Menü zu **Libre Bite** navigieren und Standort(e) konfigurieren.

---

## Roadmap

| Feature | Pro? |
|---|---|
| Übersetzungen Französisch (fr_CH) und Italienisch (it_CH) | nein |
| Rate-Limiting für Reservierungsformular | nein |
| POS-Erstladezeit: Meta-Daten gebatcht laden | nein |
| Split-Payment – Betrag auf mehrere Zahlarten aufteilen | ja |
| Offene Tabs – laufender Tischservice-Tab im POS | ja |
| Reservierungen – Zeitfenster, Auto-Tischzuweisung, Frontend-Verfügbarkeit | ja |
| PWA – POS als installierbare App (echter Vollbildmodus) | nein |
| Offline-Modus für POS | ja |
| Automatisierte Tests (AJAX-Endpoints) | – |
| Multi-Location Phase 2 / Franchise | ja |
| Erweiterte Analytik (Heatmap, Stosszeiten, CSV-Export) | ja |
| Treuepunkte / Kundenbindung | ja |
| Barcode-Scanning im POS | ja |
| Kassenbon-Druck (Thermodrucker) | ja |
| Kunden-Display / Zweiter Bildschirm | ja |
| Liefer-Modul (Zonen, Gebühren) | ja |

---

## Lizenz

**Libre Bite** steht unter der [GNU General Public License v2.0 oder später](https://www.gnu.org/licenses/gpl-2.0.html).

**Autor:** Simon Urech — [@simurech](https://github.com/simurech)
