<?php
declare(strict_types=1);
$title               = 'Interessenabwägung';
$headerTitle         = 'Interessenabwägung';
$footerLinks         = [
  ['href' => '/zero-trust/dashboard/',  'label' => 'Dashboard'],
  ['href' => '/datenschutz/', 'label' => 'Datenschutz'],
];
$showFooterThemeMenu = true;
require __DIR__ . '/../../../includes/head.php';
?>

<section class="card privacy-card">
  <div class="card-header">
    <span class="card-subtitle"><strong>Stand:</strong> 18.03.2026</span>
  </div>

  <div class="card-body privacy-content">

    <p>
      <strong>Projekt:</strong> QR-Code-basierte Community-Reichweitenanalyse<br>
      <strong>Verantwortlicher:</strong> Niklas Rühl<br>
      <strong>Kontakt:</strong> <a href="mailto:datenschutz@framenode.net">datenschutz@framenode.net</a><br>
      <strong>Datum:</strong> 18.03.2026
    </p>

    <h2>1. Beschreibung der Verarbeitung</h2>
    <p>
      Im Rahmen der Nutzung öffentlich bereitgestellter QR-Codes wird beim Aufruf einer Kurz-URL
      ein Zeitstempel (Datum und Uhrzeit) gespeichert.
    </p>
    <p>Zusätzlich werden technisch erforderliche Verbindungsdaten verarbeitet:</p>
    <ul class="indented">
      <li>HTTP-Statuscode</li>
      <li>Zeitstempel des Aufrufs</li>
    </ul>
    <p>IP-Adressen und Geräteinformationen werden weder in Server-Logs noch im Tracking-Dienst gespeichert.</p>
    <p>Die QR-Code-URL führt mittels HTTP-Status 302 auf einen Discord-Invite-Link weiter.</p>
    <p>Es erfolgt:</p>
    <ul class="indented">
      <li>keine Profilbildung</li>
      <li>keine Geräteerkennung</li>
      <li>keine dauerhafte IP-Speicherung</li>
      <li>keine Zusammenführung mit anderen Daten</li>
    </ul>
    <p>Die statistische Auswertung erfolgt ausschließlich aggregiert.</p>

    <h2>2. Berechtigtes Interesse des Verantwortlichen</h2>
    <p>Das berechtigte Interesse besteht in:</p>
    <ul class="indented">
      <li>der anonymen Reichweitenanalyse</li>
      <li>der Messung der Nutzungshäufigkeit</li>
      <li>der Bewertung des Community-Wachstums</li>
      <li>der Optimierung technischer Ressourcen</li>
    </ul>
    <p>
      Ohne Erhebung von Zugriffszahlen wäre eine sachgerechte Einschätzung der Nutzung nicht möglich.
    </p>

    <h2>3. Erforderlichkeit der Verarbeitung</h2>
    <p>Die Speicherung eines Zeitstempels ist erforderlich, um:</p>
    <ul class="indented">
      <li>Zugriffszahlen zeitlich zu analysieren</li>
      <li>Nutzungsspitzen zu erkennen</li>
      <li>technische Stabilität zu planen</li>
    </ul>
    <p>
      Eine weniger eingriffsintensive Maßnahme ist nicht ersichtlich,
      da keinerlei personenbezogene Profile erstellt werden.
    </p>

    <h2>4. Interessenabwägung mit den Betroffenen</h2>

    <h3><u>Eingriffsintensität</u></h3>
    <ul class="indented">
      <li>Es werden keine direkt identifizierenden Daten gespeichert</li>
      <li>IP-Adressen werden nicht gespeichert</li>
      <li>Keine dauerhafte Identifizierbarkeit</li>
      <li>Keine Weitergabe zu Werbezwecken</li>
      <li>Keine automatisierten Entscheidungen</li>
    </ul>
    <p>Der Eingriff ist daher als gering einzustufen.</p>

    <h3><u>Erwartungshaltung der Nutzer</u></h3>
    <p>
      Beim Aufruf eines QR-Codes ist technisch zu erwarten, dass Zugriffsdaten
      in minimalem Umfang verarbeitet werden.
      Eine rein technische Verarbeitung nicht personenbezogener Zugriffsdaten entspricht
      der allgemeinen Verkehrserwartung.
    </p>

    <h2>5. Schutzmaßnahmen</h2>
    <p>Folgende Maßnahmen minimieren das Risiko:</p>
    <ul class="indented">
      <li>Keine Speicherung von IP-Adressen (weder in Logs noch im Tracking-Dienst)</li>
      <li>keine Tracking-Cookies</li>
      <li>keine Fingerprinting-Techniken</li>
      <li>keine Profilbildung</li>
      <li>Zugriffsschutz der Administrationsoberfläche (Cloudflare Zero Trust)</li>
      <li>AV-Vertrag mit Contabo GmbH (Art. 28 DSGVO)</li>
      <li>DPA mit Cloudflare Inc. (Art. 28 DSGVO)</li>
    </ul>

    <h2>6. Ergebnis der Abwägung</h2>
    <p>
      Das berechtigte Interesse an der anonymen Reichweitenanalyse überwiegt
      die schutzwürdigen Interessen der betroffenen Personen.
      Die Verarbeitung ist daher gemäß Art. 6 Abs. 1 lit. f DSGVO rechtmäßig.
    </p>

    <h2>Hinweis zur Verwendung</h2>
    <p style="color:var(--text-muted); font-size:0.9rem; border-left:3px solid var(--border-soft); padding-left:0.75rem;">
      Dieses Dokument ist ein internes Dokumentationsinstrument gemäß der Rechenschaftspflicht
      nach Art. 5 Abs. 2 DSGVO. Eine vollständige Veröffentlichung ist nicht erforderlich.<br><br>
      Öffentlich in der Datenschutzerklärung genügt der Verweis:<br>
      <em>„Die Verarbeitung erfolgt gemäß Art. 6 Abs. 1 lit. f DSGVO auf Grundlage des berechtigten
      Interesses an der anonymen Reichweitenanalyse. Eine detaillierte Interessenabwägung wurde
      dokumentiert und kann auf Anfrage eingesehen werden."</em>
    </p>

  </div>
</section>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
