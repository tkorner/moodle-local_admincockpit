<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'local_admincockpit', language 'de'.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activeschools'] = 'Aktive {$a}-Kürzel';
$string['allcachespurged'] = 'Alle Site-Caches geleert (Klassen-Map, Theme, Sprachstrings und mehr) - nicht nur die eigenen zwischengespeicherten Zahlen dieses Plugins.';
$string['activeschools_desc'] = 'Nur vollständig gepaarte Kürzel (Kohorte und Top-Level-Kategorie mit identischer idnumber) stehen hier zur Auswahl. Sie werden auf dem Dashboard angezeigt.';
$string['activeschools_option'] = '{$a->idnumber} ({$a->cohortname} / {$a->categoryname})';
$string['activeusers'] = 'Aktiv';
$string['activeusers_help'] = 'Konten mit einem Website-Zugriff (lastaccess) innerhalb des oben gewählten Zeitraums. Über das Dropdown oben auf der Seite änderbar.';
$string['admincockpit:view'] = 'Admin-Cockpit ansehen';
$string['backtodashboard'] = 'Zurück zum Dashboard';
$string['boostunionsettings'] = 'Boost-Union-Theme-Einstellungen';
$string['cachepurged'] = 'Cache geleert - die Zahlen unten sind frisch berechnet.';
$string['courseswithoutenddate'] = 'Kurse ohne Enddatum';
$string['courseswithoutenddate_none'] = 'Keine Kurse ohne Enddatum gefunden - hier gibt es nichts zu melden.';
$string['courseswithoutenddate_truncated'] = 'Zeigt die ersten 500 von {$a} Kursen ohne Enddatum.';
$string['dashboardintro'] = 'Übersicht über Nutzer- und Kursaktivität site-weit und pro {$a}, plus Datenhygiene- und Infrastruktur-Signale, die Aufmerksamkeit brauchen. Klick auf eine Health-Signal-Kachel öffnet die zugehörige Liste bzw. den Bericht.';
$string['duplicateemails'] = 'Doppelte E-Mail-Adressen';
$string['duplicateemails_none'] = 'Keine doppelten E-Mail-Adressen gefunden - hier gibt es nichts zu melden.';
$string['duplicateemails_truncated'] = 'Zeigt die ersten 500 von {$a} Gruppen doppelter E-Mail-Adressen.';
$string['eventdashboardviewed'] = 'Admin-Cockpit angesehen';
$string['groupinglabel'] = 'Bezeichnung der Gruppierung';
$string['groupinglabel_desc'] = 'Wie eine "Gruppierung" (eine Kohorte und eine Top-Level-Kategorie mit identischer idnumber) im Dashboard genannt werden soll - z.B. Schule, Standort, Abteilung oder Fakultät. Rein kosmetisch: ändert nur die Formulierung, nie die Zuordnungslogik oder Auswahl.';
$string['healthsignals'] = 'Reihenfolge / Sichtbarkeit der Health-Signale';
$string['healthsignals_available'] = 'Aktuell verfügbare Health-Signale (component:key):';
$string['healthsignals_desc'] = 'Ein Eintrag pro Zeile, mit den oben aufgeführten "component:key"-Kennungen. Ein aufgeführter Eintrag legt die Position dieses Signals fest (von oben nach unten); mit "-" davor wird es komplett ausgeblendet (z.B. "-local_admincockpit:security"). Ein hier nicht erwähntes Signal bleibt aktiv und wird nach den aufgeführten angezeigt, in der Reihenfolge, in der es beigetragen wurde - leer lassen zeigt also weiterhin jedes aktuell verfügbare Signal, und ein neu installiertes Drittanbieter-Signal erscheint automatisch, ohne diese Einstellung anzupassen.';
$string['lastcomputed'] = 'Stand: {$a}, wird täglich aktualisiert.';
$string['mergeusershint'] = 'Diese Liste ist ein Ausgangspunkt, um zusammenzuführende Konten zu identifizieren. '
    . 'Das Admin-Tool "Merge user accounts" (tool_mergeusers) ist auf dieser Instanz nicht installiert, daher '
    . 'wird hier kein direkter Link angezeigt - installiere es, um Konten tatsächlich zusammenzuführen.';
$string['mergeusershint_link'] = 'Diese Liste ist ein Ausgangspunkt, um zusammenzuführende Konten zu identifizieren. Nutze {$a} für die eigentliche Zusammenführung.';
$string['mergeuserslinktext'] = 'Merge user accounts';
$string['navgroup_courses'] = 'Kursverwaltung';
$string['navgroup_reports'] = 'Berichte/Logs';
$string['navgroup_system'] = 'System';
$string['navgroup_theme'] = 'Theme/Erscheinungsbild';
$string['navgroup_users'] = 'Nutzerverwaltung';
$string['navitems'] = 'Navigationslinks';
$string['navitems_desc'] = 'Ein Link pro Zeile: Titel|URL|Gruppe|Capability(optional). Gruppe ist die Kartenüberschrift, unter der der Link erscheint (Karten werden in der Reihenfolge angezeigt, in der ihr Gruppenname hier zuerst auftaucht). URL kann ein site-relativer Pfad (z.B. /cohort/index.php) oder eine vollständige externe URL sein und muss http(s) verwenden - andere Schemas (z.B. javascript:) werden abgelehnt. Ist eine Capability angegeben (z.B. moodle/site:config), muss sie tatsächlich existieren, und der Link wird nur Nutzer/innen mit dieser Capability im System-Kontext angezeigt; ohne Angabe sehen ihn alle, die dieses Dashboard sehen können. Nicht parsebare Zeilen werden übersprungen - siehe ggf. den Hinweis oberhalb.';
$string['navitems_parseerror'] = '{$a} Zeile(n) in der Navigationslinks-Einstellung unten konnten nicht geparst werden und wurden übersprungen. Jede Zeile braucht 3 oder 4 nicht-leere, mit "|" getrennte Teile: Titel|URL|Gruppe|Capability(optional).';
$string['newinperiod'] = 'Neu im Zeitraum';
$string['newinperiod_help'] = 'Zählt Datensätze (Nutzerkonten, Kohorten-Mitglieder bzw. Kurse), die innerhalb des oben gewählten Zeitraums erstellt bzw. hinzugefügt wurden. Über das Dropdown oben auf der Seite änderbar.';
$string['nonavitemsconfigured'] = 'Es sind keine Navigationslinks konfiguriert.';
$string['noschoolsconfigured'] = '0 Kürzel aktiv konfiguriert.';
$string['noschoolsconfigured_linktext'] = 'Zu den Einstellungen';
$string['onesided_categoryonly'] = '{$a}: Top-Level-Kategorie vorhanden, aber keine passende Kohorte';
$string['onesided_cohortonly'] = '{$a}: Kohorte vorhanden, aber keine passende Top-Level-Kategorie';
$string['onesided_intro'] = 'Diese Kürzel sind nur einseitig gepflegt (Kohorte oder Kategorie, nicht beides) und können nicht als aktive Kohorten-/Kategorie-Gruppierung ausgewählt werden:';
$string['onesided_none'] = 'Alle Kohorten und Top-Level-Kategorien mit idnumber sind vollständig gepaart - hier gibt es nichts zu melden.';
$string['onesidedwarning'] = 'Einseitige Zuordnungen';
$string['pluginname'] = 'Admin Cockpit';
$string['privacy:metadata'] = 'Das Admin-Cockpit-Plugin speichert keine personenbezogenen Daten. Alle angezeigten Zahlen werden bei Aufruf aus bestehenden Moodle-Core-Daten (Nutzer, Kohorten, Kurse, Task-Logs) berechnet und von diesem Plugin nirgendwo geschrieben.';
$string['purgeallcaches'] = 'ALLE Site-Caches leeren';
$string['purgecache'] = 'Jetzt aktualisieren';
$string['schoolcard_coursemanagement'] = 'Kursverwaltung';
$string['schooltile_activemembers'] = 'Aktive Mitglieder';
$string['schooltile_coursecount'] = 'Kurse';
$string['schooltile_membercount'] = 'Mitglieder';
$string['schooltile_newcourses'] = 'Neue Kurse';
$string['schooltile_newmembers'] = 'Neuzugänge';
$string['section_globalusers'] = 'Globale Nutzer-Kennzahlen';
$string['section_healthsignals'] = 'Health-Signale';
$string['section_navigation'] = 'Navigation';
$string['section_schools'] = 'Pro {$a}';
$string['signal_cron'] = 'Cron-Status';
$string['signal_cron_failedtasks'] = '{$a} fehlgeschlagene Task(s) in den letzten 24h.';
$string['signal_cron_help'] = 'Zeit seit dem letzten cron.php-Lauf und Anzahl der in den letzten 24 Stunden fehlgeschlagenen geplanten Tasks. Klick auf die Kachel öffnet die Übersicht der geplanten Tasks.';
$string['signal_cron_lastrun'] = 'Letzter Lauf vor {$a}.';
$string['signal_cron_neverrun'] = 'Cron ist noch nie gelaufen.';
$string['signal_security'] = 'Security-Overview';
$string['signal_security_error'] = '{$a} Fehler';
$string['signal_security_help'] = 'Aggregierter Status der Core-Security-Overview-Checks (dieselbe Prüfung wie unter Website-Administration → Berichte → Security-Übersicht). Klick auf die Kachel öffnet den vollständigen Bericht mit Details zu jeder Prüfung.';
$string['signal_security_ok'] = '{$a} OK';
$string['signal_security_warning'] = '{$a} Warnungen';
$string['tile_activeusers'] = 'Aktive Nutzer';
$string['tile_newusers'] = 'Neue Nutzer';
$string['tile_totalusers'] = 'Nutzer gesamt';
$string['timerange_label'] = 'Zeitraum:';
$string['timerange_submit'] = 'Anzeigen';
$string['timerangedays'] = 'Zeitraum für Neu-Zählungen';
$string['timerangedays_desc'] = 'Bestimmt, welche Nutzer, Kohorten-Mitglieder und Kurse auf dem Dashboard als "neu" gelten, sowie welche als "aktiv" zählen (gemeinsamer Zeitraum für beide Kennzahlen, kein separates Setting). Kann auf der Dashboard-Seite selbst temporär überschrieben werden, ohne diesen Standardwert zu ändern.';
$string['unpublishedcoursedays'] = 'Alters-Schwelle für unveröffentlichte Kurse';
$string['unpublishedcoursedays_desc'] = 'Ein versteckter Kurs zählt erst dann zum Health-Signal "Unveröffentlichte Kurse", wenn er mindestens so lange her erstellt wurde. Ein Kurs, der während der Vorbereitung versteckt ist, ist normal, kein Problem - diese Schwelle unterscheidet die beiden Fälle.';
$string['unpublishedcourses'] = 'Unveröffentlichte Kurse';
$string['unpublishedcourses_intro'] = 'Kurse, die seit mindestens {$a} Tagen für Teilnehmende versteckt sind - eine kürzere Zeit ist normal, während ein Kurs noch vorbereitet wird, solche werden hier nicht angezeigt.';
$string['unpublishedcourses_none'] = 'Keine lange versteckten Kurse gefunden - hier gibt es nichts zu melden.';
$string['unpublishedcourses_truncated'] = 'Zeigt die ersten 500 von {$a} lange versteckten Kursen.';
