# Informationen zum Novalnet Release

## v2.0.2 (19.03.2019)

### Neu

- Auslösen der Autorisierung und des Einzugs für On-hold-Zahlungen (Kreditkarte, SEPA-Lastschrift, SEPA-Lastschrift mit Zahlungsgarantie, Kauf auf Rechnung, Rechnung mit Zahlungsgarantie und PayPal)
- Auslösen einer Rückerstattung für Zahlungen (Kreditkarte, SEPA-Lastschrift, SEPA-Lastschrift mit Zahlungsgarantie, Rechnung mit Zahlungsgarantie, Sofortüberweisung, iDeal, PayPal, eps, giropay und Przelewy24)
- Zahlungen für konfigurierte Länder empfangen
- Das Zahlungslogo wurde angepasst

### Verbessert

- Das Novalnet-Zahlungsmodul wurde ausgiebig  getestet und entsprechend optimiert

### Behoben

- Fehlermeldungsanzeige für abgelehnte Transaktionen
- Problem mit dem Betragsformat in der Callback-Benachrichtigungs-E-Mail

## v2.0.1 (23.01.2019)

### Behoben

- Problem beim Update des Zahlungsplugins über plentyMarketplace

## v2.0.0 (24.12.2018)

### Neu

- Der Status Garantierte Zahlung ausstehend wurde implementiert
- Mindestbetrag für Zahlungen mit Zahlungsgarantie auf 9,99 EUR herabgesetzt
- 3D-Secure Verfahren für Kreditkartenzahlungen basierend auf den Einstellungen im Novalnet Administrationsportal
- Anzeige der Payment Slip für Barzahlen-Zahlungen auf der Erfolgreich-Seite

### Verbessert

- On-hold-Option ist nun für bestimmte Zahlungsarten konfigurierbar Kreditkarte, SEPA-Lastschrift (mit Zahlungsgarantie), Kauf auf Rechnung (mit Zahlungsgarantie), PayPal
- Anlegen einer Bestellung vor Zahlungsaufruf für alle umgeleitete Zahlungsarten (SOFORT-Überweisung, Kreditkarte mit 3D-Secure, PayPal, etc.), um bei Kommunikationsabbrüchen aufgrund eines Timouts, Browserschließung, etc. eine fehlende Bestellung zu vermeiden

### Behoben

- Abgleich von Transaktionsinformationen in der Rechnung

## v1.0.3 (22.08.2018)

### Neu

- Funktion zum Austausch des Novalnet-Logos im Checkout durch eigenes Logo eingebaut

## v1.0.2 (01.06.2018)

### Verbessert

- Angepasstes Zahlungs-Plugin für die neue Konfigurationsstruktur und Unterstützung für mehrere Sprachen.

## v1.0.1 (17.01.2018)

### Verbessert

- Die Fehlermeldung wird ohne Fehlercode angezeigt.

## v1.0.0 (08.12.2017)

- Neuer Release
