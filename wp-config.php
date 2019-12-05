<?php
/**
 * Baskonfiguration för WordPress.
 *
 * Denna fil innehåller följande konfigurationer: Inställningar för MySQL,
 * Tabellprefix, Säkerhetsnycklar, WordPress-språk, och ABSPATH.
 * Mer information på {@link http://codex.wordpress.org/Editing_wp-config.php 
 * Editing wp-config.php}. MySQL-uppgifter får du från ditt webbhotell.
 *
 * Denna fil används av wp-config.php-genereringsskript under installationen.
 * Du behöver inte använda webbplatsen, du kan kopiera denna fil direkt till
 * "wp-config.php" och fylla i värdena.
 *
 * @package WordPress
 */

// ** MySQL-inställningar - MySQL-uppgifter får du från ditt webbhotell ** //
/** Namnet på databasen du vill använda för WordPress */
define('DB_NAME', 'slapvagnar');

/** MySQL-databasens användarnamn */
define('DB_USER', 'root');

/** MySQL-databasens lösenord */
define('DB_PASSWORD', '');

/** MySQL-server */
define('DB_HOST', 'localhost');

/** Teckenkodning för tabellerna i databasen. */
define('DB_CHARSET', 'utf8');

/** Kollationeringstyp för databasen. Ändra inte om du är osäker. */
define('DB_COLLATE', '');

/**#@+
 * Unika autentiseringsnycklar och salter.
 *
 * Ändra dessa till unika fraser!
 * Du kan generera nycklar med {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Du kan när som helst ändra dessa nycklar för att göra aktiva cookies obrukbara, vilket tvingar alla användare att logga in på nytt.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '%?>!fUg)U)MYs^Shyp-Q)6/T`jdy<k6s[&N&b8igR*gs|t6,N?m#:RaodA{-/ZNj');
define('SECURE_AUTH_KEY',  '^(,e-73&a1}a1 |s,N![hoCdM,+H?(5}`!q|1WYi}1I+Hk$v6RRlS[5M@W<uPuw|');
define('LOGGED_IN_KEY',    'B7%=PR pLUO*%!j-aX2A Tp-g5:aqrxZyw(K}c5}KZ#oRsF~=O5~4F=S[A@n>Cw!');
define('NONCE_KEY',        '>c0q)ETZj!iKAJ?2t9;Y[4D0ha,A#e-R]hdITD~<Hj]?O-a3*a4&.7CUQ$Nhj+2|');
define('AUTH_SALT',        ';v|^$SmB~s!M4kz:eWnf}7J;Hh-rj -U=R^QKP[k#.$VZ&dtUv2@^se;f(uUM_6F');
define('SECURE_AUTH_SALT', ' 4+ZJ@LNao;tj>n)[SmIdVKw*GJT%JPG^Rj_@u]B#H9rq3L!.4u|waMAmj:67Pq5');
define('LOGGED_IN_SALT',   '.|NFJ%xS%<i Q9!}g@n+Ei2W:>+FuLS*QZA>B%~Yg,0lf0!+NTixDIwy(+v o83b');
define('NONCE_SALT',       ':NVCN~85Gs,)1wi+)!etzVT-wVCV-:2xM<fM;K~3HglD^gu<7)TF4I(3*-zws+(a');

/**#@-*/

/**
 * Tabellprefix för WordPress Databasen.
 *
 * Du kan ha flera installationer i samma databas om du ger varje installation ett unikt
 * prefix. Endast siffror, bokstäver och understreck!
 */
$table_prefix  = 'wp_';

/** 
 * För utvecklare: WordPress felsökningsläge. 
 * 
 * Ändra detta till true för att aktivera meddelanden under utveckling. 
 * Det är rekommderat att man som tilläggsskapare och temaskapare använder WP_DEBUG 
 * i sin utvecklingsmiljö. 
 */ 
define('WP_DEBUG', false);

/* Det var allt, sluta redigera här! Blogga på. */

/** Absoluta sökväg till WordPress-katalogen. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Anger WordPress-värden och inkluderade filer. */
require_once(ABSPATH . 'wp-settings.php');