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
    # Dashboard-Strings (neu aus dieser Session)
    "Loading orders...":
        "Bestellungen werden geladen...",
    "Error loading orders":
        "Fehler beim Laden der Bestellungen",
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
}

# Formelle Kunden-Anrede (Sie) – für de_CH formal und de_DE_formal und de_AT
CUSTOMER_FORMAL = {
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
    "What's your name?":                            "Wie heisst du?",
    "Your Name":                                    "Dein Name",
    "Your Order":                                   "Deine Bestellung",
    "Your Pickup Number":                           "Deine Abholnummer",
    "your@email.com":                               "deine@email.ch",
    "Please select a location.":                    "Bitte wähle einen Standort.",
    "Please select an order type.":                 "Bitte wähle eine Bestellart.",
    "Please select a pickup time.":                 "Bitte wähle eine Abholzeit.",
    "Please select a location and pickup time.":    "Bitte wähle einen Standort und eine Abholzeit.",
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
    # fuzzy-Flag entfernen
    entry = re.sub(r'^#, fuzzy\n?', '', entry, flags=re.MULTILINE)
    entry = re.sub(r', fuzzy', '', entry)
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
