# OERinForm plugin für ILIAS

* Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE
* Copyright (c) 2024 Databay AG, GPLv3, see LICENSE

Autoren:
* Fred Neumann <fneumann@databay.de>
* Jesus Copado <jesus.copado@ili.fau.de>

Das Plugin erlaubt es, Magazin-Inhalte von ILIAS auf ihrem Export-Reiter als OER zu veröffentlichen.
In einem Assistenten wird ein Autor bis zur Veröffentlichung geleitet:
* mit Checklisten werden die wichtigsten rechtlichen Voraussetzungen abgefragt,
* die Lizenzauswahl wird unterstützt,
* eine Grundmenge  an Metadaten kann eingegeben werden,
* die endgültige Zustimmung zur Freigabe wird eingeholt.
Als Ergebnis ist der Inhalt in einer öffentlichen Kategorie verknüpft und seine Metadaten sind im Format
zur Indizierung durch einen Publikationsserver für den OAI (Open Archives Initiative) exportiert.

Zusätzlich kann ein Hilfe-Wiki importiert und in der Plugin-Konfiguration ausgewählt werden:
https://www.demo.odl.org/goto.php?target=file_205_download&client_id=Demo


Dieses Plugin wurde im Rahmen des BMBF-geförderten Projekts OERinForm entwickelt. Weitere Materialien finden Sie unter:
https://oer.amh-ev.de/

Die Checkliste im Assistenten des Plugis basiert auf:
OER & Recht Checkliste Teil 1, OERinForm/Anna Wiggeringloh, http://oer.amh-ev.de/, CC BY-SA 4.0,
https://creativecommons.org/licenses/by-sa/4.0/deed.de

Die Lizenzauswahl im Assistenten ist eine PHP-Portierung des CCMixer von edu-sharing:
http://ccmixer.edu-sharing.org/


Aufgrund der verwendeten Begiffe ist das Plugin bislang nur auf Deutsch verfügbar.

## Installation

Wenn Sie das Plugin als ZIP-Datei aus GitHub herunterladen, benennen Sie das entpackte Verzeichnis bitte als *OERinForm*
(entfernen Sie das Branch-Suffix, z.B. -master und verwenden Sie die Groß-/Kleinschreibung wie angegeben).

1. Kopieren Sie das Plugin-Verzeichnis in Ihrer ILIAS-Installation unter
Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
(erzeugen Sie die Unterverzeichnisse, falls nötig)

2. Wechseln Sie zu Administration > Plugins
3. Wählen Sie die Aktion  "Aktualisieren" für das OERinForm-Plugin
4. Wählen Sie die Aktion  "Aktivieren" für das OERinForm-Plugin
5. Wählen Sie die Aktion "Konfigurieren" für das OERinForm-Plugin

### Update auf ILIAS 8

Verwenden Sie die Version aus dem GitHub-Branch main-ilias8

Das Setup von ILIAS 8 enthält eine Daten-Migration der vorhandenen CC-Lizenzen. Diese müssen Sie aufrufen, damit das Plugin die Lizenzen erkennt und zur Auswahl anbieten kann:

````
php setup/setup.php migrate --run md.ilMDCopyrightMigration
````

## Konfiguration

In der Konfiguration können Sie eine Kategorie aus dem öffentlichen Bereich von ILIAS auswählen, in welcher die veröffentlichten Inhalte verknüpft werden sollen.
Die Rechtevoreinstellungen in dieser Kategorie müssen alle neuen Objekte für anonyme Nutzer lesbar machen.

Daneben können Sie Web-Adressen von Hilfeseiten angeben, die auf den Seiten zur Veröffentlichung eines Inhalts verlinkt werden.

## Verwendung

Auf den Export-Reiter von Objekten, die als OER in Frage kommen (z.B. Dateien, Lernmodule, Glossare) wird der Status der Veröffentlichung angezeigt.
Über den Button "Veröffentlichen" startet ein Assistent, der Sie in fünf Schritten durch die notwendigen Prüfungen und Eingaben leitet. Wurden alle notwendigen
Prüfpunkte von Ihnen bestätigt, können Sie den Inhalt freigeben. Das Objekt wird dann in der öffentlichen Kategorie verlinkt und es werden seine Metadaten zur Indizierung
durch einen OAI-Server exportiert.

## OAI-Export
Die Open Archives Initiative (OAI) ist eine Initiative von Betreibern von Preprint- und anderen Dokumentenservern,
um die auf diesen Servern abgelegten elektronischen Publikationen im Internet besser auffindbar und nutzbar zu machen.

Mit der Veröffentlichung der Metadaten Ihres Lerninhalts über OAI ermöglichen Sie es öffentlichen Verzeichnisse von OER, diese Metadaten zu indizieren.
Damit kann Ihr Lerninhalt auch außerhalb von ILIAS über die Portale dieser Verzeichnisse gesucht werden.

Die Metadaten werden im geschützten Datenverzeichnis Ihrer Installation in verschiedenen Formaten unter oerinf/publish exportiert.
Zur Veröffentlichung müssen Sie einen OAI-Server installieren, der diese Metadaten indiziert und online abgefragt werden kann.
Eine Möglichkeit ist die Java/Tomcat-basierte Software jOAI:
https://github.com/NCAR/joai-project

Die Adresse Ihres jOAI-Servers müssen Sie dann bei OER-Portalen bekanntgeben, so dass diese von Ihrem Server die Metadaten der veröffentlichten Inhalte abfragen.
