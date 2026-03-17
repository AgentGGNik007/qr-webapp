<?php
declare(strict_types=1);
$title               = 'Datenschutz';
$headerTitle         = 'Datenschutzerklärung';
$footerLinks         = [];
$showFooterThemeMenu = true;
require __DIR__ . '/../../includes/head.php';
?>

<section class="card privacy-card">
  <div class="card-header">
    <span class="card-subtitle"><strong>Stand:</strong> 03.03.2026</span>
  </div>

  <div class="card-body privacy-content">

    <h2>1. Verantwortlicher</h2>
    <p>
      Verantwortlich im Sinne der Datenschutz-Grundverordnung (DSGVO) ist:<br>
      <strong>Niklas Rühl</strong><br>
      E-Mail: <a href="mailto:datenschutz@framenode.net">datenschutz@framenode.net</a>
    </p>

    <h2>2. Zweck der Verarbeitung</h2>
    <p>
      Die über diese Website bereitgestellten QR-Codes dienen der anonymen Reichweitenanalyse
      sowie der statistischen Auswertung des Community-Wachstums.
    </p>
    <p>
      Die Verarbeitung erfolgt ausschließlich zu diesem Zweck.
      Eine Profilbildung oder personenbezogene Auswertung findet nicht statt.
    </p>

    <h2>3. Art und Umfang der verarbeiteten Daten</h2>
    <p>Beim Aufruf einer QR-Code-URL werden folgende Daten verarbeitet:</p>
    <ul class="indented">
      <li>Zeitpunkt des Aufrufs (Datum und Uhrzeit)</li>
      <li>Anonymisierte IP-Adresse (gekürzt / nicht rückverfolgbar)</li>
      <li>Technische Verbindungsdaten (z.B. HTTP-Statuscode)</li>
    </ul>
    <p>
      Es werden keine Endgerätedaten gespeichert, keine Cookies gesetzt
      und keine personenbezogenen Nutzerprofile erstellt.
    </p>

    <h2>4. Rechtsgrundlage</h2>
    <p>
      Die Verarbeitung erfolgt gemäß Art. 6 Abs. 1 lit. f DSGVO
      auf Grundlage des berechtigten Interesses an der anonymen
      Reichweitenanalyse und statistischen Auswertung der Nutzung.
    </p>

    <h2>5. Speicherdauer</h2>
    <p>
      Statistische Zeitstempel werden für maximal 24 Monate gespeichert.
      Server-Logdaten werden in anonymisierter Form für maximal 3 Stunden vorgehalten
      und anschließend automatisch gelöscht.
    </p>

    <h2>6. Hosting und Auftragsverarbeitung</h2>

    <h3><u>6.1 Hosting (Contabo GmbH)</u></h3>
    <p>
      Die Datenverarbeitung erfolgt auf einem gemieteten Server
      der Contabo GmbH, Aschauer Straße 32a, 81549 München, Deutschland.
      Der Serverstandort befindet sich in Lauterbourg (Frankreich, EU).
    </p>
    <p>
      Mit Contabo besteht ein Vertrag zur Auftragsverarbeitung
      gemäß Art. 28 DSGVO.
    </p>
    <p>
      Gegenstand der Verarbeitung: Bereitstellung der Serverinfrastruktur,
      Speicherung anonymisierter Nutzungsdaten,
      technische Protokollierung und Systemsicherheit.
    </p>

    <h3><u>6.2 Reverse-Proxy und Sicherheitsdienst (Cloudflare Inc.)</u></h3>
    <p>
      Zur technischen Absicherung und Auslieferung der QR-Code-URL
      wird der Dienst Cloudflare eingesetzt.
      Cloudflare verarbeitet hierbei technisch erforderliche
      Verbindungsdaten (z.B. IP-Adresse, TLS-Metadaten).
    </p>
    <p>
      Mit Cloudflare besteht ein Data Processing Addendum (DPA)
      gemäß Art. 28 DSGVO.
    </p>
    <p>
      Eine mögliche Übermittlung personenbezogener Daten in Drittstaaten
      erfolgt auf Grundlage der EU-Standardvertragsklauseln
      gemäß Art. 46 DSGVO.
    </p>

    <h2>7. Weiterleitung zu Discord</h2>
    <p>
      Die QR-Code-URL führt mittels HTTP-Statuscode 302
      auf einen Einladungslink zu Discord.
      Beim Aufruf dieser Zieladresse verarbeitet Discord eigenständig Daten
      gemäß deren Datenschutzbestimmungen.
    </p>
    <p>
      <em>Weitere Informationen: <a href="https://discord.com/privacy" target="_blank" rel="noopener noreferrer">discord.com/privacy</a></em>
    </p>

    <h2>8. Zugriffsschutz der Administrationsoberfläche</h2>
    <p>
      Die statistische Auswertung ist nicht öffentlich zugänglich.
      Der Zugriff erfolgt ausschließlich über eine abgesicherte
      Zero-Trust-Authentifizierung und ist auf berechtigte
      Administratoren beschränkt.
    </p>

    <h2>9. Rechte betroffener Personen</h2>
    <p>
      Betroffene Personen haben im Rahmen der gesetzlichen Bestimmungen
      das Recht auf Auskunft, Berichtigung, Löschung,
      Einschränkung der Verarbeitung sowie Widerspruch.
    </p>
    <p>
      <em>Anfragen können an <a href="mailto:datenschutz@framenode.net">datenschutz@framenode.net</a> gerichtet werden.</em>
    </p>

  </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
