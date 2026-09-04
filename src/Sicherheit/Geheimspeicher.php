<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Sicherheit;

/**
 * Verschlüsselt die Postfach-Kennwörter, bevor sie in die Datenbank gehen.
 *
 * Contao bringt dafür nichts Brauchbares mehr mit: Die Klasse
 * `Contao\Encryption` und die DCA-Angabe `'encrypt' => true` gibt es zwar in
 * 4.13 noch, in Contao 5.7 aber nicht mehr — dort landete der Wert
 * stillschweigend im Klartext in der Tabelle. Deshalb übernimmt das Bundle
 * die Verschlüsselung selbst, mit libsodium und in beiden Contao-Fassungen
 * gleich.
 *
 * Der Schlüssel wird aus dem Anwendungsgeheimnis (`kernel.secret`, also
 * APP_SECRET) abgeleitet. Wird dieses Geheimnis ausgetauscht, sind die
 * gespeicherten Kennwörter nicht mehr lesbar und müssen neu eingetragen
 * werden. Das ist beabsichtigt: Ein Datenbank-Abzug allein reicht damit nicht
 * aus, um an die Postfächer zu kommen.
 */
class Geheimspeicher
{
    /**
     * Kennzeichnet einen verschlüsselten Wert in der Datenbankspalte.
     *
     * Ohne diese Kennung ließe sich ein verschlüsselter Wert nicht von einem
     * Kennwort unterscheiden, das zufällig wie Base64 aussieht.
     */
    private const PRAEFIX = 'sodium:v1:';

    /**
     * Der abgeleitete Schlüssel, 32 Byte, oder null bis zur ersten Nutzung.
     *
     * @var string|null
     */
    private ?string $schluessel = null;

    /**
     * Nimmt das Anwendungsgeheimnis entgegen, aus dem der Schlüssel entsteht.
     *
     * @param string $appSecret Wert des Container-Parameters `kernel.secret`.
     *                          Er wird nicht unmittelbar als Schlüssel
     *                          benutzt, weil seine Länge nicht festgelegt ist;
     *                          die Ableitung erfolgt erst beim ersten Zugriff.
     */
    public function __construct(private readonly string $appSecret)
    {
    }

    /**
     * Verschlüsselt eine Zeichenkette für die Ablage in der Datenbank.
     *
     * Jeder Aufruf erzeugt eine neue Zufallszahl (Nonce), zwei Aufrufe mit
     * demselben Klartext liefern also unterschiedliche Ergebnisse. Das ist
     * gewollt und verhindert, dass sich aus der Tabelle ablesen lässt, welche
     * Listen dasselbe Kennwort benutzen.
     *
     * Ein leerer Klartext bleibt leer — ein leeres Feld soll in der Datenbank
     * auch leer aussehen und nicht als scheinbar gesetztes Kennwort gelten.
     *
     * @param string $klartext Das zu schützende Kennwort
     *
     * @return string Der Wert mit Kennung und Base64-Hülle, oder ''
     */
    public function verschluesseln(string $klartext): string
    {
        if ('' === $klartext) {
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $geheim = sodium_crypto_secretbox($klartext, $nonce, $this->schluessel());

        return self::PRAEFIX.base64_encode($nonce.$geheim);
    }

    /**
     * Stellt den Klartext eines gespeicherten Wertes wieder her.
     *
     * Werte ohne Kennung werden unverändert zurückgegeben. Damit bleiben
     * Kennwörter lesbar, die vor der Einführung dieser Klasse oder von Hand
     * per SQL eingetragen wurden; beim nächsten Speichern im Backend werden
     * sie verschlüsselt.
     *
     * @param string $wert Der Inhalt der Datenbankspalte
     *
     * @return string Der Klartext, oder '' wenn der Wert beschädigt ist oder
     *                mit einem anderen Anwendungsgeheimnis verschlüsselt wurde
     */
    public function entschluesseln(string $wert): string
    {
        if ('' === $wert || !str_starts_with($wert, self::PRAEFIX)) {
            return $wert;
        }

        $roh = base64_decode(substr($wert, \strlen(self::PRAEFIX)), true);

        // Zu kurz heißt: Der Wert kann nicht einmal die Nonce enthalten.
        if (false === $roh || \strlen($roh) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }

        $nonce = substr($roh, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $geheim = substr($roh, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $klartext = sodium_crypto_secretbox_open($geheim, $nonce, $this->schluessel());

        // false steht für eine fehlgeschlagene Prüfsumme — meist ein
        // gewechseltes APP_SECRET. Eine Ausnahme wäre hier unangebracht: Der
        // Cronjob soll die übrigen Listen weiter abarbeiten.
        return false === $klartext ? '' : $klartext;
    }

    /**
     * Sagt, ob ein Wert bereits in verschlüsselter Form vorliegt.
     *
     * Wird vom Speicher-Rückruf der DCA gebraucht, um ein unverändert
     * zurückgesendetes Kennwort nicht ein zweites Mal zu verschlüsseln.
     *
     * @param string $wert Der zu prüfende Wert
     *
     * @return bool true, wenn der Wert die Kennung dieser Klasse trägt
     */
    public function istVerschluesselt(string $wert): bool
    {
        return str_starts_with($wert, self::PRAEFIX);
    }

    /**
     * Leitet den 32 Byte langen Schlüssel aus dem Anwendungsgeheimnis ab.
     *
     * Das Ergebnis wird für die Lebensdauer des Objekts behalten, damit der
     * Hash nicht bei jedem einzelnen Feld erneut berechnet wird. `generichash`
     * (BLAKE2b) liefert bei beliebiger Eingabelänge genau die von
     * `secretbox` verlangten SODIUM_CRYPTO_SECRETBOX_KEYBYTES.
     *
     * @return string Der Schlüssel als Rohbytes
     */
    private function schluessel(): string
    {
        return $this->schluessel ??= sodium_crypto_generichash(
            'contao-mailingliste:'.$this->appSecret,
            '',
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
        );
    }
}
