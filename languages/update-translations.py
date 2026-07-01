#!/usr/bin/env python3
"""
Übersetzungs-Update-Script für Libre Bite
Aktualisiert alle .po-Dateien und erstellt informelle Varianten.

Varianten:
  de_CH           Schweizerdeutsch, formell (Sie), ss statt ß
  de_CH_informal  Schweizerdeutsch, informell (du), ss statt ß
  de_DE           Deutsch (Deutschland), informell (du), ß
  de_DE_formal    Deutsch (Deutschland), formell (Sie), ß
  de_AT           Deutsch (Österreich), formell (Sie), ß
"""

import re
import subprocess
import shutil
import os

LANG_DIR = os.path.dirname(os.path.abspath(__file__))

# ─────────────────────────────────────────────────────────────────
# Neue / fehlende / fuzzy Strings – neutrale Basis-Übersetzungen
# (Sie-Form, ss – werden weiter unten per Variante angepasst)
# ─────────────────────────────────────────────────────────────────
NEW_STRINGS_FORMAL_SS = {
    # Dashboard-Strings
    "Loading orders...":
        "Bestellungen werden geladen...",
    "Error loading orders":
        "Fehler beim Laden der Bestellungen",
    "Error loading more orders":
        "Fehler beim Laden weiterer Bestellungen",
    "Do you really want to cancel this order?\n\nThe payment will be automatically refunded.":
        "Möchten Sie diese Bestellung wirklich stornieren?\n\nDie Zahlung wird automatisch zurückerstattet.",
    "Cancelling order...":
        "Bestellung wird storniert...",
    "Order cancelled and payment refunded":
        "Bestellung storniert und Zahlung zurückerstattet",
    "Error cancelling":
        "Fehler beim Stornieren",
    "Error cancelling order":
        "Fehler beim Stornieren der Bestellung",
    "Unknown error":
        "Unbekannter Fehler",
    "more order(s)":
        "weitere Bestellung(en)",
    # Produkt-Add-ons (bisher «Options»/«Optionen»)
    "Product Options":
        "Produkt-Add-ons",
    "Product Option":
        "Produkt-Add-on",
    "Options":
        "Add-ons",
    # Vorbestellungen-Einstellungen
    "Show Future Pre-orders":
        "Zukünftige Vorbestellungen anzeigen",
    "Show pre-orders with a pickup time further in the future than the preparation time in the Kanban board.":
        "Vorbestellungen mit einer Abholzeit, die weiter in der Zukunft liegt als die Zubereitungszeit, im Kanban-Board anzeigen.",
    "Dim Future Pre-orders":
        "Zukünftige Vorbestellungen ausgrauen",
    "Display future pre-orders dimmed (greyed out) in the Kanban board.":
        "Zukünftige Vorbestellungen ausgegraut im Kanban-Board anzeigen.",
    "Pre-orders can always be cancelled or manually processed regardless of this setting.":
        "Vorbestellungen können unabhängig von dieser Einstellung jederzeit storniert oder manuell bearbeitet werden.",
    # POS-Strings (neu aus dieser Session)
    "Error loading products":
        "Fehler beim Laden der Produkte",
    "Try again":
        "Erneut versuchen",
    "Creating order...":
        "Bestellung wird erstellt...",
    "Order #":
        "Bestellung #",
    " created":
        " erstellt",
    # Welcome/UI
    "Dismiss":
        "Schliessen",
    # Location-Limit-Notice (neu)
    "Location not published \u2013 the Free plan is limited to 1 location.":
        "Standort nicht veröffentlicht \u2013 die Gratis-Version ist auf 1 Standort begrenzt.",
    "Upgrade to Pro for unlimited locations \u2192":
        "Auf Pro upgraden für unbegrenzte Standorte \u2192",
    # Feature-Gruppen-Beschreibung (neu)
    "At least one location must always be set up (required for POS and order overview). "
    "These toggles control additional location features.":
        "Mindestens 1 Standort muss immer eingerichtet sein (wird für POS und Bestellübersicht "
        "benötigt). Diese Optionen steuern zusätzliche Standort-Funktionen.",
    # Bisher leere Strings (de_CH hatte diese als untranslated)
    "requires at least WooCommerce version 8.0. Please update WooCommerce.":
        "erfordert mindestens WooCommerce Version 8.0. Bitte aktualisieren Sie WooCommerce.",
    "If disabled, the tip selection will not be shown in checkout.":
        "Wenn deaktiviert, wird die Trinkgeld-Auswahl nicht im Checkout angezeigt.",
    "Notes about your order, e.g. special delivery instructions.":
        "Hinweise zu Ihrer Bestellung, z. B. besondere Lieferanweisungen.",
    "Custom title for the \"Billing Details\" section in checkout (optional)":
        "Benutzerdefinierter Titel für den Abschnitt «Rechnungsdetails» im Checkout (optional)",
    "When this shortcode is used, the automatic popup is disabled.":
        "Wenn dieser Shortcode verwendet wird, ist das automatische Popup deaktiviert.",
    "Print the QR code or copy the link for your table stands.":
        "Drucken Sie den QR-Code aus oder kopieren Sie den Link für Ihre Tischaufsteller.",
    "Locations must be created before orders can be accepted":
        "Standorte müssen erstellt werden, bevor Bestellungen angenommen werden können.",
    "Opening hours are automatically considered when selecting time slots":
        "Öffnungszeiten werden bei der Auswahl von Zeitslots automatisch berücksichtigt.",
    "Manage incoming orders with the Kanban board or POS system.":
        "Verwalten Sie eingehende Bestellungen mit dem Kanban-Board oder dem Kassensystem.",
    "Completed orders for today. Older orders are stored separately.":
        "Abgeschlossene Bestellungen von heute. Ältere Bestellungen werden separat gespeichert.",
    "Completed orders for today. Older orders are loaded on demand.":
        "Abgeschlossene Bestellungen von heute. Ältere Bestellungen werden auf Anfrage geladen.",
    "The sound only plays when the browser tab with the dashboard is open.":
        "Der Sound wird nur abgespielt, wenn der Browser-Tab mit dem Dashboard geöffnet ist.",
    "You can customize or disable the sound under Settings \u2192 Dashboard.":
        "Sie können den Sound unter Einstellungen \u2192 Dashboard anpassen oder deaktivieren.",
    "This way you always know exactly when to start preparing.":
        "So wissen Sie immer genau, wann Sie mit der Zubereitung beginnen können.",
    "This price is added to the product price when the option is selected.":
        "Dieser Preis wird zum Produktpreis addiert, wenn die Option ausgewählt wird.",
    "After submitting the form, two emails are automatically sent:":
        "Nach dem Absenden des Formulars werden automatisch zwei E-Mails versendet:",
    "To help you quickly, please prepare the following information:":
        "Um Ihnen schnell helfen zu können, bereiten Sie bitte folgende Informationen vor:",
    "Make sure the location is published and opening hours are configured.":
        "Stellen Sie sicher, dass der Standort veröffentlicht und die Öffnungszeiten konfiguriert sind.",
    "Check whether the option is published and assigned to the product.":
        "Prüfen Sie, ob die Option veröffentlicht und dem Produkt zugewiesen ist.",
    "For larger setups, you can create multiple tables in one step:":
        "Für grössere Setups können Sie mehrere Tische in einem Schritt erstellen:",
    "As staff, you have access to the order overview and the POS system.":
        "Als Personal haben Sie Zugriff auf die Bestellübersicht und das Kassensystem.",
    "Drag an order to the next column or click the status button":
        "Ziehen Sie eine Bestellung in die nächste Spalte oder klicken Sie den Statusbutton",
    "Disable and re-enable the plugin to recreate roles.":
        "Deaktivieren und reaktivieren Sie das Plugin, um die Rollen neu zu erstellen.",
    "Enable WP_DEBUG in wp-config.php for detailed error messages:":
        "Aktivieren Sie WP_DEBUG in der wp-config.php für detaillierte Fehlermeldungen:",
    "How often the reservations overview is updated. Default: 60 seconds.":
        "Wie oft die Reservierungsübersicht aktualisiert wird. Standard: 60 Sekunden.",
    "Please select a location first and save to generate the link.":
        "Bitte wählen Sie zuerst einen Standort und speichern Sie, um den Link zu generieren.",
    # Onboarding-Text (geänderte Formulierung)
    "Activate the features you need in the Features tab. All core features are "
    "enabled by default \u2013 you can adjust this at any time.":
        "Aktivieren Sie die ben\u00f6tigten Funktionen im Funktionen-Tab. Alle "
        "Kernfunktionen sind standardm\u00e4ssig aktiviert \u2013 Sie k\u00f6nnen "
        "dies jederzeit anpassen.",
    # Checkout-Felder (formell)
    "your@email.com":
        "ihre@email.ch",

    # ── Neu hinzugefügte Strings (174 fehlende Übersetzungen) ──────────────────

    # Allgemeine UI / Status
    "✓ Complete":
        "✓ Abgeschlossen",
    "Invalid parameters":
        "Ungültige Parameter",
    "Order completed":
        "Bestellung abgeschlossen",
    "Prepare Now →":
        "Jetzt vorbereiten →",
    '"%s" is not available at the selected location.':
        '«%s» ist am gewählten Standort nicht verfügbar.',

    # Checkout / Trinkgeld
    '"Complete Order" → choose payment method (cash, card, Twint, etc.)':
        '«Bestellung abschliessen» → Zahlungsmethode wählen (Bar, Karte, Twint, etc.)',
    '"No Tip" Label':
        'Bezeichnung für «Kein Trinkgeld»',
    'Label for the "no tip" option. Leave empty to use the default.':
        'Bezeichnung für die Option «Kein Trinkgeld». Leer lassen für die Standardbezeichnung.',
    'Offer your customers a tip option at checkout. You can define three percentages and set a default selection.':
        'Bieten Sie Ihren Kunden eine Trinkgeld-Option im Checkout an. Sie können drei Prozentsätze definieren und eine Standardauswahl festlegen.',
    'Percentage: values are percentages (e.g. 10 = 10%). Fixed: values are absolute amounts in your shop currency.':
        'Prozent: Werte sind Prozentsätze (z. B. 10 = 10 %). Fix: Werte sind Fixbeträge in Ihrer Shop-Währung.',
    'Prevents rounding errors when combining vouchers and tips. Recommended for Swiss businesses.':
        'Verhindert Rundungsfehler bei der Kombination von Gutscheinen und Trinkgeld. Empfohlen für Schweizer Betriebe.',
    'Rounds the amount to 5 centimes (0.05 CHF). Recommended for Swiss businesses to avoid rounding errors at the cash register.':
        'Rundet den Betrag auf 5 Rappen (0.05 CHF). Empfohlen für Schweizer Betriebe, um Rundungsfehler an der Kasse zu vermeiden.',

    # Checkout – Felder & Optionen
    '"Standard" shows the normal WooCommerce checkout. "Optimized" (Pro) reduces it to the essentials: only name and receipt option – ideal for take-away without delivery.':
        '«Standard» zeigt den normalen WooCommerce-Checkout. «Optimiert» (Pro) reduziert ihn auf das Wesentliche: nur Name und Belegwunsch – ideal für Take-away ohne Lieferung.',
    'Checked = field is shown in checkout. Unchecked = field is hidden. The label can optionally be overridden.':
        'Aktiviert = Feld wird im Checkout angezeigt. Deaktiviert = Feld wird ausgeblendet. Die Bezeichnung kann optional überschrieben werden.',
    'Choose which fields the customer must fill in when ordering (e.g. first name, email, phone). For take-away, often only the first name is needed.':
        'Wählen Sie, welche Felder der Kunde beim Bestellen ausfüllen muss (z. B. Vorname, E-Mail, Telefon). Für Take-away wird oft nur der Vorname benötigt.',
    'Here you can specify which fields should be displayed in checkout and customize field labels.':
        'Hier können Sie festlegen, welche Felder im Checkout angezeigt werden, und die Feldbezeichnungen anpassen.',
    'If disabled, the checkbox and shipping address fields will not be shown in checkout.':
        'Wenn deaktiviert, werden das Kontrollkästchen und die Lieferadressfelder im Checkout nicht angezeigt.',
    'If enabled, shipping information will be displayed in cart and checkout (shipping costs, shipping methods, etc.).':
        'Wenn aktiviert, werden Versandinformationen in Warenkorb und Checkout angezeigt (Versandkosten, Versandmethoden, etc.).',
    'In optimized mode, only the name is requested and whether a receipt by email is desired.':
        'Im optimierten Modus wird nur der Name abgefragt und ob ein Beleg per E-Mail gewünscht wird.',
    'Show "Order Notes" field':
        'Feld «Bestellhinweise» anzeigen',
    'Show "Ship to a different address" option':
        'Option «An andere Adresse liefern» anzeigen',
    'The Checkout tab contains two sections: field configuration and additional checkout options.':
        'Der Tab «Checkout» enthält zwei Bereiche: Feldkonfiguration und zusätzliche Checkout-Optionen.',
    'The optimized checkout only works with the classic WooCommerce shortcode. Your checkout page must contain the shortcode':
        'Der optimierte Checkout funktioniert nur mit dem klassischen WooCommerce-Shortcode. Ihre Checkout-Seite muss den Shortcode enthalten',

    # Quittung / Beleg
    'A "Send Receipt" button is available in the WooCommerce order detail view under the "Libre Bite" metabox.':
        'In der WooCommerce-Bestelldetailansicht unter der «Libre Bite»-Metabox steht ein Button «Beleg senden» zur Verfügung.',
    'After ordering, an "Email Receipt" button appears on the confirmation page (only if a valid email was entered).':
        'Nach der Bestellung erscheint auf der Bestätigungsseite ein Button «Beleg per E-Mail» (nur wenn eine gültige E-Mail-Adresse eingegeben wurde).',
    'The receipt email uses the standard WooCommerce invoice email. It can only be sent once per order to prevent duplicates.':
        'Die Beleg-E-Mail verwendet die Standard-WooCommerce-Rechnung. Sie kann pro Bestellung nur einmal versendet werden, um Duplikate zu verhindern.',
    'With the optimized checkout, customers can request a receipt by email directly on the order confirmation page. Admins can also resend receipts from the order detail view.':
        'Mit dem optimierten Checkout können Kunden auf der Bestellbestätigungsseite direkt einen Beleg per E-Mail anfordern. Admins können Belege auch aus der Bestelldetailansicht erneut versenden.',

    # Standorte / Locations
    'Activate or deactivate individual feature modules of Libre Bite. Disabled features are hidden from the menu – including the corresponding settings tabs.':
        'Aktivieren oder deaktivieren Sie einzelne Funktionsmodule von Libre Bite. Deaktivierte Funktionen werden im Menü ausgeblendet – einschliesslich der entsprechenden Einstellungs-Tabs.',
    'Automatically pre-selects the location. You can find the ID in the URL when editing the location.':
        'Wählt den Standort automatisch vor. Die ID finden Sie in der URL beim Bearbeiten des Standorts.',
    'Create locations under "Libre Bite → Locations"':
        'Standorte unter «Libre Bite → Standorte» erstellen',
    'Customers select a location at checkout. Available pickup times are automatically calculated based on opening hours.':
        'Kunden wählen im Checkout einen Standort. Die verfügbaren Abholzeiten werden automatisch anhand der Öffnungszeiten berechnet.',
    'Each location can be assigned an accent color. This is displayed as a visual highlight in the following areas:':
        'Jedem Standort kann eine Akzentfarbe zugewiesen werden. Diese wird als visuelles Highlight in den folgenden Bereichen angezeigt:',
    'Go to "Libre Bite → Locations"':
        'Gehen Sie zu «Libre Bite → Standorte»',
    'Go to "Libre Bite" → "Locations"':
        'Gehen Sie zu «Libre Bite» → «Standorte»',
    'Optional: Link to Google Maps for this location. The address will be linked on order confirmation and in emails.':
        'Optional: Link zu Google Maps für diesen Standort. Die Adresse wird auf der Bestellbestätigung und in E-Mails verlinkt.',
    'The color is set in the location editing area under "Color" with the WordPress color picker.':
        'Die Farbe wird im Bearbeitungsbereich des Standorts unter «Farbe» mit dem WordPress-Farbwähler festgelegt.',
    'The location selection is saved per user and automatically pre-selected on the next visit.':
        'Die Standortauswahl wird pro Benutzer gespeichert und beim nächsten Besuch automatisch vorausgewählt.',
    'The page where your locations are embedded with the shortcode [lbite_location_selector]. Used e.g. as a target for internal links.':
        'Die Seite, auf der Ihre Standorte mit dem Shortcode [lbite_location_selector] eingebettet sind. Wird z. B. als Ziel für interne Links verwendet.',
    'The tile layout optionally displays location images. These can be uploaded in location settings.':
        'Das Kachellayout zeigt optional Standortbilder an. Diese können in den Standorteinstellungen hochgeladen werden.',
    'Unlock multi-location management, tip options, pickup reminders and much more.':
        'Schalten Sie Multi-Standort-Verwaltung, Trinkgeld-Optionen, Abholbenachrichtigungen und vieles mehr frei.',

    # Standort-Shortcode / Selector
    'Displays a location and time selection. By default as a tile layout with two-step process (location first, then time) and automatic redirect to the shop page.':
        'Zeigt eine Standort- und Zeitauswahl an. Standardmässig als Kachellayout mit zweistufigem Prozess (zuerst Standort, dann Zeit) und automatischer Weiterleitung zur Shop-Seite.',
    'Layout: Tiles (grid cards), inline (compact form), or banner (wide 2-column with image)':
        'Layout: Kacheln (Raster-Karten), Inline (kompaktes Formular) oder Banner (breite 2-spaltige Ansicht mit Bild)',
    'Note: The legacy parameter ?location=ID (without prefix) is still supported for backward compatibility.':
        'Hinweis: Der alte Parameter ?location=ID (ohne Präfix) wird aus Gründen der Rückwärtskompatibilität weiterhin unterstützt.',
    'Pre-select a specific location (use the location ID). Leave empty to let the customer choose.':
        'Einen bestimmten Standort vorauswählen (Standort-ID verwenden). Leer lassen, damit der Kunde wählen kann.',
    'Pre-selects location 123 and "Order Now"':
        'Wählt Standort 123 vor und «Jetzt bestellen»',
    'Pre-selects location 123 and "Pre-order for Later"':
        'Wählt Standort 123 vor und «Vorbestellen»',
    'Select the page where the shortcode [lbite_location_selector] is included, or create a new page.':
        'Wählen Sie die Seite aus, auf der der Shortcode [lbite_location_selector] eingebunden ist, oder erstellen Sie eine neue Seite.',
    'Sets the order type to "Now" (fastest possible pickup time).':
        'Setzt den Bestelltyp auf «Jetzt» (schnellstmögliche Abholzeit).',
    'Sets the order type to "Pre-order" (customer chooses pickup time themselves).':
        'Setzt den Bestelltyp auf «Vorbestellung» (Kunde wählt die Abholzeit selbst).',
    'Tip: Enable the "Location Selection" feature under Settings → Features for the shortcode to be visible.':
        'Tipp: Aktivieren Sie die Funktion «Standortauswahl» unter Einstellungen → Funktionen, damit der Shortcode sichtbar ist.',
    'With the following shortcode, customers can select a location directly on a page or at checkout:':
        'Mit dem folgenden Shortcode können Kunden direkt auf einer Seite oder im Checkout einen Standort auswählen:',
    'You can also pre-select location and order type directly via URL – useful for QR codes, flyers or links.':
        'Sie können Standort und Bestelltyp auch direkt per URL vorauswählen – nützlich für QR-Codes, Flyer oder Links.',
    'You can insert the shortcode on any page (e.g. the order page or homepage). The selected location is stored in the session and used at checkout.':
        'Sie können den Shortcode auf jeder beliebigen Seite einfügen (z. B. auf der Bestellseite oder der Startseite). Der gewählte Standort wird in der Session gespeichert und im Checkout verwendet.',

    # Öffnungszeiten / Feiertage
    'Define holidays on which a location is closed or has different opening hours. Holiday settings override the regular weekly schedule for a specific date.':
        'Definieren Sie Feiertage, an denen ein Standort geschlossen ist oder abweichende Öffnungszeiten hat. Feiertagseinstellungen überschreiben den regulären Wochenplan für ein bestimmtes Datum.',
    'Define holidays on which a location is closed or has different opening hours. Holidays override the regular weekly schedule.':
        'Definieren Sie Feiertage, an denen ein Standort geschlossen ist oder abweichende Öffnungszeiten hat. Feiertage überschreiben den regulären Wochenplan.',
    'The location is open, but with different hours than usual. Enter one or two time windows (e.g. a shorter service on a public holiday).':
        'Der Standort ist geöffnet, aber mit abweichenden Zeiten. Geben Sie ein oder zwei Zeitfenster ein (z. B. kürzerer Betrieb an einem Feiertag).',
    'Two windows per day: Use both windows for locations with a midday break (e.g. 11:00–14:00 and 17:00–22:00). Leave the 2nd window empty if not needed.':
        'Zwei Fenster pro Tag: Nutzen Sie beide Fenster für Standorte mit Mittagspause (z. B. 11:00–14:00 und 17:00–22:00). Lassen Sie das 2. Fenster leer, wenn es nicht benötigt wird.',
    'Use the arrow keys ‹ and › to navigate day by day. The date field also accepts direct input. Click «Today» to return to the current day.':
        'Verwenden Sie die Pfeiltasten ‹ und › zur tagesweisen Navigation. Das Datumsfeld akzeptiert auch direkte Eingaben. Klicken Sie auf «Heute», um zum aktuellen Tag zurückzukehren.',

    # Zeitslots
    'Cut off the first N minutes from each time window. Example: with 30 minutes, a window from 11:00 only shows slots from 11:30 onwards.':
        'Sperrt die ersten N Minuten jedes Zeitfensters. Beispiel: Mit 30 Minuten zeigt ein Fenster ab 11:00 nur Slots ab 11:30.',
    'Cut off the last N minutes from each time window. Example: with 30 minutes, a window until 22:00 only shows slots up to 21:30.':
        'Sperrt die letzten N Minuten jedes Zeitfensters. Beispiel: Mit 30 Minuten zeigt ein Fenster bis 22:00 nur Slots bis 21:30.',
    'Distance between selectable pickup times. With 15 minutes, the customer sees e.g. 12:00, 12:15, 12:30, etc.':
        'Abstand zwischen wählbaren Abholzeiten. Mit 15 Minuten sieht der Kunde z. B. 12:00, 12:15, 12:30 usw.',
    'Earliest selectable pickup time for customers. A preparation time of 30 minutes means: the earliest time slot is 30 minutes from the order time. Pre-orders are also automatically moved to "Preparing" X minutes before pickup time.':
        'Frühestmögliche Abholzeit für Kunden. Eine Zubereitungszeit von 30 Minuten bedeutet: Der früheste Zeitslot liegt 30 Minuten nach dem Bestellzeitpunkt. Vorbestellungen werden zudem automatisch X Minuten vor der Abholzeit auf «In Zubereitung» gesetzt.',
    'Hides the first N minutes of each opening window. Example: 30 minutes with an opening at 11:00 → first available slot is 11:30.':
        'Blendet die ersten N Minuten jedes Öffnungsfensters aus. Beispiel: 30 Minuten bei Öffnung um 11:00 → erster verfügbarer Slot ist 11:30.',
    'Hides the last N minutes of each opening window. Example: 30 minutes with closing at 22:00 → last available slot is 21:30.':
        'Blendet die letzten N Minuten jedes Öffnungsfensters aus. Beispiel: 30 Minuten bei Schliessung um 22:00 → letzter verfügbarer Slot ist 21:30.',

    # Vorbestellungen / Pre-orders
    'Customers can select a specific pickup time at checkout – e.g. "today at 12:30". Libre Bite manages these pre-orders automatically:':
        'Kunden können im Checkout eine bestimmte Abholzeit wählen – z. B. «heute um 12:30». Libre Bite verwaltet diese Vorbestellungen automatisch:',
    'Future pre-orders are displayed with reduced opacity in the "Incoming" column.':
        'Zukünftige Vorbestellungen werden in der Spalte «Eingehend» mit reduzierter Deckkraft angezeigt.',
    'Pre-orders are automatically moved from "Incoming" to "Preparing" X minutes before pickup time.':
        'Vorbestellungen werden automatisch X Minuten vor der Abholzeit von «Eingehend» nach «In Zubereitung» verschoben.',
    'Pre-orders initially appear in "Incoming" with the pickup time shown.':
        'Vorbestellungen erscheinen zunächst in «Eingehend» mit der angezeigten Abholzeit.',
    'Status buttons are disabled until the order enters the preparation window.':
        'Statusschaltflächen sind deaktiviert, bis die Bestellung in das Vorbereitungsfenster eintritt.',
    'When disabled, future pre-orders are completely hidden from the Kanban board until they are within the preparation window.':
        'Wenn deaktiviert, werden zukünftige Vorbestellungen vollständig aus dem Kanban-Board ausgeblendet, bis sie im Vorbereitungsfenster liegen.',
    'When disabled, pre-orders far in the future are hidden from the Kanban board until they enter the preparation window. When enabled, they appear dimmed and cannot be moved.':
        'Wenn deaktiviert, werden weit in der Zukunft liegende Vorbestellungen aus dem Kanban-Board ausgeblendet, bis sie in das Vorbereitungsfenster eintreten. Wenn aktiviert, erscheinen sie ausgegraut und können nicht verschoben werden.',
    'When enabled, pre-orders with a pickup time far in the future are shown greyed out in the Kanban board. They cannot be processed until they are within the preparation window.':
        'Wenn aktiviert, werden Vorbestellungen mit einer weit in der Zukunft liegenden Abholzeit ausgegraut im Kanban-Board angezeigt. Sie können erst bearbeitet werden, wenn sie im Vorbereitungsfenster liegen.',
    'X minutes before pickup time (configurable under Settings → General), they are automatically moved to the "Preparing" column.':
        'X Minuten vor der Abholzeit (konfigurierbar unter Einstellungen → Allgemein) werden sie automatisch in die Spalte «In Zubereitung» verschoben.',
    'X minutes before the selected pickup time, the customer is automatically sent a reminder email (if enabled).':
        'X Minuten vor der gewählten Abholzeit wird dem Kunden automatisch eine Erinnerungs-E-Mail gesendet (sofern aktiviert).',

    # Kanban-Board / Bestellübersicht
    'Activate this feature under Settings → Features → "Dim Future Pre-orders". Configure visibility under Settings → Order Overview.':
        'Aktivieren Sie diese Funktion unter Einstellungen → Funktionen → «Zukünftige Vorbestellungen ausgrauen». Konfigurieren Sie die Sichtbarkeit unter Einstellungen → Bestellübersicht.',
    'Change order status via status buttons':
        'Bestellstatus über Statusschaltflächen ändern',
    'Click on an order to view all details such as products, customer name, and pickup time.':
        'Klicken Sie auf eine Bestellung, um alle Details wie Produkte, Kundenname und Abholzeit anzuzeigen.',
    'How often the order overview checks for new orders in the background (in seconds). Recommendation: 30 seconds.':
        'Wie oft die Bestellübersicht im Hintergrund auf neue Bestellungen prüft (in Sekunden). Empfehlung: 30 Sekunden.',
    'In the order overview (Kanban) you can set a filter in the top right to show only table orders or only take-away.':
        'In der Bestellübersicht (Kanban) können Sie oben rechts einen Filter setzen, um nur Tischbestellungen oder nur Take-away anzuzeigen.',
    'In the order overview and POS, the table number is displayed directly with the order.':
        'In der Bestellübersicht und im POS wird die Tischnummer direkt bei der Bestellung angezeigt.',
    'Mark as "Completed" once the order is ready for pickup.':
        'Als «Abgeschlossen» markieren, sobald die Bestellung abholbereit ist.',
    'Mark as "Completed" once the order is ready for pickup. The order moves to the completed column.':
        'Als «Abgeschlossen» markieren, sobald die Bestellung abholbereit ist. Die Bestellung wechselt in die abgeschlossene Spalte.',
    'New orders that have not yet been processed. New orders are highlighted here in color.':
        'Neue Bestellungen, die noch nicht bearbeitet wurden. Neue Bestellungen werden hier farblich hervorgehoben.',
    'Order is moved to "Preparing". For pre-orders, this happens automatically X minutes before pickup time.':
        'Bestellung wird in «In Zubereitung» verschoben. Bei Vorbestellungen geschieht dies automatisch X Minuten vor der Abholzeit.',
    'Orders currently being prepared. Pre-orders are automatically placed here shortly before the pickup time.':
        'Bestellungen, die derzeit zubereitet werden. Vorbestellungen werden kurz vor der Abholzeit automatisch hierher verschoben.',
    'The Kanban board is your real-time overview of all active orders. It has three columns: Incoming, Preparing, and Completed.':
        'Das Kanban-Board ist Ihre Echtzeit-Übersicht aller aktiven Bestellungen. Es hat drei Spalten: Eingehend, In Zubereitung und Abgeschlossen.',
    'The order immediately appears in the "Incoming" column of the order overview. Optionally a sound plays.':
        'Die Bestellung erscheint sofort in der Spalte «Eingehend» der Bestellübersicht. Optional wird ein Sound abgespielt.',
    'The order immediately appears in the Kanban board under "Incoming"':
        'Die Bestellung erscheint sofort im Kanban-Board unter «Eingehend»',
    'Tip: The dashboard updates automatically. You do not need to manually reload the page to see new orders. The interval can be adjusted under Settings → Order Overview.':
        'Tipp: Das Dashboard aktualisiert sich automatisch. Sie müssen die Seite nicht manuell neu laden, um neue Bestellungen zu sehen. Das Intervall kann unter Einstellungen → Bestellübersicht angepasst werden.',
    'Libre Bite adds a complete order management system for food service businesses to your WooCommerce store. New orders appear automatically in the order overview – no need to manually search WooCommerce orders.':
        'Libre Bite ergänzt Ihren WooCommerce-Shop um ein vollständiges Bestellmanagementsystem für Gastronomie. Neue Bestellungen erscheinen automatisch in der Bestellübersicht – kein manuelles Suchen in WooCommerce-Bestellungen nötig.',

    # POS
    '"Complete Order" → choose payment method (cash, card, Twint, etc.)':
        '«Bestellung abschliessen» → Zahlungsmethode wählen (Bar, Karte, Twint, etc.)',
    'Categories are shown as filter buttons in the POS (e.g. "Burgers", "Drinks", "Desserts").':
        'Kategorien werden als Filterschaltflächen im POS angezeigt (z. B. «Burger», «Getränke», «Desserts»).',
    'Check in the settings whether sound notifications are enabled. Make sure the browser allows sound playback.':
        'Prüfen Sie in den Einstellungen, ob Sound-Benachrichtigungen aktiviert sind. Stellen Sie sicher, dass der Browser die Soundwiedergabe erlaubt.',
    'Choose which payment methods are displayed in the POS payment modal and customize the labels.':
        'Wählen Sie, welche Zahlungsmethoden im POS-Zahlungsmodal angezeigt werden, und passen Sie die Bezeichnungen an.',
    'Clear cart: "Clear Cart" button':
        'Warenkorb leeren: Schaltfläche «Warenkorb leeren»',
    'Click "Complete Order"':
        '«Bestellung abschliessen» klicken',
    'Define which payment methods are shown in the POS payment modal (cash, card, Twint, other). You can disable payment methods and customize their labels.':
        'Definieren Sie, welche Zahlungsmethoden im POS-Zahlungsmodal angezeigt werden (Bar, Karte, Twint, Sonstiges). Sie können Zahlungsmethoden deaktivieren und deren Bezeichnungen anpassen.',
    'Product images appear in the POS system – ideal for quickly finding the right items.':
        'Produktbilder erscheinen im POS-System – ideal zum schnellen Auffinden der richtigen Artikel.',
    'The POS system allows orders to be placed directly at the counter – e.g. for walk-in customers, phone orders, or table orders.':
        'Das POS-System ermöglicht die direkte Aufnahme von Bestellungen an der Theke – z. B. für Laufkundschaft, Telefonbestellungen oder Tischbestellungen.',
    'Tip: The POS system is optimized for use on tablets or a second monitor – allowing staff to work independently from the dashboard.':
        'Tipp: Das POS-System ist für die Nutzung auf Tablets oder einem zweiten Monitor optimiert – so kann das Personal unabhängig vom Dashboard arbeiten.',

    # Produkte / Optionen
    'All products you manage in WooCommerce are automatically available in Libre Bite – in the online shop, in the POS system, and at checkout.':
        'Alle Produkte, die Sie in WooCommerce verwalten, sind automatisch in Libre Bite verfügbar – im Onlineshop, im POS-System und im Checkout.',
    'Create product options under "Libre Bite → Product Options"':
        'Produkt-Add-ons unter «Libre Bite → Produkt-Add-ons» erstellen',
    'E.g. "100g" or "1 serving (250g)"':
        'Z. B. «100g» oder «1 Portion (250g)»',
    'Enter a name, e.g. "Extra Cheese"':
        'Name eingeben, z. B. «Extra Käse»',
    'Go to "Libre Bite" → "Product Options"':
        'Gehen Sie zu «Libre Bite» → «Produkt-Add-ons»',
    'Libre Bite builds directly on your WooCommerce products. You do not need to create new products – you simply extend the existing ones with food service-specific features like product options (add-ons).':
        'Libre Bite baut direkt auf Ihren WooCommerce-Produkten auf. Sie müssen keine neuen Produkte erstellen – Sie erweitern die bestehenden einfach um gastrospezifische Funktionen wie Produkt-Add-ons.',
    'One product, one price – e.g. "Hamburger CHF 14.50".':
        'Ein Produkt, ein Preis – z. B. «Hamburger CHF 14.50».',
    'Optional: Customize checkout fields under "Checkout Fields"':
        'Optional: Checkout-Felder unter «Checkout-Felder» anpassen',
    'Optionally enter a surcharge, e.g. "+0.50"':
        'Optional einen Aufpreis eingeben, z. B. «+0.50»',
    'Product options are additional choices customers can make when ordering – e.g. "Extra Cheese", "Sauce", "no onions". They appear directly on the product in the shop and are transmitted with the order.':
        'Produkt-Add-ons sind zusätzliche Auswahlmöglichkeiten, die Kunden beim Bestellen treffen können – z. B. «Extra Käse», «Sauce», «keine Zwiebeln». Sie erscheinen direkt beim Produkt im Shop und werden mit der Bestellung übermittelt.',
    'Products with variants – e.g. "Pizza" in sizes S, M, L with different prices.':
        'Produkte mit Varianten – z. B. «Pizza» in den Grössen S, M, L mit unterschiedlichen Preisen.',
    'Scroll to the section "Libre Bite Product Options"':
        'Zum Abschnitt «Libre Bite Produkt-Add-ons» scrollen',
    'Tip: You can assign the same option to multiple products. If you want to offer "Extra Cheese" for all burger products, create the option once and then assign it to each burger.':
        'Tipp: Sie können dieselbe Option mehreren Produkten zuweisen. Wenn Sie «Extra Käse» für alle Burger-Produkte anbieten möchten, erstellen Sie die Option einmal und weisen Sie sie dann jedem Burger zu.',

    # Tischverwaltung / Tables
    'As soon as a table is assigned to a location and saved, the QR code is automatically generated.':
        'Sobald ein Tisch einem Standort zugewiesen und gespeichert wurde, wird der QR-Code automatisch generiert.',
    'Click "Create Tables"':
        '«Tische erstellen» klicken',
    'Click "Download QR Code" – the PNG image can be printed directly or inserted into table stands.':
        '«QR-Code herunterladen» klicken – das PNG-Bild kann direkt gedruckt oder in Tischaufsteller eingefügt werden.',
    'Click on "Create"':
        '«Erstellen» klicken',
    'Click on "Save Positions"':
        '«Positionen speichern» klicken',
    'Create tables under "Libre Bite → Tables".':
        'Tische unter «Libre Bite → Tische» erstellen.',
    'Create tables, define seats, generate QR codes and enable orders directly at the table':
        'Tische erstellen, Plätze definieren, QR-Codes generieren und Bestellungen direkt am Tisch ermöglichen',
    'Drag tables to the desired position. Click on a table to see the current order.':
        'Tische an die gewünschte Position ziehen. Auf einen Tisch klicken, um die aktuelle Bestellung zu sehen.',
    'Enter a name, e.g. "Table 1" or "Terrace A"':
        'Name eingeben, z. B. «Tisch 1» oder «Terrasse A»',
    'Go to "Libre Bite" → "Table Plan"':
        'Gehen Sie zu «Libre Bite» → «Tischplan»',
    'Go to "Libre Bite" → "Tables"':
        'Gehen Sie zu «Libre Bite» → «Tische»',
    'Go to "Libre Bite" → "Tables" → "Create Multiple Tables"':
        'Gehen Sie zu «Libre Bite» → «Tische» → «Mehrere Tische erstellen»',
    '"Print QR Code" opens a print-optimized view with the table name.':
        '«QR-Code drucken» öffnet eine druckoptimierte Ansicht mit dem Tischnamen.',
    'Prefix for the table name, e.g. "Table" → "Table 1", "Table 2"…':
        'Präfix für den Tischnamen, z. B. «Tisch» → «Tisch 1», «Tisch 2» …',
    'QR-code-based table ordering: guests scan and order directly – without address or pickup time fields.':
        'QR-Code-basierte Tischbestellung: Gäste scannen und bestellen direkt – ohne Adress- oder Abholzeitfelder.',
    'Select location and prefix (e.g. "Table")':
        'Standort und Präfix auswählen (z. B. «Tisch»)',
    'The number after "post=" is the location ID':
        'Die Zahl nach «post=» ist die Standort-ID',
    'The table plan shows all tables of a location as freely positionable tiles on a canvas. It serves both for maintaining the floor plan and as a live overview of table occupancy.':
        'Der Tischplan zeigt alle Tische eines Standorts als frei positionierbare Kacheln auf einem Canvas. Er dient sowohl zur Pflege des Grundrisses als auch als Live-Übersicht der Tischbelegung.',
    'Tip: The QR link contains the location ID and table ID. When scanned, both are automatically saved in the session and used at checkout.':
        'Tipp: Der QR-Link enthält die Standort-ID und die Tisch-ID. Beim Scannen werden beide automatisch in der Session gespeichert und im Checkout verwendet.',
    'With table management you can create tables for your locations and generate QR codes for contactless ordering.':
        'Mit der Tischverwaltung können Sie Tische für Ihre Standorte erstellen und QR-Codes für kontaktloses Bestellen generieren.',
    'With table management, guests can order directly at the table – they simply scan the QR code and are taken directly to checkout with the location and table pre-filled. Address and pickup time fields are automatically hidden.':
        'Mit der Tischverwaltung können Gäste direkt am Tisch bestellen – sie scannen einfach den QR-Code und gelangen direkt zum Checkout mit vorausgefülltem Standort und Tisch. Adress- und Abholzeitfelder werden automatisch ausgeblendet.',

    # Tischplan / Live-Übersicht
    'As soon as a location is selected, the tables automatically color according to the current order status:':
        'Sobald ein Standort ausgewählt ist, färben sich die Tische automatisch entsprechend dem aktuellen Bestellstatus.',
    'Click on an occupied table to see the order number, time, item count and total. Clicking "Show in Order Overview" opens the Kanban board. The status updates automatically every 30 seconds.':
        'Auf einen belegten Tisch klicken, um Bestellnummer, Uhrzeit, Artikelanzahl und Gesamtbetrag zu sehen. «In Bestellübersicht anzeigen» öffnet das Kanban-Board. Der Status aktualisiert sich automatisch alle 30 Sekunden.',

    # Reservierungen
    'Click the colored status badge of a reservation to advance its status (Pending → Confirmed → Completed → Cancelled).':
        'Auf das farbige Statusabzeichen einer Reservierung klicken, um den Status weiterzuschalten (Ausstehend → Bestätigt → Abgeschlossen → Storniert).',
    'Contains all reservation details and the note that the request still needs to be confirmed.':
        'Enthält alle Reservierungsdetails und den Hinweis, dass die Anfrage noch bestätigt werden muss.',
    'Customers can reserve tables online – frontend form via shortcode [lbite_reservation_form]':
        'Kunden können Tische online reservieren – Frontend-Formular via Shortcode [lbite_reservation_form]',
    'Embeds a reservation request form on any page. Guests can submit a reservation inquiry which is managed in the reservation board.':
        'Bettet ein Reservierungsanfrageformular auf einer beliebigen Seite ein. Gäste können eine Reservierungsanfrage einreichen, die im Reservierungsboard verwaltet wird.',
    'Open a reservation and enter the desired table in the «Table» field. This assignment is only visible internally and is used for planning in the restaurant.':
        'Eine Reservierung öffnen und den gewünschten Tisch im Feld «Tisch» eingeben. Diese Zuweisung ist nur intern sichtbar und dient der Planung im Restaurant.',
    'Reservations are managed under Libre Bite → Reservations. Two emails are sent automatically: one to the guest (confirmation) and one to the admin (notification).':
        'Reservierungen werden unter Libre Bite → Reservierungen verwaltet. Zwei E-Mails werden automatisch versendet: eine an den Gast (Bestätigung) und eine an den Admin (Benachrichtigung).',
    'Select the desired table from the dropdown on a reservation card. Only tables of the currently selected location are shown.':
        'Gewünschten Tisch aus dem Dropdown auf einer Reservierungskarte auswählen. Es werden nur Tische des aktuell gewählten Standorts angezeigt.',
    'The reservation module allows guests to submit a table request via a frontend form. Each request is created as an entry in the backend and confirmed by email to the guest and admin.':
        'Das Reservierungsmodul ermöglicht es Gästen, eine Tischanfrage über ein Frontend-Formular einzureichen. Jede Anfrage wird als Eintrag im Backend erstellt und per E-Mail an den Gast und den Admin bestätigt.',
    'You can change the status directly in the detail view of a reservation. Filter the list by location or status to keep a quick overview.':
        'Den Status direkt in der Detailansicht einer Reservierung ändern. Die Liste nach Standort oder Status filtern, um einen schnellen Überblick zu behalten.',
    'Your reservation request has been received. We will contact you shortly to confirm the reservation.':
        'Ihre Reservierungsanfrage ist eingegangen. Wir werden uns in Kürze bei Ihnen melden, um die Reservierung zu bestätigen.',
    'Your reservation request has been submitted successfully. We will contact you soon.':
        'Ihre Reservierungsanfrage wurde erfolgreich übermittelt. Wir melden uns in Kürze bei Ihnen.',
    'Your reservation request has been submitted successfully. We will get back to you shortly to confirm the reservation.':
        'Ihre Reservierungsanfrage wurde erfolgreich übermittelt. Wir melden uns in Kürze bei Ihnen, um die Reservierung zu bestätigen.',

    # Rollen & Benutzer
    'As a Super Admin, you have full access to all Libre Bite features and settings.':
        'Als Super Admin haben Sie vollen Zugriff auf alle Libre Bite-Funktionen und -Einstellungen.',
    'As an administrator you can manage orders, configure products, set up locations and customize settings.':
        'Als Administrator können Sie Bestellungen verwalten, Produkte konfigurieren, Standorte einrichten und Einstellungen anpassen.',
    'Choose the menu items that should be hidden for users with the role "%s".':
        'Wählen Sie die Menüpunkte aus, die für Benutzer mit der Rolle «%s» ausgeblendet werden sollen.',
    'Choose which menu items should be hidden for each user role. Administrators always have full access.':
        'Wählen Sie aus, welche Menüpunkte für jede Benutzerrolle ausgeblendet werden sollen. Administratoren haben immer vollen Zugriff.',
    'Choose which standard roles are allowed to access the plugin. Enabled roles receive the same access as Libre Bite Personal (Order Overview, POS).':
        'Wählen Sie, welche Standardrollen auf das Plugin zugreifen dürfen. Aktivierte Rollen erhalten denselben Zugriff wie Libre Bite Personal (Bestellübersicht, POS).',
    'Customize the displayed names of user roles in the backend or completely disable unused roles.':
        'Passen Sie die angezeigten Namen der Benutzerrollen im Backend an oder deaktivieren Sie nicht benötigte Rollen vollständig.',
    'Go to "Users" → "All Users"':
        'Gehen Sie zu «Benutzer» → «Alle Benutzer»',
    'Select the desired POS role under "Role"':
        'Gewünschte POS-Rolle unter «Rolle» auswählen',

    # Einstellungen allgemein
    'Activate or deactivate individual feature modules of Libre Bite. Disabled features are hidden from the menu – including the corresponding settings tabs.':
        'Aktivieren oder deaktivieren Sie einzelne Funktionsmodule von Libre Bite. Deaktivierte Funktionen werden im Menü ausgeblendet – einschliesslich der entsprechenden Einstellungs-Tabs.',
    'Customize the logo, colors and name of the plugin in the backend – so the system matches your business.':
        'Passen Sie Logo, Farben und Namen des Plugins im Backend an – so passt das System zu Ihrem Betrieb.',
    'Override the displayed name of the plugin in the backend menu and on pages. Leave empty for the default "Libre Bite".':
        'Den angezeigten Namen des Plugins im Backend-Menü und auf Seiten überschreiben. Leer lassen für den Standard «Libre Bite».',
    'The settings are divided into separate tabs – organized by function. Tabs for disabled features are automatically hidden.':
        'Die Einstellungen sind in separate Tabs unterteilt – nach Funktion geordnet. Tabs für deaktivierte Funktionen werden automatisch ausgeblendet.',
    'These settings are only visible to administrators and allow advanced customizations.':
        'Diese Einstellungen sind nur für Administratoren sichtbar und ermöglichen erweiterte Anpassungen.',
    'This option will permanently delete all locations, product options, settings, and order metadata!':
        'Diese Option löscht dauerhaft alle Standorte, Produkt-Add-ons, Einstellungen und Bestellmetadaten!',
    'Warning: To delete all data, the plugin must be deleted via the WordPress interface (not just deactivated).':
        'Warnung: Um alle Daten zu löschen, muss das Plugin über die WordPress-Oberfläche gelöscht werden (nicht nur deaktiviert).',
    'With feature toggles, you can enable or disable individual Libre Bite features.':
        'Mit den Funktions-Toggles können Sie einzelne Libre Bite-Funktionen aktivieren oder deaktivieren.',

    # Staff-Standort-Fixierung
    'Location is fixed for your account':
        'Der Standort ist für Ihr Konto gesperrt.',
    'Assigned Location':
        'Zugewiesener Standort',
    'No fixed location (user can select freely)':
        'Kein fixer Standort (Benutzer kann frei wählen)',
    'When set, this location is pre-selected in POS and Order Overview and cannot be changed by the user.':
        'Wenn gesetzt, wird dieser Standort in POS und Bestellübersicht vorausgewählt und kann vom Benutzer nicht geändert werden.',

    # Sound
    'As soon as a new order arrives, the dashboard automatically plays an alert sound – so you never miss an order, even if you are not looking at the screen.':
        'Sobald eine neue Bestellung eingeht, spielt das Dashboard automatisch einen Alarmton ab – damit Sie keine Bestellung verpassen, auch wenn Sie nicht auf den Bildschirm schauen.',
    'Default sound is available. You can also select your own sound from the media library.':
        'Standard-Sound ist verfügbar. Sie können auch einen eigenen Sound aus der Mediathek auswählen.',
    'Some browsers only allow sounds after an interaction (e.g. clicking on the page once).':
        'Einige Browser erlauben Sounds erst nach einer Interaktion (z. B. einmaligem Klicken auf die Seite).',
    'The sound played when new orders arrive. You can choose a custom sound from the media library or use the default sound.':
        'Der Sound, der bei neuen Bestellungen abgespielt wird. Sie können einen eigenen Sound aus der Mediathek wählen oder den Standard-Sound verwenden.',

    # ── v1.5.0: Manager-Rolle (F13) ───────────────────────────────────────────
    'Manager Assignments':
        'Manager-Zuweisungen',
    '+ Location Settings (assigned locations only), Statistics':
        '+ Standort-Einstellungen (nur zugewiesene Standorte), Statistik',
    '+ All Settings, Roles, Support, Debug':
        '+ Alle Einstellungen, Rollen, Support, Debug',
    'View Statistics':
        'Statistik ansehen',
    'Location Manager':
        'Standort-Manager',
    'Administrator':
        'Administrator',
    'No managers found.':
        'Keine Manager gefunden.',
    'Hold Ctrl/Cmd to select multiple locations.':
        'Strg/Cmd gedrückt halten, um mehrere Standorte auszuwählen.',
    'Save Assignments':
        'Zuweisungen speichern',

    # ── v1.5.0: WC-Bestellliste Standort-Spalte + Filter (F14) ───────────────
    'Location Column in WooCommerce Order List':
        'Standort-Spalte in der WooCommerce-Bestellliste',
    'Libre Bite adds a "Location" column to the WooCommerce order list and a filter dropdown so you can quickly find orders for a specific location.':
        'Libre Bite fügt der WooCommerce-Bestellliste eine Spalte «Standort» und ein Filter-Dropdown hinzu, damit Sie Bestellungen für einen bestimmten Standort schnell finden.',
    'The column shows the location name for every order placed via Libre Bite.':
        'Die Spalte zeigt den Standortnamen für jede über Libre Bite aufgegebene Bestellung.',
    'Use the location filter at the top of the order list to narrow results to one location.':
        'Verwenden Sie den Standort-Filter oben in der Bestellliste, um die Ergebnisse auf einen Standort einzuschränken.',
    'Open WooCommerce Orders':
        'WooCommerce-Bestellungen öffnen',

    # ── v1.5.0: Statistik-Seite (F15) ────────────────────────────────────────
    'Statistics':
        'Statistik',
    'Open Statistics':
        'Statistik öffnen',
    'The Statistics page shows revenue, order count, and average order value – filterable by period (today, 7 days, 30 days, year) and broken down per location.':
        'Die Statistikseite zeigt Umsatz, Bestellanzahl und Ø-Bestellwert – filterbar nach Zeitraum (heute, 7 Tage, 30 Tage, Jahr) und aufgeschlüsselt nach Standort.',
    'Last 30 Days':
        'Letzte 30 Tage',
    'This Year':
        'Dieses Jahr',
    'Revenue':
        'Umsatz',
    'Avg. Order Value':
        'Ø Bestellwert',
    'No orders found for this period.':
        'Keine Bestellungen für diesen Zeitraum gefunden.',

    # ── v1.5.0: Branding-Ausbau (F16) ────────────────────────────────────────
    'Classic':
        'Klassisch',
    'Modern':
        'Modern',
    'Dark':
        'Dunkel',
    'Summer':
        'Sommer',
    'Ocean':
        'Ozean',
    'Forest':
        'Wald',
    'Click a preset to apply the colors. Save to keep the changes.':
        'Klicken Sie auf ein Preset, um die Farben zu übernehmen. Speichern, um die Änderungen zu behalten.',
    'Secondary text and links':
        'Sekundärtext und Links',
    'Live Preview':
        'Live-Vorschau',
    'Choose Logo':
        'Logo wählen',
    'Use Logo':
        'Logo verwenden',
    'Color Presets':
        'Farbpresets',
    'Primary Color':
        'Primärfarbe',
    'Main color for buttons and important elements.':
        'Hauptfarbe für Schaltflächen und wichtige Elemente.',
    'Secondary Color':
        'Sekundärfarbe',
    'For texts and secondary elements.':
        'Für Texte und sekundäre Elemente.',
    'Accent Color':
        'Akzentfarbe',
    'For success and confirmation elements.':
        'Für Erfolgs- und Bestätigungselemente.',
    'Inherit from Theme':
        'Vom Theme übernehmen',
    'Inherit Colors from Theme':
        'Farben vom Theme übernehmen',
    'Attempts to inherit colors from your active theme.':
        'Versucht, Farben vom aktiven Theme zu übernehmen.',
    'Order Now':
        'Jetzt bestellen',
    'Confirm':
        'Bestätigen',
    'Colors inherited!':
        'Farben übernommen!',
    'Could not find colors from theme.':
        'Keine Farben vom Theme gefunden.',
    'Error retrieving theme colors.':
        'Fehler beim Abrufen der Theme-Farben.',

    # ── v1.5.0: Tab-Router / POS-Master-Toggle ────────────────────────────────
    'Enable the Point of Sale interface for in-person orders.':
        'Kassensystem für Vor-Ort-Bestellungen aktivieren.',
    'Open Settings':
        'Einstellungen öffnen',

    # ── v1.5.0: Hilfe-Inhalte ────────────────────────────────────────────────
    'The settings are divided into tabs – one per functional area. Each tab starts with a toggle to enable or disable the feature. Pro features are marked and require an active license.':
        'Die Einstellungen sind in Tabs unterteilt – einen pro Funktionsbereich. Jeder Tab beginnt mit einem Schalter zum Aktivieren oder Deaktivieren der Funktion. Pro-Funktionen sind gekennzeichnet und erfordern eine aktive Lizenz.',
    'Tip: Enable the "Location Selection" feature under Settings → Locations for the shortcode to be visible.':
        'Tipp: Aktivieren Sie die Funktion «Standortauswahl» unter Einstellungen → Standorte, damit der Shortcode sichtbar ist.',
    'Tip: The dashboard updates automatically. You do not need to manually reload the page to see new orders. The interval can be adjusted under Settings → Orders.':
        'Tipp: Das Dashboard aktualisiert sich automatisch. Sie müssen die Seite nicht manuell neu laden, um neue Bestellungen zu sehen. Das Intervall kann unter Einstellungen → Bestellungen angepasst werden.',
    'Activate this feature under Settings → Orders → "Dim Future Pre-orders". Configure visibility under Settings → Orders.':
        'Aktivieren Sie diese Funktion unter Einstellungen → Bestellungen → «Zukünftige Vorbestellungen ausgrauen». Sichtbarkeit konfigurieren unter Einstellungen → Bestellungen.',
    'Features are now enabled or disabled directly within each thematic settings tab. There is no longer a separate "Features" tab – each functional area (Orders, POS, Checkout, etc.) begins with its own master toggle.':
        'Funktionen werden jetzt direkt in jedem thematischen Einstellungs-Tab aktiviert oder deaktiviert. Es gibt keinen separaten «Funktionen»-Tab mehr – jeder Funktionsbereich (Bestellungen, Kassensystem, Checkout etc.) beginnt mit seinem eigenen Master-Schalter.',
    'Manage the three user levels: Staff, Manager, and Administrator.':
        'Die drei Benutzerebenen verwalten: Personal, Manager und Administrator.',

    # ── v1.5.0: Freemius Trial-Badge ─────────────────────────────────────────
    '7-day free trial · No credit card required':
        '7-Tage-Gratis-Testphase · Keine Kreditkarte erforderlich',

    # ── v1.5.0: Standort-Fix (de_DE hatte "Location is fixed for your account") ─
    'Location is fixed for your account':
        'Standort ist für Ihr Konto festgelegt',

    # ── v1.5.0: Manager-Zuweisung Beschreibungstexte ──────────────────────────
    'Assign managers to specific locations. Managers can view and manage orders '
    'only for their assigned locations.':
        'Manager bestimmten Standorten zuweisen. Manager können Bestellungen '
        'nur für ihre zugewiesenen Standorte einsehen und verwalten.',
    'Assign one or more locations to each manager. Managers can only see and '
    'manage orders for their assigned locations.':
        'Jedem Manager einen oder mehrere Standorte zuweisen. Manager können '
        'Bestellungen nur für ihre zugewiesenen Standorte einsehen und verwalten.',
    'No manager users found. Create a user and assign the "Libre Bite Manager" '
    'role to get started.':
        'Keine Manager-Benutzer gefunden. Erstellen Sie einen Benutzer und '
        'weisen Sie die Rolle «Libre Bite Manager» zu, um zu beginnen.',

    # ── v1.5.0: Tab-Beschreibungen (Master-Toggle-Partials) ───────────────────
    'Replace WooCommerce fields with a minimal checkout: name and receipt option '
    'only.':
        'WooCommerce-Felder durch einen minimalen Checkout ersetzen: '
        'nur Name und Belegwunsch.',
    'This feature is available in Libre Bite Pro. Start your free 7-day trial to '
    'unlock it – no credit card required.':
        'Diese Funktion ist in Libre Bite Pro verfügbar. Starten Sie Ihre '
        'kostenlose 7-Tage-Testphase, um sie freizuschalten – '
        'keine Kreditkarte erforderlich.',
    'Show a location selector in the frontend so customers can choose their '
    'pickup location.':
        'Einen Standortwahlbereich im Frontend anzeigen, damit Kunden '
        'ihren Abholstandort wählen können.',
    'Locations are managed as individual entries. Each location has its own '
    'opening hours, timeslots, and holidays.':
        'Standorte werden als einzelne Einträge verwaltet. Jeder Standort '
        'hat eigene Öffnungszeiten, Zeitslots und Feiertage.',
    'Allow customers to customize products with add-ons, variants, or extras.':
        'Kunden erlauben, Produkte mit Add-ons, Varianten oder Extras anzupassen.',

    # ── v1.5.0: Fuzzy-Fixes (falsche Auto-Matches von msgmerge) ──────────────
    'Add New User':
        'Neuen Benutzer hinzufügen',
    'No locations found.':
        'Keine Standorte gefunden.',
    'Edit Assigned Location Settings':
        'Zugewiesene Standort-Einstellungen bearbeiten',
    'Configure each area of the plugin using the tabs below. Core features are '
    'active by default – you can adjust them at any time.':
        'Konfigurieren Sie jeden Bereich des Plugins mit den Tabs unten. '
        'Kernfunktionen sind standardmässig aktiv – Sie können dies jederzeit anpassen.',
    'Create tables, generate QR codes, and allow guests to order directly at '
    'the table. Available with Libre Bite Pro.':
        'Tische erstellen, QR-Codes generieren und Gästen direkt am Tisch '
        'bestellen ermöglichen. Verfügbar mit Libre Bite Pro.',
    'Let customers reserve tables online via a frontend form. Available with '
    'Libre Bite Pro.':
        'Kunden ermöglichen, Tische über ein Frontend-Formular online zu reservieren. '
        'Verfügbar mit Libre Bite Pro.',
    'Price Rounding':
        'Preisrundung',
    'Round total to 5 cents (0.05 CHF). Prevents rounding errors when combining '
    'vouchers and tips. Recommended for Swiss businesses.':
        'Gesamtbetrag auf 5 Rappen (0.05 CHF) runden. Verhindert Rundungsfehler '
        'bei der Kombination von Gutscheinen und Trinkgeld. Empfohlen für Schweizer Betriebe.',
    'Display incoming orders as a kanban board for quick status management.':
        'Eingehende Bestellungen als Kanban-Board anzeigen für schnelle Statusverwaltung.',
    'Pro Feature':
        'Pro-Funktion',
    'Play a sound when a new order arrives in the order overview.':
        'Einen Sound abspielen, wenn eine neue Bestellung in der Bestellübersicht eintrifft.',
    'Manage Product Options':
        'Produkt-Add-ons verwalten',
    'Show Nutritional Information':
        'Nährwertangaben anzeigen',
    'Display allergen information on product pages.':
        'Allergen-Informationen auf Produktseiten anzeigen.',
    'Last 7 Days':
        'Letzte 7 Tage',
    'No location':
        'Kein Standort',
    'No orders found for the selected period.':
        'Keine Bestellungen im gewählten Zeitraum gefunden.',

    # ── v1.5.1: Rollen-Tab + Branding + Locations ───────────────────────────────
    'Access for User Roles':
        'Zugriff nach Benutzerrollen',
    'Overview of access levels. Standard roles can be granted Order Overview and POS access.':
        'Übersicht der Zugriffsebenen. Standardrollen können Zugriff auf Bestellübersicht und Kassensystem erhalten.',
    'Full access (same as Administrator)':
        'Voller Zugriff (wie Administrator)',
    'Grant access (Order Overview + POS)':
        'Zugriff erteilen (Bestellübersicht + Kassensystem)',
    'Customize the displayed names of Libre Bite roles or disable unused ones.':
        'Angezeigte Namen der Libre Bite-Rollen anpassen oder nicht benötigte Rollen deaktivieren.',
    'Staff users always have access to: Order Overview, POS System, Help & Support. Menu visibility cannot be customized for this role.':
        'Personal hat immer Zugriff auf: Bestellübersicht, Kassensystem, Hilfe & Support. Die Menüsichtbarkeit kann für diese Rolle nicht angepasst werden.',
    'For Staff users, location assignment is done via the individual user profile page.':
        'Für Personal-Benutzer erfolgt die Standortzuweisung über die individuelle Benutzerprofilseite.',
    'Manage Staff users':
        'Personal-Benutzer verwalten',
    'Plugin Identity':
        'Plugin-Identität',
    'Plugin Name':
        'Plugin-Name',
    'Replaces "Libre Bite" throughout the admin menu and pages. Leave empty to use the default name.':
        'Ersetzt «Libre Bite» im Backend-Menü und auf Seiten. Leer lassen, um den Standardnamen zu verwenden.',
    'These are global defaults. Individual locations can override preparation time and slot buffers via their location settings.':
        'Dies sind globale Standardwerte. Einzelne Standorte können Zubereitungszeit und Slot-Buffer in ihren Standort-Einstellungen überschreiben.',

    # ── Fuzzy-Bestätigungen (gute Übersetzungen, nur Wortlaut leicht geändert) ─
    'Table Management & Ordering':
        'Tischverwaltung & Tischbestellung',
    'Allow customers to add a tip at checkout.':
        'Kunden erlauben, beim Checkout ein Trinkgeld hinzuzufügen.',
    'Order Overview (Kanban)':
        'Bestellübersicht (Kanban)',
    'Send an automatic email reminder before the scheduled pickup time.':
        'Eine automatische E-Mail-Erinnerung vor der geplanten Abholzeit senden.',
    'Display calorie counts and nutritional values on product pages.':
        'Kalorienanzahl und Nährwerte auf Produktseiten anzeigen.',
    'Show Allergen Warnings':
        'Allergen-Warnungen anzeigen',
    'Shop Manager':
        'Shop-Manager',
    'Manage pickup locations, opening hours, and timeslots.':
        'Abholstandorte, Öffnungszeiten und Zeitfenster verwalten.',
    'Manage tables and generate QR codes for table ordering.':
        'Tische verwalten und QR-Codes für Tischbestellungen generieren.',
    'View and manage table reservations.':
        'Tischreservierungen anzeigen und verwalten.',
    'Account':
        'Konto',
    'Open Account Dashboard':
        'Konto-Dashboard öffnen',
    'Reservation Form':
        'Reservierungsformular',
    'Reservation Overview':
        'Reservierungsübersicht',
    'Table Order Page':
        'Tischbestellseite',
    '— Select page —':
        '— Seite auswählen —',

    # ── v1.5.0: Dashboard-Kacheln + Standort-CPT-Meta-Box ───────────────────────
    'View and manage incoming orders in the Kanban board.':
        'Eingehende Bestellungen im Kanban-Board anzeigen und verwalten.',
    'Display incoming orders as a kanban board for quick status management.':
        'Eingehende Bestellungen als Kanban-Board anzeigen für schnelle Statusverwaltung.',
    'Process in-person orders with the Point of Sale interface.':
        'Direkte Bestellungen über die Kassensystem-Oberfläche erfassen.',
    'Revenue and order statistics per location and time period.':
        'Umsatz- und Bestellstatistiken pro Standort und Zeitraum.',
    'Documentation, guides, and support contact.':
        'Dokumentation, Anleitungen und Support-Kontakt.',
    'Configure features, locations, checkout, branding, and more.':
        'Funktionen, Standorte, Checkout, Branding und mehr konfigurieren.',
    "First check the help area – you'll find guides for all features there.":
        'Schauen Sie zuerst in den Hilfe-Bereich – dort finden Sie Anleitungen zu allen Funktionen.',
    'Manage incoming orders with the Kanban board or POS system.':
        'Eingehende Bestellungen mit dem Kanban-Board oder Kassensystem verwalten.',
    'Manage the three user levels: Staff, Manager, and Administrator.':
        'Die drei Benutzerebenen verwalten: Personal, Manager und Administrator.',
    'Configure the support contact information displayed on the help pages.':
        'Support-Kontaktdaten konfigurieren, die auf den Hilfe-Seiten angezeigt werden.',
    'Let customers reserve tables online via a frontend form.':
        'Kunden können Tische online über ein Frontend-Formular reservieren.',

    # Zeiteinstellungen pro Standort (Meta-Box)
    'Time Settings (Override)':
        'Zeiteinstellungen (Standort)',
    'These settings override the global defaults for this location only. Leave blank to use the global value.':
        'Diese Einstellungen überschreiben die globalen Standardwerte nur für diesen Standort. Leer lassen, um den globalen Wert zu verwenden.',
    'Minimum lead time before a pickup slot becomes available. Global default: %d min.':
        'Minimale Vorlaufzeit, bevor ein Abholzeitfenster verfügbar wird. Globaler Standardwert: %d Min.',
    'Hides the first X minutes of each opening window from the slot picker (e.g. setup time after opening). Global default: %d min.':
        'Blendet die ersten X Minuten jedes Öffnungsfensters aus dem Zeitfenster-Picker aus (z. B. Einrichtungszeit nach Öffnung). Globaler Standardwert: %d Min.',
    'Hides the last X minutes of each opening window from the slot picker (e.g. to stop accepting orders before closing). Global default: %d min.':
        'Blendet die letzten X Minuten jedes Öffnungsfensters aus dem Zeitfenster-Picker aus (z. B. um Bestellungen vor dem Schliessen nicht mehr anzunehmen). Globaler Standardwert: %d Min.',

    # Allgemeine fehlende Strings
    'Add the reservation form to any page using the shortcode:':
        'Reservierungsformular auf beliebigen Seiten via Shortcode einbinden:',
    'After selection, the user is automatically redirected to the shop page.':
        'Nach der Auswahl wird der Benutzer automatisch zur Shop-Seite weitergeleitet.',
    'After submitting the form, two emails are automatically sent:':
        'Nach dem Absenden des Formulars werden automatisch zwei E-Mails versendet:',
    'As staff, you have access to the order overview and the POS system.':
        'Als Personal haben Sie Zugriff auf die Bestellübersicht und das Kassensystem.',
    'Check whether the option is published and assigned to the product.':
        'Prüfen Sie, ob die Option veröffentlicht und dem Produkt zugewiesen ist.',
    'Click a preset to apply the colors. Save to keep the changes.':
        'Preset anklicken, um die Farben zu übernehmen. Speichern, um die Änderungen zu behalten.',
    'Completed orders for today. Older orders are loaded on demand.':
        'Abgeschlossene Bestellungen für heute. Ältere Bestellungen werden bei Bedarf geladen.',
    'Custom title for the "Billing Details" section in checkout (optional)':
        'Benutzerdefinierter Titel für den Bereich «Rechnungsdetails» im Checkout (optional)',
    'Disable and re-enable the plugin to recreate roles.':
        'Plugin deaktivieren und erneut aktivieren, um Rollen neu anzulegen.',
    'Drag an order to the next column or click the status button':
        'Bestellung in die nächste Spalte ziehen oder Statusschaltfläche klicken',
    'Enable WP_DEBUG in wp-config.php for detailed error messages:':
        'WP_DEBUG in wp-config.php aktivieren für detaillierte Fehlermeldungen:',
    'For larger setups, you can create multiple tables in one step:':
        'Für grössere Konfigurationen können Sie mehrere Tische in einem Schritt erstellen:',
    'Heading shown above the tip options. Leave empty to use the default.':
        'Überschrift über den Trinkgeldoptionen. Leer lassen für den Standardtext.',
    'How often the reservations overview is updated. Default: 60 seconds.':
        'Wie oft die Reservierungsübersicht aktualisiert wird. Standard: 60 Sekunden.',
    'Icon':
        'Icon',
    'If disabled, the tip selection will not be shown in checkout.':
        'Wenn deaktiviert, wird die Trinkgeldauswahl nicht im Checkout angezeigt.',
    'Is taken directly to the order page – location and table are pre-filled':
        'Wird direkt zur Bestellseite geleitet – Standort und Tisch sind vorausgefüllt',
    'Location not published – the Free plan is limited to 1 location.':
        'Standort nicht veröffentlicht – der Free-Plan ist auf 1 Standort begrenzt.',
    'Locations must be created before orders can be accepted':
        'Standorte müssen erstellt werden, bevor Bestellungen angenommen werden können',
    "Look at the browser status bar (bottom left) – you'll see the URL there":
        'Schauen Sie in die Browser-Statusleiste (unten links) – dort sehen Sie die URL',
    'Make sure the location is published and opening hours are configured.':
        'Stellen Sie sicher, dass der Standort veröffentlicht und die Öffnungszeiten konfiguriert sind.',
    'Note: WordPress Cron must be active for reminders to be sent.':
        'Hinweis: WordPress Cron muss aktiv sein, damit Erinnerungen versendet werden.',
    'Opening hours are automatically considered when selecting time slots':
        'Öffnungszeiten werden bei der Auswahl von Zeitfenstern automatisch berücksichtigt',
    'Play a sound when a new order arrives in the order overview.':
        'Einen Sound abspielen, wenn eine neue Bestellung in der Bestellübersicht eintrifft.',
    'Please select a location first and save to generate the link.':
        'Bitte zuerst einen Standort auswählen und speichern, um den Link zu generieren.',
    'Print the QR code or copy the link for your table stands.':
        'QR-Code drucken oder Link für Tischaufsteller kopieren.',
    'Send an automatic email reminder before the scheduled pickup time.':
        'Automatische E-Mail-Erinnerung vor der geplanten Abholzeit versenden.',
    'Start of an optional second opening window (e.g. for lunch break)':
        'Beginn eines optionalen zweiten Öffnungsfensters (z. B. für eine Mittagspause)',
    'The sound only plays when the browser tab with the dashboard is open.':
        'Der Sound wird nur abgespielt, wenn der Browser-Tab mit dem Dashboard geöffnet ist.',
    'The view updates automatically. The interval can be adjusted under %s.':
        'Die Ansicht aktualisiert sich automatisch. Das Intervall kann unter %s angepasst werden.',
    'This price is added to the product price when the option is selected.':
        'Dieser Preis wird zum Produktpreis addiert, wenn die Option ausgewählt wird.',
    'This way you always know exactly when to start preparing.':
        'So wissen Sie immer genau, wann Sie mit der Zubereitung beginnen müssen.',
    'To help you quickly, please prepare the following information:':
        'Um Ihnen schnell helfen zu können, bereiten Sie bitte folgende Informationen vor:',
    'Top products (%d)':
        'Top-Produkte (%d)',
    'When this shortcode is used, the automatic popup is disabled.':
        'Wenn dieser Shortcode verwendet wird, ist das automatische Popup deaktiviert.',
    'You can apply a holiday to all locations or only to specific ones.':
        'Feiertage können für alle Standorte oder nur für bestimmte Standorte gelten.',
    'You can customize or disable the sound under Settings → Dashboard.':
        'Den Sound können Sie unter Einstellungen → Dashboard anpassen oder deaktivieren.',
    '"Complete Order" → choose payment method (cash, card, Twint, etc.)':
        '«Bestellung abschliessen» → Zahlungsmethode wählen (Bar, Karte, Twint, etc.)',
    '"Print QR Code" opens a print-optimized view with the table name.':
        '«QR-Code drucken» öffnet eine druckoptimierte Ansicht mit dem Tischnamen.',
    'requires at least WooCommerce version 8.0. Please update WooCommerce.':
        'benötigt mindestens WooCommerce 8.0. Bitte aktualisieren Sie WooCommerce.',
    'Freemius Account':
        'Freemius-Konto',
    # Dashboard-Kacheln
    'Go to page':
        'Aufrufen',
    # Master-Toggle Checkbox-Hinweis
    'Enable':
        'Aktivieren',

    # ── v1.6.0: BUG-1 Fullscreen-Fix ─────────────────────────────────────────
    'Enter fullscreen':
        'Vollbild aktivieren',
    'Exit fullscreen':
        'Vollbild beenden',

    # ── v1.6.0: F_AVAIL+ – Nicht-verfügbar-Dialog ────────────────────────────
    'Mark as Unavailable':
        'Als nicht verfügbar markieren',
    'How long should this product be unavailable?':
        'Wie lange soll dieses Produkt nicht verfügbar sein?',
    'Today only':
        'Nur heute',
    'Until further notice':
        'Bis auf Weiteres',

    # ── v1.6.0: F17r – Zeitslot-Intervall pro Standort ───────────────────────
    'Timeslot Interval':
        'Zeitslot-Intervall',
    'Interval between pickup slots. Global default: %d min.':
        'Abstand zwischen Abholzeitfenstern. Globaler Standardwert: %d Min.',

    # ── v1.6.0: F_NOTES – Positions-Notizen ──────────────────────────────────
    'Item Notes':
        'Positions-Notizen',
    'Item Notes in POS':
        'Positions-Notizen im Kassensystem',
    'Item Notes in Online Checkout':
        'Positions-Notizen im Online-Checkout',
    'Allow staff to add a short note to individual cart items in the POS system.':
        'Ermöglicht dem Personal, einzelnen Warenkorb-Artikeln im Kassensystem eine kurze Notiz hinzuzufügen.',
    'Allow customers to add a note to individual cart items at checkout.':
        'Ermöglicht Kunden, einzelnen Warenkorb-Artikeln im Checkout eine Notiz hinzuzufügen.',
    'Note...':
        'Notiz...',
    'Note':
        'Notiz',

    # ── v1.6.0: F_TAX – Steuerklassen pro Bestelltyp ─────────────────────────
    # "Standard" = Standard-Steuerklasse in WooCommerce
    'Standard':
        'Standard',
    'Multiple Tax Rates':
        'Mehrere Steuerklassen',
    'Apply a different tax class per order type: configure which tax class applies to Takeaway orders and which applies to Dine-in orders.':
        'Unterschiedliche Steuerklassen pro Bestelltyp: konfiguriere, welche Steuerklasse für Take-away- und welche für Tischbestellungen gilt.',
    'Takeaway Tax Class':
        'Take-away-Steuerklasse',
    'Dine-in Tax Class':
        'Tisch-Steuerklasse',
    'Tax class applied to takeaway and pickup orders.':
        'Steuerklasse für Take-away- und Abholbestellungen.',
    'Tax class applied to dine-in and table orders.':
        'Steuerklasse für Tischbestellungen (vor Ort).',
    'Order type:':
        'Bestellart:',
    'Takeaway':
        'Take-away',
    'Dine-in':
        'Tischbestellung',

    # ── v2.0.4: Frontend-Bestelltyp-Auswahl ──────────────────────────────────
    'Order Type Selection':
        'Bestelltyp-Auswahl',
    'Default Order Type':
        'Standard-Bestelltyp',
    'Which order type is pre-selected when the POS is opened or reloaded.':
        'Welcher Bestelltyp beim Öffnen oder Aktualisieren der Kasse vorausgewählt ist.',
    'Show a Takeaway / Dine-in selector in the checkout. When Multiple Tax Rates is enabled, '
    'the selection also controls the applicable tax rate. With the Table module active, '
    'Dine-in reveals an optional table number field.':
        'Zeigt im Checkout einen Bestelltyp-Selektor (Take-away / Tischbestellung) an. Wenn «Mehrere '
        'Steuerklassen» aktiv ist, steuert die Auswahl auch den anzuwendenden Steuersatz. '
        'Mit aktivem Tisch-Modul wird bei Tischbestellung ein optionales Tischnummer-Feld eingeblendet.',
    'Table Number (optional):':
        'Tischnummer (optional):',
    'e.g. 5':
        'z.B. 5',

    # ── v2.0.5: Statistik-Erweiterungen ──────────────────────────────────────
    'Export CSV':
        'CSV exportieren',
    'Share':
        'Anteil',
    'By Quantity':
        'Nach Menge',
    'By Revenue':
        'Nach Umsatz',
    'Qty':
        'Menge',
    'Combined with':
        'Kombiniert mit',
    'By Location':
        'Nach Standort',
    'Payment Methods (POS)':
        'Zahlungsarten (Kassensystem)',
    'All Locations':
        'Alle Standorte',
    'Top Products':
        'Top-Produkte',
    'Product':
        'Produkt',

    # ── v2.0.5: Beta-Label ────────────────────────────────────────────────────
    'Beta':
        'Beta',
    'Hover over table → change shape (◐) and size (⊞) · Auto-refresh every 30 sec.':
        'Tisch überfahren → Form (◐) und Grösse (⊞) ändern · Automatisch alle 30 Sek. aktualisiert.',
    'Hover over a table: ◐ switches shape (square/round), ⊞ switches size (S/M/L)':
        'Tisch überfahren: ◐ wechselt Form (eckig/rund), ⊞ wechselt Grösse (S/M/L)',

    # ── Fehlende Help-Strings ─────────────────────────────────────────────────
    "Didn't find what you were looking for? I'm happy to help.":
        'Nicht das Gesuchte gefunden? Ich helfe gerne.',
    'Unlock all premium features to realize the full potential of Libre Bite.':
        'Schalten Sie alle Premium-Funktionen frei, um das volle Potenzial von Libre Bite auszuschöpfen.',
    'Tip: A sound plays when new orders arrive. Make sure the sound is enabled.':
        'Tipp: Ein Sound wird abgespielt, wenn neue Bestellungen eintreffen. Stellen Sie sicher, dass der Sound aktiviert ist.',
    'Kanban board for incoming orders with status tracking and fullscreen mode':
        'Kanban-Board für eingehende Bestellungen mit Statusverfolgung und Vollbild-Modus',

    # ── Tisch / QR-Code ───────────────────────────────────────────────────────
    'Which page are guests redirected to after scanning the QR code? Default: WooCommerce shop page.':
        'Zu welcher Seite werden Gäste nach dem Scannen des QR-Codes weitergeleitet? Standard: WooCommerce-Shop-Seite.',
    'You can use these URLs in emails, QR codes, or on social media, for example.':
        'Diese URLs können beispielsweise in E-Mails, QR-Codes oder in sozialen Medien verwendet werden.',
    "When scanning the QR code, the location is automatically preset in the customer's browser.":
        'Beim Scannen des QR-Codes wird der Standort im Browser des Kunden automatisch voreingestellt.',
    "Customers at a table don't need to enter address data. The system only asks for name and (optional) email.":
        'Kunden an einem Tisch müssen keine Adressdaten eingeben. Das System fragt nur nach Name und (optional) E-Mail.',
    'QR code-based table ordering – guests scan and order directly at the table.':
        'QR-Code-basierte Tischbestellung – Gäste scannen und bestellen direkt am Tisch.',
    'The WooCommerce shop page customers are redirected to after scanning the table QR code.':
        'Die WooCommerce-Shop-Seite, zu der Kunden nach dem Scannen des Tisch-QR-Codes weitergeleitet werden.',
    'Create tables and generate QR codes for each. Guests scan the QR code to start ordering.':
        'Tische erstellen und für jeden einen QR-Code generieren. Gäste scannen den QR-Code, um zu bestellen.',

    # ── Öffnungszeiten / Standort ─────────────────────────────────────────────
    "The location is completely closed on this date. The date is blocked in the customer's date picker.":
        'Der Standort ist an diesem Datum vollständig geschlossen. Das Datum ist in der Datumsauswahl des Kunden gesperrt.',
    'The location dropdown receives a colored border matching the selected color.':
        'Das Standort-Dropdown erhält einen farbigen Rahmen passend zur gewählten Farbe.',
    'The location dropdown in the order overview is also highlighted in color.':
        'Das Standort-Dropdown in der Bestellübersicht wird ebenfalls farblich hervorgehoben.',

    # ── Reservierungen ────────────────────────────────────────────────────────
    'Insert the following shortcode on any page to display the reservation form:':
        'Folgenden Shortcode auf einer beliebigen Seite einbinden, um das Reservierungsformular anzuzeigen:',
    'You can find the location ID in the URL when editing the location (post=…).':
        'Die Standort-ID befindet sich in der URL beim Bearbeiten des Standorts (post=…).',
    'A table selection by the guest is intentionally not provided — table assignment is done by staff in the backend.':
        'Eine Tischauswahl durch den Gast ist bewusst nicht vorgesehen – die Tischzuweisung erfolgt durch das Personal im Backend.',
    'Contains all details and a direct link to the reservation in the backend.':
        'Enthält alle Details und einen direkten Link zur Reservierung im Backend.',
    'Receive table inquiries via a frontend form, manage and confirm via email.':
        'Tischanfragen über ein Frontend-Formular empfangen, verwalten und per E-Mail bestätigen.',

    # ── Bestellablauf / Help-Texte ─────────────────────────────────────────────
    'The customer selects products, a location and a pickup time on the website.':
        'Der Kunde wählt Produkte, einen Standort und eine Abholzeit auf der Website.',
    'You can optionally hide future pre-orders completely until they are relevant.':
        'Zukünftige Vorbestellungen können optional vollständig ausgeblendet werden, bis sie relevant sind.',
    'Add products to the cart by tapping – including variants and product options':
        'Produkte durch Antippen in den Warenkorb legen – inkl. Varianten und Produkt-Add-ons',
    'The selected location is currently closed. Please select a pre-order time.':
        'Der gewählte Standort ist derzeit geschlossen. Bitte wählen Sie eine Vorbestellzeit.',
    'Add-on':
        'Add-on',

    # ── Kanban-Spaltenbezeichnungen (neue Strings) ─────────────────────────────
    'Pre-orders are automatically moved from "Pre-orders" to "Prepare Now" X minutes before pickup time.':
        'Vorbestellungen werden automatisch X Minuten vor der Abholzeit von «Vorbestellungen» nach «Jetzt vorbereiten» verschoben.',
    'The order immediately appears in the "Pre-orders" column of the order overview. Optionally a sound plays.':
        'Die Bestellung erscheint sofort in der Spalte «Vorbestellungen» der Bestellübersicht. Optional wird ein Sound abgespielt.',
    'Order is moved to "Prepare Now". For pre-orders, this happens automatically X minutes before pickup time.':
        'Bestellung wird in «Jetzt vorbereiten» verschoben. Bei Vorbestellungen geschieht dies automatisch X Minuten vor der Abholzeit.',
    'The Kanban board is your real-time overview of all active orders. It has three columns: Pre-orders, Prepare Now, and Completed.':
        'Das Kanban-Board ist Ihre Echtzeit-Übersicht aller aktiven Bestellungen. Es hat drei Spalten: Vorbestellungen, Jetzt vorbereiten und Abgeschlossen.',
    'Pre-orders initially appear in "Pre-orders" with the pickup time shown.':
        'Vorbestellungen erscheinen zunächst in «Vorbestellungen» mit der angezeigten Abholzeit.',
    'X minutes before pickup time (configurable under Settings → General), they are automatically moved to the "Prepare Now" column.':
        'X Minuten vor der Abholzeit (konfigurierbar unter Einstellungen → Allgemein) werden sie automatisch in die Spalte «Jetzt vorbereiten» verschoben.',

    # ── v2.0.11: Checkout-UX-Fixes ───────────────────────────────────────────
    'Order Options':
        'Bestelloptionen',
    'Please select a location to continue.':
        'Bitte wählen Sie einen Standort, um fortzufahren.',
    'How would you like your order?':
        'Wie möchten Sie bestellen?',
    'Please select whether you want to take away or eat here.':
        'Bitte wählen Sie, ob Sie Ihre Bestellung mitnehmen oder vor Ort konsumieren möchten.',
    'Table Sort Order':
        'Tisch-Sortierung',
    'Alphabetical with natural number sort (1, 2, 10, 11)':
        'Alphabetisch mit natürlicher Zahlensortierung (1, 2, 10, 11)',
    'Menu order (drag to reorder in table list)':
        'Menüreihenfolge (in der Tischliste per Drag & Drop anpassen)',
    'Controls how tables are sorted in the checkout dropdown.':
        'Legt die Sortierreihenfolge der Tische im Checkout-Dropdown fest.',

    # ── v2.0.9: Frontend-Bestelltyp-Wording und Tisch-Dropdown ───────────────
    'To take away':
        'zum Mitnehmen',
    'Eat here':
        'vor Ort konsumieren',
    'Table (optional):':
        'Tisch (optional):',
    'Select table (optional)':
        'Tisch auswählen (optional)',
    'The VAT rate per order type is configured under %s.':
        'Der Mehrwertsteuersatz pro Bestelltyp wird unter %s konfiguriert.',

    # ── v2.1.x: POS-Produktmarkierung & Drag-Drop-Reihenfolge ────────────────
    'POS only (hidden from customers)':
        'Nur im POS (für Kunden nicht sichtbar)',
    'Product is visible in POS but not shown on shop pages or product URLs.':
        'Produkt ist im POS sichtbar, wird aber nicht auf Shop-Seiten oder '
        'Produkt-URLs angezeigt.',
    'Product Order':
        'Produktreihenfolge',
    'Drag products into the desired order. This order applies to the POS and to '
    "the store's catalog (when WooCommerce uses custom ordering).":
        'Produkte in die gewünschte Reihenfolge ziehen. Diese Reihenfolge gilt im '
        'POS und im Shop-Katalog (wenn WooCommerce «Benutzerdefinierte Reihenfolge» '
        'verwendet).',
    'Save Order':
        'Reihenfolge speichern',
}

# Formelle Kunden-Anrede (Sie) – für de_CH formal und de_DE_formal und de_AT
CUSTOMER_FORMAL = {
    "Your Email":                                   "Ihre E-Mail-Adresse",
    "What's your name?":                            "Wie heissen Sie?",
    "Your Name":                                    "Ihr Name",
    "Your Order":                                   "Ihre Bestellung",
    "Your Pickup Number":                           "Ihre Abholnummer",
    "your@email.com":                               "ihre@email.ch",
    "this is a reminder about your upcoming order.":
        "dies ist eine Erinnerung an Ihre bevorstehende Bestellung.",
    "Do you really want to cancel this order?\n\nThe payment will be automatically refunded.":
        "Möchten Sie diese Bestellung wirklich stornieren?\n\nDie Zahlung wird automatisch zurückerstattet.",
}

# Informelle Kunden-Anrede (du) – für de_CH_informal und de_DE
CUSTOMER_INFORMAL_SS = {
    "Your Email":                                   "Deine E-Mail-Adresse",
    "What's your name?":                            "Wie heisst du?",
    "Your Name":                                    "Dein Name",
    "Your Order":                                   "Deine Bestellung",
    "Your Pickup Number":                           "Deine Abholnummer",
    "your@email.com":                               "deine@email.ch",
    "Please select a location.":                    "Bitte wähle einen Standort.",
    "Please select an order type.":                 "Bitte wähle eine Bestellart.",
    "Please select a pickup time.":                 "Bitte wähle eine Abholzeit.",
    "Please select a location and pickup time.":    "Bitte wähle einen Standort und eine Abholzeit.",
    "Please select a location to continue.":        "Bitte wähle einen Standort, um fortzufahren.",
    "How would you like your order?":               "Wie möchtest du bestellen?",
    "Please select whether you want to take away or eat here.":
        "Bitte wähle, ob du deine Bestellung mitnehmen oder vor Ort konsumieren möchtest.",
    "Would you like to leave a tip?":               "Möchtest du ein Trinkgeld hinterlassen?",
    "Please select your desired location:":         "Bitte wähle deinen gewünschten Standort:",
    "When would you like to order?":                "Wann möchtest du bestellen?",
    "this is a reminder about your upcoming order.":
        "dies ist eine Erinnerung an deine bevorstehende Bestellung.",
    "Do you really want to cancel this order?\n\nThe payment will be automatically refunded.":
        "Möchtest du diese Bestellung wirklich stornieren?\n\nDie Zahlung wird automatisch zurückerstattet.",
    "Notes about your order, e.g. special delivery instructions.":
        "Hinweise zu deiner Bestellung, z. B. besondere Lieferhinweise.",
    "Activate the features you need in the Features tab. All core features are "
    "enabled by default \u2013 you can adjust this at any time.":
        "Aktiviere die ben\u00f6tigten Funktionen im Funktionen-Tab. Alle "
        "Kernfunktionen sind standardm\u00e4ssig aktiviert \u2013 du kannst "
        "dies jederzeit anpassen.",
}


def ss_to_sz(text: str) -> str:
    """ss → ß für de_DE / de_AT"""
    replacements = [
        ("Strasse",     "Straße"),
        ("strasse",     "straße"),
        ("Adresse",     "Adresse"),  # Behalten (kein reines ss)
        ("müssen",      "müssen"),   # Behalten
        ("muss",        "muss"),     # Behalten
        ("Schliessung", "Schließung"),
        ("schliessen",  "schließen"),
        ("Schliessen",  "Schließen"),
        ("schliesst",   "schließt"),
        ("Heissen",     "Heißen"),
        ("heissen",     "heißen"),
        ("heisst",      "heißt"),
        ("Heisst",      "Heißt"),
        ("Grüssen",     "Grüßen"),
        ("grüssen",     "grüßen"),
        ("Grösse",      "Größe"),
        ("grösse",      "größe"),
        ("weiss",       "weiß"),
        ("Weiss",       "Weiß"),
        ("Strassenbahn","Straßenbahn"),
        ("Öffnungszeiten", "Öffnungszeiten"),  # Behalten
        # Dismiss / Schliessen → Schließen
        ("Schliessen",  "Schließen"),
    ]
    for old, new in replacements:
        text = text.replace(old, new)
    return text


# ─────────────────────────────────────────────────────────────────
# Hilfsfunktionen zum Parsen und Bearbeiten von .po-Dateien
# ─────────────────────────────────────────────────────────────────

def parse_po(content: str):
    """Gibt Liste von Entry-Dicts zurück."""
    entries = []
    # Splitte auf Leerzeilen zwischen Einträgen
    raw_entries = re.split(r'\n(?=\n)', content)
    for raw in raw_entries:
        raw = raw.strip('\n')
        if not raw:
            continue
        entries.append(raw)
    return entries


def get_msgid(entry: str) -> str:
    """Extrahiert den kompletten msgid-Wert aus einem Eintrag."""
    m = re.search(r'^msgid (.*?)(?=\nmsgstr|\nmsgid_plural)', entry, re.DOTALL | re.MULTILINE)
    if not m:
        return ''
    raw = m.group(1)
    return _decode_po_string(raw)


def get_msgstr(entry: str) -> str:
    """Extrahiert den kompletten msgstr-Wert aus einem Eintrag."""
    m = re.search(r'^msgstr (.*?)(?=\n#|\nmsgid|\Z)', entry, re.DOTALL | re.MULTILINE)
    if not m:
        return ''
    raw = m.group(1)
    return _decode_po_string(raw)


def _decode_po_string(raw: str) -> str:
    """Dekodiert mehrzeilige PO-String-Darstellung → Python-String."""
    lines = raw.strip().split('\n')
    result = ''
    for line in lines:
        line = line.strip()
        if line.startswith('"') and line.endswith('"'):
            inner = line[1:-1]
            inner = inner.replace('\\n', '\n').replace('\\"', '"').replace('\\\\', '\\')
            result += inner
    return result


def _encode_po_string(value: str) -> str:
    """Kodiert Python-String → PO-Format (mehrzeilig wenn nötig)."""
    escaped = value.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n')
    if '\\n' in escaped:
        parts = escaped.split('\\n')
        # Letzter Part ist leer wenn String mit \n endet → ignorieren
        lines = []
        for i, part in enumerate(parts):
            if i < len(parts) - 1:
                lines.append('"' + part + '\\n"')
            elif part:
                lines.append('"' + part + '"')
        return '""\n' + '\n'.join(lines)
    return '"' + escaped + '"'


def set_msgstr(entry: str, new_msgstr: str) -> str:
    """Ersetzt msgstr und entfernt #, fuzzy-Flag."""
    encoded = _encode_po_string(new_msgstr)
    # msgstr ersetzen
    entry = re.sub(
        r'^(msgstr ).*?(?=\n#|\nmsgid|\Z)',
        lambda m: 'msgstr ' + encoded,
        entry,
        flags=re.DOTALL | re.MULTILINE
    )
    # fuzzy-Flag entfernen (alle Formen: alleine, am Anfang, am Ende kombinierter Flags)
    entry = re.sub(r'^#, fuzzy\n', '', entry, flags=re.MULTILINE)        # standalone: "#, fuzzy\n"
    entry = re.sub(r'(^#,\s+)fuzzy,\s+', r'\1', entry, flags=re.MULTILINE)  # prefix: "#, fuzzy, php-format"
    entry = re.sub(r',\s+fuzzy(?=\n|$)', '', entry, flags=re.MULTILINE)  # suffix: "#, php-format, fuzzy"
    return entry


def apply_translations(content: str, translations: dict) -> str:
    """Wendet ein Dict {msgid: msgstr} auf den .po-Inhalt an."""
    entries = content.split('\n\n')
    result = []
    for entry in entries:
        msgid = get_msgid(entry)
        if msgid in translations:
            entry = set_msgstr(entry, translations[msgid])
        result.append(entry)
    return '\n\n'.join(result)


# ─────────────────────────────────────────────────────────────────
# Varianten-Definitionen
# ─────────────────────────────────────────────────────────────────

def build_de_ch(content: str) -> str:
    """de_CH: formell, ss"""
    t = {**NEW_STRINGS_FORMAL_SS, **CUSTOMER_FORMAL}
    return apply_translations(content, t)


def build_de_ch_informal(content: str) -> str:
    """de_CH_informal: informell, ss"""
    # Basis: formelle de_CH, dann Kunden-Strings auf du umstellen
    t = {**NEW_STRINGS_FORMAL_SS, **CUSTOMER_INFORMAL_SS}
    # Dismiss bleibt "Schliessen" (ss)
    return apply_translations(content, t)


def build_de_de(content: str) -> str:
    """de_DE: informell, ß"""
    informal_sz = {k: ss_to_sz(v) for k, v in CUSTOMER_INFORMAL_SS.items()}
    new_sz = {k: ss_to_sz(v) for k, v in NEW_STRINGS_FORMAL_SS.items()}
    # email placeholder für DE
    new_sz["your@email.com"] = "deine@email.de"
    informal_sz["your@email.com"] = "deine@email.de"
    informal_sz["Notes about your order, e.g. special delivery instructions."] = \
        "Hinweise zu deiner Bestellung, z.\u00a0B. besondere Lieferhinweise."
    t = {**new_sz, **informal_sz}
    result = apply_translations(content, t)
    # ss→ß im gesamten Dateiinhalt (nur msgstr-Bereiche)
    result = apply_ss_to_sz_in_msgstr(result)
    return result


def build_de_de_formal(content: str) -> str:
    """de_DE_formal: formell, ß"""
    formal_sz = {k: ss_to_sz(v) for k, v in CUSTOMER_FORMAL.items()}
    new_sz = {k: ss_to_sz(v) for k, v in NEW_STRINGS_FORMAL_SS.items()}
    new_sz["your@email.com"] = "ihre@email.de"
    formal_sz["your@email.com"] = "ihre@email.de"
    t = {**new_sz, **formal_sz}
    result = apply_translations(content, t)
    result = apply_ss_to_sz_in_msgstr(result)
    return result


def build_de_at(content: str) -> str:
    """de_AT: formell, ß (identisch mit de_DE_formal aber .at-Domain)"""
    formal_sz = {k: ss_to_sz(v) for k, v in CUSTOMER_FORMAL.items()}
    new_sz = {k: ss_to_sz(v) for k, v in NEW_STRINGS_FORMAL_SS.items()}
    new_sz["your@email.com"] = "ihre@email.at"
    formal_sz["your@email.com"] = "ihre@email.at"
    t = {**new_sz, **formal_sz}
    result = apply_translations(content, t)
    result = apply_ss_to_sz_in_msgstr(result)
    return result


def apply_ss_to_sz_in_msgstr(content: str) -> str:
    """Wendet ss→ß nur auf msgstr-Zeilen an (nicht auf msgid oder Kommentare)."""
    lines = content.split('\n')
    in_msgstr = False
    result = []
    for line in lines:
        stripped = line.lstrip()
        if stripped.startswith('msgstr ') or stripped.startswith('msgstr['):
            in_msgstr = True
        elif stripped.startswith('msgid ') or stripped.startswith('#') or stripped == '':
            in_msgstr = False

        if in_msgstr and (stripped.startswith('"') or stripped.startswith('msgstr ')):
            line = ss_to_sz(line)
        result.append(line)
    return '\n'.join(result)


def update_header(content: str, language: str, plural_forms: str) -> str:
    """Aktualisiert Language- und Plural-Forms-Header."""
    content = re.sub(
        r'"Language: [^\\]*\\n"',
        f'"Language: {language}\\\\n"',
        content
    )
    content = re.sub(
        r'"Plural-Forms: [^\\]*\\n"',
        f'"Plural-Forms: {plural_forms}\\\\n"',
        content
    )
    return content


# ─────────────────────────────────────────────────────────────────
# Hauptprogramm
# ─────────────────────────────────────────────────────────────────

def main():
    # Basis: aktuelle de_CH.po als Ausgangspunkt für alle Varianten
    base_path = os.path.join(LANG_DIR, 'libre-bite-de_CH.po')
    with open(base_path, encoding='utf-8') as f:
        base_content = f.read()

    plural_de = r'nplurals=2; plural=(n != 1);'

    variants = [
        {
            'file':     'libre-bite-de_CH.po',
            'builder':  build_de_ch,
            'language': 'de_CH',
            'plural':   plural_de,
        },
        {
            'file':     'libre-bite-de_CH_informal.po',
            'builder':  build_de_ch_informal,
            'language': 'de_CH',
            'plural':   plural_de,
        },
        {
            'file':     'libre-bite-de_DE.po',
            'builder':  build_de_de,
            'language': 'de_DE',
            'plural':   plural_de,
        },
        {
            'file':     'libre-bite-de_DE_formal.po',
            'builder':  build_de_de_formal,
            'language': 'de_DE',
            'plural':   plural_de,
        },
        {
            'file':     'libre-bite-de_AT.po',
            'builder':  build_de_at,
            'language': 'de_AT',
            'plural':   plural_de,
        },
    ]

    for v in variants:
        path = os.path.join(LANG_DIR, v['file'])

        # Für neue Varianten: Kopie der Basis verwenden
        if not os.path.exists(path):
            shutil.copy(base_path, path)
            with open(path, encoding='utf-8') as f:
                source = f.read()
        else:
            with open(path, encoding='utf-8') as f:
                source = f.read()

        result = v['builder'](source)
        result = update_header(result, v['language'], v['plural'])

        with open(path, 'w', encoding='utf-8') as f:
            f.write(result)

        # .mo kompilieren
        mo_path = path.replace('.po', '.mo')
        ret = subprocess.run(['msgfmt', path, '-o', mo_path], capture_output=True, text=True)
        stats = subprocess.run(['msgfmt', '--statistics', path], capture_output=True, text=True)
        status = stats.stderr.strip() if stats.stderr else stats.stdout.strip()
        print(f"{'OK' if ret.returncode == 0 else 'FEHLER':4s}  {v['file']:40s}  {status}")
        if ret.returncode != 0:
            print(f"       {ret.stderr}")


if __name__ == '__main__':
    main()
