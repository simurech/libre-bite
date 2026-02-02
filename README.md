# Libre Bite

**WooCommerce-Erweiterung für Gastronomie und Food-Business**

[![Beta](https://img.shields.io/badge/Status-Beta-orange.svg)](https://github.com/simurech/libre-bite)
[![Version](https://img.shields.io/badge/version-1.0.0--beta-blue.svg)](https://github.com/simurech/libre-bite)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

> **Beta-Phase:** Dieses Plugin befindet sich in aktiver Entwicklung. Alle Funktionen sind während der Beta kostenlos nutzbar.

---

## Funktionen

- **Standortverwaltung** - Mehrere Filialen mit Adressen und Öffnungszeiten verwalten.
- **Kanban-Dashboard** - Real-time Bestellübersicht mit Drag & Drop für die Küche.
- **POS/Kassensystem** - Touch-optimierte Oberfläche für Walk-in-Kunden vor Ort.
- **Zeitwahl-System** - Kunden können zwischen Sofort-Bestellung oder Vorbestellung mit Zeitslots wählen.
- **Produkt-Optionen** - Zentrale Add-on-Verwaltung für Extras und Beilagen mit individuellen Preisen.
- **Trinkgeld-System** - Flexible Prozent-Optionen oder individuelle Beträge im Checkout.
- **Nährwerte & Allergene** - Hinterlegung von EU-konformen Produktinformationen.
- **Benachrichtigungen** - Automatische E-Mail-Reminder für Kunden und Sound-Alerts bei Bestelleingang.
- **5-Rappen-Rundung** - Unterstützung für Schweizer Währungskonventionen.

---

## Systemanforderungen

| Komponente | Minimum |
|------------|---------|
| WordPress | 6.0+ |
| WooCommerce | 8.0+ |
| PHP | 7.4+ |
| MySQL | 5.6+ / MariaDB 10.0+ |

---

## Download & Installation

Die stabilen Versionen des Plugins können direkt über die GitHub **Releases** heruntergeladen werden:

👉 **[Download der neuesten Version](https://github.com/simurech/libre-bite/releases)**

1. Laden Sie die `libre-bite.zip` aus dem neuesten Release herunter.
2. Gehen Sie in Ihrem WordPress-Backend zu **Plugins → Installieren → Plugin hochladen**.
3. Aktivieren Sie das Plugin nach dem Hochladen.

---

## Module im Überblick

### Standortverwaltung
- Erfassung von Standorten mit individuellen Adressdaten.
- Definition von Öffnungszeiten pro Wochentag.
- Zuweisung von Produkten zu spezifischen Standorten.
- Dynamische Statusanzeige (Offen/Geschlossen) im Frontend.

### Kanban-Dashboard
Optimierter Workflow für die Bestellabwicklung:
1. **Eingang** - Neue Bestellungen (mit Sound-Signal).
2. **Zubereiten** - Aktive Bearbeitung in der Küche.
3. **Abholbereit** - Abgeschlossene Zubereitung, Kunde wird ggf. informiert.
4. **Abgeschlossen** - Archivierte Bestellungen.

### POS/Kassensystem
- Schnelle Bestellerfassung für den Tresen-Verkauf.
- Kategoriefilter für schnellen Zugriff auf Produkte.
- Integration der Produkt-Optionen und Add-ons.
- Direkter Abschluss ohne zwingende Kundendaten-Erfassung.

### Checkout & Zeitwahl
- Standort-Auswahl via Modal oder Inline-Element.
- Berechnung valider Zeitslots basierend auf den Öffnungszeiten und der Vorbereitungszeit.
- Optimierter Checkout-Modus für maximale Conversion.

---

## Benutzerrollen

| Rolle | Zugriff |
|-------|---------|
| **Personal** | Zugriff auf das Kanban-Dashboard, Bestellübersicht und POS. |
| **Admin** | Vollständiger Zugriff auf Standorte, Produkt-Optionen und allgemeine Einstellungen. |
| **Super-Admin** | Zugriff auf System-Debug-Tools und Feature-Toggles. |

---

## Feature-Toggles

Libre Bite ist modular aufgebaut. Funktionen können unter **Libre Bite → Feature-Toggles** individuell aktiviert werden:
- POS-System & Kanban-Board
- Optimierter Checkout & Trinkgeld-System
- 5-Rappen-Rundung (Schweiz)
- Pickup-Reminder (E-Mail)
- Nährwert- & Allergenanzeige

---

## Architektur & Kompatibilität

- **HPOS Ready:** Vollständige Unterstützung für das WooCommerce High-Performance Order Storage System.
- **Block Themes:** Nahtlose Integration in moderne WordPress Block-Themes.
- **Datenschutz:** Optionale Datenlöschung aller Plugin-Einstellungen bei Deinstallation.

---

## Roadmap

- **Tisch-Bestellung (QR-Code):** Kunden scannen einen Code am Tisch und bestellen/bezahlen direkt.
- **Tisch-Reservierung:** Verwaltung von Reservierungsslots pro Standort.

---

## Lizenz & Autor

**Lizenz:** GPL-2.0-or-later
**Autor:** Simon Urech - [@simurech](https://github.com/simurech)
