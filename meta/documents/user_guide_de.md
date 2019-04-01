<div class="alert alert-warning" role="alert">
   <strong><i>Hinweis:</i></strong> Das Novalnet Plugin wurde für die Verwendung mit dem Onlineshop System CERES entwickelt und funktioniert nur in dieser Struktur oder anderen Tempalte-Plugins. Ein IO Plugin ist erforderlich.
</div>

# Novalnet-Plugin für Plentymarkets:

Das Novalnet Zahlungsmodul für Plentymarkets vereinfacht die tägliche Arbeit aufgrund der Automatisierung des gesamten Zahlungsprozesses, angefangen beim Bezahlvorgang bis hin zum Inkasso. Dieses Plugin/Modul ist entwickelt worden, um Ihren Umsatz aufgrund der Vielzahl an internationalen und lokalen Zahlungsmethoden zu steigern.

Das Plugin ist perfekt auf Plentymarkets und das umfangreiche Serviceangebot der Novalnet AG angepasst. Das vielfältige Angebot an Zahlungsmethoden umfasst zum Beispiel **Kreditkarte**, **Lastschrift SEPA**, **PayPal**, **Kauf auf Rechnung**, **Barzahlen**, **SOFORT-Überweisung**, **iDeal** und viele mehr

## Abschluss eines Novalnet Händlerkontos/Dienstleistungsvertrags:

Sie müssen zuerst einen Dienstleistungsvertrag/ ein Händlerkonto bei Novalnet abschließen/einrichten, bevor Sie das Zahlungsmodul in Plentymarkets installieren können. Sie erhalten nach Abschluss des Vertrags die Daten zur Installation und Konfiguration der Zahlungsmethoden. Bitte kontaktieren Sie Novalnet unter der [sales@novalnet.de](mailto:sales@novalnet.de), um Ihr Händlerkonto bei Novalnet einrichten zu lassen.

## Konfiguration des Novalnet Plugins in Plentymarkets:

Zur Einrichtung rufen Sie den Menüpunkt **Plugins -> Plugin-Übersicht -> Novalnet -> Konfiguration** auf.

### Novalnet Haupteinstellungen

- Geben Sie Ihre Novalnet Login Daten ein, um die Zahlungsmethode in Ihrem Shop sichtbar zu machen.
- Die Eingabefelder **Merchant ID**, **Authentication code**, **Project ID**, **Tariff ID** und **Payment access key** sind Pflichtfelder.
- Diese Daten sind im [Novalnet Händler Administrationsportal ersichtlich](https://admin.novalnet.de/).
- Um die Einrichtung von Novalnet in Ihrem Shop abzuschließen, bestätigen Sie bitte nach der Eingabe der Daten in den jeweiligen Feldern den Menüpunkt **Enable payment method**.

##### Informationen zu Ihrem Novalnet Händlerkonto:

1. Melden Sie sich an Ihrem Händlerkonto an.
2. Klicken Sie auf den Menüpunkt **PROJEKTE**.
3. Wählen Sie das gewünschte Produkt/Projekt aus.
4. Unter dem Reiter **Parameter Ihres Shops** finden Sie die benötigten Informationen.
5. Wichtiger Hinweis: Evtl. sind mehrere **Tarif-ID´s** vorhanden, wenn Sie mehrere Tarife für Ihre Projekte angelegt haben. Notieren/Kopieren Sie sich die Tarif-ID, welche Sie in Ihrem Shop verwenden wollen.

### Novalnet Einrichtung mit der Installationsbeschreibung.

<table>
    <thead>
        <th>
            Feld
        </th>
        <th>
            Beschreibung
        </th>
    </thead>
    <tbody>
        <tr>
        <td class="th" align=CENTER colspan="2">Allgemeines</td>
        </tr>
        <tr>
            <td>
                <b>Enable test mode</b>
            </td>
            <td>Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen</td>
        </tr>
        <tr>
            <td>
                <b>Merchant ID</b>
            </td>
            <td>A Die Händler ID wird von der Novalnet AG nach Eröffnung eines Händlerkontos Ihnen zur Verfügung gestellt. Bitte kontaktieren Sie Novalnet unter der <a href="mailto:sales@novalnet.de">sales@novalnet.de</a>, um Ihr Händlerkonto bei Novalnet einrichten zu lassen.</td>
        </tr>
        <tr>
            <td>
                <b>Authentication code</b>
            </td>
            <td>Der Händler Authenthifizierungscode wird von der Novalnet AG nach Eröffnen eines Händlerkontos Ihnen zur Verfügung gestellt.</td>
        </tr>
        <tr>
            <td>
                <b>Project ID</b>
            </td>
            <td>Die Projekt ID ist eine eindeutige Identifikationsnummer eines angelegten Händlerprojekts. Der Händler kann eine beliebige Anzahl von Projekten im Novalnet-Adminportal erstellen.</td>
        </tr>
        <tr>
            <td>
                <b>Tariff ID</b>
            </td>
            <td>Die Tarif ID ist eine eindeutige Identifikationsnummer für jedes angelegte Projekt. Der Händler kann eine beliebige Anzahl von Tarifen im Novalnet-Adminportal erstellen.</td>
        </tr>
        <tr>
            <td>
                <b>Payment access key</b>
            </td>
            <td>Dies ist der sichere öffentliche Schlüssel zur Verschlüsselung und Entschlüsselung von Transaktionsparametern. Für alle Transaktionen muss dieser Schlüssel zwingend übermittelt werden.</td>
        </tr>
        <tr>
            <td>
                <b>Referrer ID</b>
            </td>
            <td>Geben Sie die Partner-ID der Person / des Unternehmens ein, welche / welches Ihnen Novalnet empfohlen hat</td>
        </tr>
        <tr>
        <td class="th" align=CENTER colspan="2"><b>Zahlungseinstellungen</b></td>
        </tr>
        <tr>
        <td class="th" align=CENTER colspan="2">Kreditkarte</td>
        </tr>
        <tr>
            <td>
                <b>Enable 3D secure</b>
            </td>
            <td>Bei der Aktivierung der 3D-Secure fordert das ausstellende Kreditkarteninstitut den Käufer zusätzlich auf ein Passwort einzugeben. Mit der Aktivierung dieser Option kann der ausstellenden Bank der Beweis geliefert werden, dass es sich bei dem Käufer tatsächlich um den Karteninhaber handelt. Das Risiko eines Charge-Backs wird dadurch verringert.</td>
        </tr>
        <tr>
        <td class="th" align=CENTER colspan="2">Lastschrift SEPA</td>
        </tr>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>Falls eine Bestellung die angegebene Grenze überschreitet, wird diese Bestellung bis zur manuellen Bestätigung des Händlers auf den Status <b>on-hold</b> gesetzt.</td>
        </tr>
        <td class="th" align=CENTER colspan="2">Kauf auf Rechnung / Vorauskasse</td>
        <tr>
            <td>
                <b>Payment due date (in days)</b>
            </td>
            <td>Geben Sie die Anzahl der Tage ein, binnen derer die Zahlung bei Novalnet eingehen soll (muss größer als 7 Tage sein). Falls dieses Feld leer ist, werden 14 Tage als Standard-Zahlungsfrist gesetzt.</td>
        </tr>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>Falls eine Bestellung die angegebene Grenze überschreitet, wird diese Bestellung bis zur manuellen Bestätigung des Händlers auf den Status <b>on-hold</b> gesetzt.</td>
        </tr>
        <td class="th" align=CENTER colspan="2">Barzahlen</td>
        <tr>
            <td>
                <b>Slip expiry date (in days)</b>
            </td>
            <td>Geben Sie die Anzahl der Tage ein, um den Betrag in einer Barzahlen-Partnerfiliale in Ihrer Nähe zu bezahlen. Wenn das Feld leer ist, werden standardmäßig 14 Tage als Fälligkeitsdatum gesetzt.</td>
        </tr>
        <td class="th" align=CENTER colspan="2">Paypal</td>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>Falls eine Bestellung die angegebene Grenze überschreitet, wird diese Bestellung bis zur manuellen Bestätigung des Händlers auf den Status <b>on-hold</b> gesetzt.</td>
        </tr>
    </tbody>
</table>

## Anzeige der Transaktionsdetails zu Zahlungen auf der Bestellbestätigungsseite.

Um die Transaktionsdetails anzeigen zu lassen, befolgen Sie bitte die folgenden Schritte.

1. Navigieren Sie zum Menüpunkt **CMS » Container-Verknüpfungen**.
2. Navigieren Sie zum Bereich **Novalnet payment details**.
3. Aktivieren Sie das Feld **Order confirmation: Additional payment information**.
4. Drücken Sie auf **Speichern**.<br />→ Die Zahlungsdetails werden danach auf der Bestellbestätigungsseite angezeigt.

## Aktualisierung der Händlerskript-URL

Die Händlerskript-URL wird dazu benötigt, um den Transaktionsstatus in der Datenbank / im System des Händlers aktuell und auf demselben Stand wie bei Novalnet zu halten. Dazu muss die Händlerskript-URL im [Novalnet-Händleradministrationsportal](https://admin.novalnet.de/) eingerichtet werden.

Vom Novalnet-Server wird die Information zu jeder Transaktion und deren Status (durch asynchrone Aufrufe) an den Server des Händlers übertragen.

Konfiguration der Händlerskript URL,

1. Melden Sie sich an Ihrem Händlerkonto im Novalnet Adminportal an.
2. Wählen Sie den Menüpunkt **Projekte** aus.
3. Wählen Sie das gewünschte Projekt aus.
4. Bitte geben Sie unter dem Reiter **Projektübersicht** und Menüpunkt **Händlerskript URL** Ihre Händlerskript URL für Ihren Shop ein.
5. Standardmäßig lautet die Händlerskript URL wie folgt: **URL-Ihrer-Webseite/payment/novalnet/callback**.

## Weitere Informationen

Um mehr über verschiedene Features bei Novalnet zu erfahren, kontaktieren Sie bitte Novalnet unter [sales@novalnet.de](mailto:sales@novalnet.de).
