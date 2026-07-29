# SPEC – local_admincockpit[cite: 1]

Navigations- und Kennzahlen-Dashboard für Moodle-Administratoren.[cite: 1] Bündelt Nutzer-/Kurs-Kennzahlen pro "Schule", Health-Signale mit Call-to-Action sowie Direktlinks zu häufig genutzten Verwaltungsseiten.[cite: 1] Das System wird punktuell um direkte Ausführungsbefehle (Schnellaktionen) ergänzt, um tief verschachtelte Moodle-Menüs zu umgehen.

---

## 1. Zweck

Ein Admin verbringt aktuell Zeit damit, über mehrere Menüpunkte verteilt den Zustand der Instanz zu erfassen (Nutzerzahlen, Kurszahlen pro Schule, Datenhygiene-Probleme).[cite: 1] Das Dashboard bündelt das auf einer Seite:[cite: 1]

- **Kennzahlen** – Ist-Zustand + Veränderung über einen einstellbaren Zeitraum[cite: 1]
- **Health-Signale** – Zahl + direkter Klick zur Behebung/Prüfung (kein reines Reporting)[cite: 1]
- **Schnellaktionen** – Unmittelbare Ausführung kritischer und häufiger Admin-Tasks direkt aus dem Dashboard heraus (z. B. Identitätswechsel, Cache-Management).
- **Navigation** – Kurzwege zu Verwaltungsaufgaben, gruppiert nach Aufgabentyp und pro Schule[cite: 1]

Kein Nachbau von Moodle Workplace Report Builder – bewusst schlanker, auf die konkreten Bedürfnisse dieser Instanz zugeschnitten.[cite: 1]

---

## 2. Grundkonzept "Schule"

Eine Schule besteht aus zwei unabhängig gepflegten Moodle-Objekten, die über ein gemeinsames Kürzel (`idnumber`) verknüpft werden:[cite: 1]

- einer **globalen Kohorte** (`idnumber` = Kürzel, z. B. `TBZ`)[cite: 1]
- einer **Top-Level-Kurskategorie** (`idnumber` = dasselbe Kürzel)[cite: 1]

Es gibt keine feste Namenskonvention (Anzeigenamen können frei bleiben), das Matching läuft ausschliesslich über `idnumber`.[cite: 1] `idnumber` ist ein freier String (alphanumerisch), Kürzel wie `TBZ` sind also problemlos möglich.[cite: 1]

**Wichtig:** Nicht jede Top-Level-Kategorie und nicht jede globale Kohorte ist zwangsläufig eine "Schule" – es können weitere Kategorien/Kohorten ohne Dashboard-Bezug existieren.[cite: 1] Das Plugin muss daher:[cite: 1]

1. Alle Kürzel ermitteln, bei denen sowohl eine Kohorte als auch eine Top-Level-Kategorie mit identischer `idnumber` existieren (= vollständige Paare)[cite: 1]
2. Kürzel mit nur einseitigem Match (Kohorte ohne Kategorie oder umgekehrt) in den Plugin-Einstellungen als Warnung anzeigen, nicht stillschweigend ignorieren[cite: 1]
3. Aus den vollständigen Paaren eine Auswahl-Liste bauen, aus der der Admin die auf dem Dashboard anzuzeigenden Kürzel per Mehrfachauswahl wählt[cite: 1]

Es wird **keine eigene Zuordnungstabelle** in der Datenbank benötigt – die Zuordnung wird zur Laufzeit über `idnumber` aufgelöst; nur die Auswahl ("welche Kürzel sind aktiv") wird als Plugin-Setting gespeichert.[cite: 1]

---

## 3. Kennzahlen

Alle "im Zeitraum"-Werte beziehen sich auf einen global einstellbaren Zeitraum (Standardwerte 30 / 90 / 180 / 360 Tage, als Admin-Setting wählbar, keine Snapshot-Historie – reiner `timecreated`-Filter).[cite: 1]

### Global
| Kennzahl | Quelle / Logik |
|---|---|
| Nutzer gesamt[cite: 1] | Anzahl aktive (nicht gelöschte) Nutzerkonten[cite: 1] |
| davon aktiv | `lastaccess` innerhalb des gewählten Zeitraums (**revidiert 2026-07-29**: teilt sich den globalen Zeitraum aus Abschnitt 3 mit "Neue Nutzer", kein eigener Fixwert mehr – siehe §11 "Bewusst nicht weiterverfolgt") |
| Neue Nutzer im Zeitraum[cite: 1] | `timecreated` innerhalb des gewählten Zeitraums[cite: 1] |

### Pro Schule (für jedes ausgewählte Kürzel)
| Kennzahl | Quelle / Logik |
|---|---|
| Mitgliederzahl[cite: 1] | Kohorten-Mitglieder (`cohort_members`)[cite: 1] |
| Neuzugänge im Zeitraum[cite: 1] | `cohort_members.timeadded` innerhalb Zeitraum[cite: 1] |
| Aktive Mitglieder | Mitglieder mit `lastaccess` innerhalb des gewählten Zeitraums (**revidiert 2026-07-29**: teilt sich den globalen Zeitraum aus Abschnitt 3 mit "Neuzugänge", kein eigener Fixwert mehr, analog zu "davon aktiv" global – siehe §11 "Bewusst nicht weiterverfolgt") |
| Kurszahl[cite: 1] | Kurse in der zugeordneten Top-Level-Kategorie, **ohne** Subkategorie-Aufschlüsselung – zu klären: zählen Kurse in Subkategorien mit oder nur direkt in der Top-Level-Kategorie? (Annahme: inkl. Subkategorien, aber ohne separate Anzeige je Subkategorie – bitte bei Umsetzung bestätigen)[cite: 1] |
| Neue Kurse im Zeitraum[cite: 1] | `course.timecreated` innerhalb Zeitraum, gefiltert auf die Kategorie[cite: 1] |

---

## 4. Health-Signale (v1)

Jedes Health-Signal ist eine Zahl **mit Klick-Ziel** (Call-to-Action) – keine reine Statistik-Kachel.[cite: 1]

| Signal | Logik | Klick-Ziel |
|---|---|---|
| Doppelte E-Mail-Adressen[cite: 1] | Nutzer mit identischer `email`, gruppiert[cite: 1] | Eigene Liste im Plugin mit betroffenen Nutzerpaaren/-gruppen, als Vorbereitung für `tool_mergeusers`[cite: 1] |
| Kurse ohne Enddatum[cite: 1] | `course.enddate = 0`[cite: 1] | Eigene gefilterte Kursliste im Plugin, von dort Sprung in die jeweiligen Kurseinstellungen[cite: 1] |
| Security-Overview-Ampel[cite: 1] | Aggregierter Status der Core-Security-Checks (grün/gelb/rot)[cite: 1] | Direktlink zur bestehenden Seite Site administration → Reports → Security overview[cite: 1] |
| Cron-Status[cite: 1] | Zeitpunkt letzter Cron-Lauf + Anzahl fehlgeschlagener Scheduled Tasks[cite: 1] | Direktlink zur bestehenden Scheduled-Tasks-Übersicht[cite: 1] |

**Bewusst nicht in v1:** unbestätigte Konten, auth-Methoden-Übersicht, Plugin-Update-Übersicht, Kurse ohne Teilnehmer/Lehrperson (mögliche v2-Kandidaten).[cite: 1]

**Offen bei Umsetzung:** exakte API/Query zur Aggregation der Security-Overview-Ergebnisse (Core-Klasse identifizieren, nicht neu erfinden) und exakte URL/Parameter der Scheduled-Tasks-Übersicht – vor dem Bau im Code verifizieren.[cite: 1]

---

## 5. Schnellaktionen (Quick Actions)

Das Dashboard wird um operative Werkzeuge erweitert. Dies ersetzt den mühsamen Weg durch die Standard-Navigation für Routineeingriffe.

**One-Click-Impersonate (Schnell-Login)**
- **Logik:** Ein Moodle Auto-Complete-Formularelement direkt auf dem Dashboard. Ermöglicht die sofortige Suche nach Nutzern (Name oder E-Mail). Ein Klick auf den Suchtreffer löst direkt den "Login As"-Prozess aus.
- **Technische Umsetzung:** Aufruf von `\core\session\manager::loginas($userid, $context)`.
- **Sicherheitsvorgabe:** Zwingende Prüfung der Capability `moodle/user:loginas` im Systemkontext. Um Privilege Escalation zu verhindern, muss programmatisch ausgeschlossen werden, dass sich ein Administrator über diese Funktion als Site-Admin einloggt.

**Quick Cache Purge & Debug Switch**
- **Logik:** Zwei funktionale Steuerelemente auf dem Dashboard. Ein Button für "Purge all caches" und ein direkter Toggle-Switch für "Developer Debug Mode On/Off".
- **Technische Umsetzung:** Nutzung der Core-Funktion `purge_all_caches()`. Für das Debugging wird `set_config('debug', DEBUG_DEVELOPER)` sowie `set_config('debugdisplay', 1)` aufgerufen (bzw. `0` bei Deaktivierung).
- **Sicherheitsvorgabe:** Diese Eingriffe in die Systemkonfiguration erfordern strikten CSRF-Schutz via `require_sesskey()` und die Prüfung der Berechtigung `moodle/site:config`.

---

## 6. Navigation

Gruppiert nach Aufgabentyp, reine Links ohne Logik:[cite: 1]

**Pro Schule** (für jedes ausgewählte Kürzel, direkt bei der Schul-Kennzahlengruppe)[cite: 1]
- Link zur Kursverwaltung der zugeordneten Kategorie (`course/management.php?categoryid=X`)[cite: 1]

**Nutzerverwaltung**[cite: 1]
- Nutzer/innen hochladen[cite: 1]
- Kohorten verwalten / hochladen[cite: 1]
- Merge Users (`tool_mergeusers`)[cite: 1]

**Kursverwaltung**[cite: 1]
- Kurs anlegen[cite: 1]
- Kategorien verwalten[cite: 1]
- Kurs-Backup/-Restore[cite: 1]

**Berichte/Logs**[cite: 1]
- Report Builder (Custom Reports)[cite: 1]
- Site-Logs[cite: 1]
- Config-Change-Log (`report_configlog`)[cite: 1]

**System**[cite: 1]
- Scheduled Tasks Übersicht[cite: 1]
- Plugin-Übersicht (Site administration → Plugins → Plugins overview)[cite: 1]

**Theme/Erscheinungsbild**[cite: 1]
- Direktlink zu den Boost-Union-Theme-Einstellungen (genaue Section-URL bei Umsetzung im Code prüfen, Theme hat mehrere Tabs)[cite: 1]

---

## 7. Konfigurationsseite (Plugin-Settings)

- **Zeitraum** für "neu im Zeitraum"-Werte: Auswahl 30 / 90 / 180 / 360 Tage (einzelner globaler Wert für v1, kein individuelles Setting pro Kennzahl)[cite: 1]
- **Aktive Schul-Kürzel**: Mehrfachauswahl aus allen gefundenen vollständigen Kohorte/Kategorie-Paaren[cite: 1]
- **Warnliste**: schreibgeschützte Anzeige von Kürzeln mit nur einseitigem Match (Kohorte ohne Kategorie-Pendant oder umgekehrt)[cite: 1]

---

## 8. Technischer Aufbau (Vorschlag)

- **Plugin-Typ:** `local_admincockpit` – eigene Admin-Seite (`admin_externalpage`) unter Site administration → Reports, kein Block (vermeidet Block-Regionen-/Theme-Constraints)[cite: 1]
- **Kein eigenes DB-Schema nötig** – alle Werte werden zur Laufzeit berechnet (kein Snapshot-Mechanismus, da bewusst auf `timecreated`-Filter statt historischer Delta-Werte gesetzt)[cite: 1]
- **Capability:** `local/admincockpit:view`, Standard-Kontext System, nur für Nutzer mit Admin-Rolle vorgesehen[cite: 1]
- **Rendering:** eigener Renderer + Mustache-Templates für Kachel-Layout; Zahlen serverseitig berechnet, keine AJAX-Nachladelogik in v1[cite: 1]
- **Wiederverwendung Core-APIs wo sinnvoll:** z. B. bestehende Security-Overview-Logik referenzieren statt Checks neu zu implementieren[cite: 1]
- **Sicherheitskonzept Schnellaktionen:** Konsequente Nutzung von Moodle-Core-Funktionen (`\core\session\manager::loginas`, `purge_all_caches()`) in Verbindung mit strikter `sesskey`-Prüfung und spezifischen Capabilities (`moodle/user:loginas`, `moodle/site:config`), um Sicherheitsrisiken und unautorisierten Zugriff technisch auszuschliessen.

---

## 9. Offene Punkte vor Implementierungsstart

Stand Schritt 12 (siehe CLAUDE.md "Zwingende Recherche-Punkte" für die Fundstellen im Code): alle vier Punkte sind geklärt, hier zur historischen Nachvollziehbarkeit unverändert belassen.[cite: 1]

1. Zählt die Kurszahl pro Schule Kurse aus Subkategorien mit? (Annahme: ja, siehe Abschnitt 3) – ✅ bestätigt und umgesetzt (`classes/metrics/school_metrics.php`)[cite: 1]
2. Exakte Core-Klasse/-API zur Security-Overview-Aggregation identifizieren – ✅ `\core\check\manager::get_checks('security')` (`classes/metrics/health_signals.php`)[cite: 1]
3. Exakte URL/Parameter für Scheduled-Tasks-Übersicht und für gefilterte Nutzerlisten – ✅ `/admin/tool/task/scheduledtasks.php`; gefilterte Nutzerlisten wurden nicht benötigt (die Health-Signal-Klickziele sind eigene Drill-down-Seiten, siehe Abschnitt 4)[cite: 1]
4. Exakte Section-URL der Boost-Union-Theme-Einstellungen – ✅ `/theme/boost_union/settings_overview.php` (`classes/navitems_parser.php`)[cite: 1]

---

## 10. Explizit ausserhalb des Scopes (v1)

- Historische Trend-/Delta-Werte über Snapshot-Tabelle (siehe frühere Diskussion) – nur einfache `timecreated`-Filter[cite: 1]
- Weitere Health-Signale (unbestätigte Konten, auth-Mismatch, Plugin-Updates, Kurse ohne Teilnehmer/Lehrperson)[cite: 1]
- Automatisierte Zuordnung Kohorte↔Kategorie über etwas anderes als `idnumber`[cite: 1]
- Block-Variante (nur eigene Admin-Seite in v1)[cite: 1]
- Erstellung eines Dashboard-Widgets für beliebige, frei definierbare SQL-Queries (Aufwand und Komplexität stehen in keinem Verhältnis zum Release v1).

---

## 11. v2-Backlog (Stand nach Veröffentlichung v1)

### Neue Settings (Editable-Erweiterung)
| Setting | Beschreibung |
|---|---|
| Cron-Status-Fenster[cite: 1] | Auswahl 6/12/24/48h für die Zählung fehlgeschlagener Tasks[cite: 1] |
| Ignorierte Security-Checks[cite: 1] | Mehrfachauswahl der Check-IDs, die aus der Security-Overview-Ampel ausgeschlossen werden (relevant bei strukturell nicht behebbaren Warnungen im Managed Hosting)[cite: 1] |
| Boost-Union-Link-Sichtbarkeit[cite: 1] | automatisch ausblenden, wenn `theme_boost_union` nicht das aktive Theme ist (keine manuelle Checkbox nötig – zur Laufzeit prüfbar)[cite: 1] |

### Neues Health-Signal
| Signal | Logik | Klick-Ziel | Notiz |
|---|---|---|---|
| Kurse ohne Teilnehmer/Lehrperson[cite: 1] | Kurse ohne eingeschriebene Nutzer mit Rolle Student ODER ohne Rolle Teacher, sichtbar[cite: 1] | Eigene gefilterte Liste im Plugin, Link in die jeweiligen Kurseinstellungen[cite: 1] | Doppelter Nutzen vermutet: neben echten Karteileichen vermutlich auch ein guter Indikator für liegengebliebene Testkurse – bei der Umsetzung beide Fälle im Hinterkopf behalten (evtl. getrennt ausweisen, falls sich das als sinnvoll erweist)[cite: 1] |

### Bewusst nicht weiterverfolgt (endgültig verworfen, nicht nur vertagt)
- **Eigene "Aktiv-Schwelle" (1/2/4/8 Wochen) als separates Setting für "aktive Nutzer" (global) und "aktive Mitglieder" (pro Schule)**: verworfen, 2026-07-29. Statt eines zweiten, unabhängigen Zeitraum-Settings nutzen sowohl "davon aktiv" (global, `classes/metrics/user_metrics.php`) als auch "Aktive Mitglieder" (pro Schule, `classes/metrics/school_metrics.php`) jetzt denselben `local_admincockpit/timerangedays`-Wert wie "Neue Nutzer"/"Neuzugänge im Zeitraum" – ein gemeinsamer Zeitraum ist für Admins einfacher zu verstehen als zwei getrennte Werte.
- **Auth-Methoden-Mismatch als Health-Signal**: verworfen.[cite: 1] Begründung: das Problem trat einmalig im Rahmen der Migration auf und ist seither behoben; ausserdem gibt es legitime Konten mit `auth=manual`, wodurch ein pauschales Signal ohne Schul-spezifische Zusatzkonfiguration zu viele False Positives erzeugen würde.[cite: 1] Der Zusatzaufwand einer Pro-Schule-Konfiguration steht in keinem Verhältnis zum (einmaligen) Nutzen.[cite: 1]
- **Plugin-Update-Übersicht als Health-Signal**: verworfen zugunsten eines einfachen Navigationslinks zur bestehenden Plugin-Übersicht (siehe Abschnitt 6, System) – kein eigener Aggregations-Aufwand nötig für etwas, das primär ein "mal kurz nachschauen"-Bedürfnis ist, kein akutes Handlungssignal.[cite: 1]
- **Delegierte Schul-Admins/kontextsensitive Capability**: verworfen.[cite: 1] Das Dashboard bleibt bewusst ausschliesslich für system-weite Administratoren; keine eingeschränkte Pro-Schule-Sicht für Koordinatoren vorgesehen.[cite: 1] Damit bleibt die Capability-Prüfung überall (Seite + Navigationslinks) ein einfacher `has_capability()`-Check ohne Kategorie-/Kontext-Bezug.[cite: 1]

### Umgesetzt nach v1-Veröffentlichung
| Punkt | Details |
|---|---|
| Caching[cite: 1] | Moodle Cache API (MUC), Application-Cache, TTL 1 Tag, plus manueller "Cache jetzt leeren"-Button direkt auf der Dashboard-Seite (nicht nur über die generelle Cache-Verwaltung)[cite: 1] |
| Bootstrap-4-Bereinigung[cite: 1] | Vollständige Durchsicht aller Templates auf verbliebene BS4-Klassennamen, Ersatz durch BS5-Äquivalente[cite: 1] |
| Dashboard-Event[cite: 1] | `local_admincockpit\event\dashboard_viewed`, erscheint in Site-Logs[cite: 1] |

### Weiterhin unverändert im Backlog (noch keine Entscheidung)
- Historische Trend-/Delta-Werte über Snapshot-Tabelle[cite: 1]
- Abgelaufene Einschreibungen (`enrolenddate` in der Vergangenheit, Status aktiv)[cite: 1]
- Selbsteinschreibung ohne Schlüssel/ohne Enddatum[cite: 1]
- Block-Variante[cite: 1]
- Automatisierte Kohorte↔Kategorie-Zuordnung jenseits von `idnumber`[cite: 1]