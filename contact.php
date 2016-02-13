<? /*************************************************************************
	* contact.php
	* Copyright (c) François Pirsch 2007
	* http://aspirine.org/contact/
	* Distribué sous licence BSD.
	*
	*	12 avril 2007
	*
	* Envoi par mail des données d'un formulaire de contact.
	* Le formulaire lui-même doit être dans une page html séparée,
	* il doit être envoyé vers ce script php avec la méthode POST.
	* 
	* Il doit contenir un champ nommé "email"
	* et un champ nommé "sujet" ou "subject".
	* Tout champ nommé "email2" sera ignoré.
	*************************************************************************/

include 'contact.config.php';

// Vérifie l'existence du domaine indiqué.
function HostExists($domaine) {
  if (preg_match("/^([0-9]{1,3}\\.){3}[0-9]{1,3}$/", GetHostByName($domaine))) return true;
  // Si la vérification a échoué, on réessaie éventuellement en ajoutant "www."
  // C'est un peu foireux dans le cas des domaines avec uniquement un MX et pas de serveur web,
  // Mais ça fonctionne dans la plupart des cas.
  // Voir les fonctions checkdnsrr() ou getmxrr(), qui ne sont pour l'instant pas dispo
  // sous windows.
  if(substr($domaine, 0, 4) == "www.") return false;
  return (preg_match("/^([0-9]{1,3}\\.){3}[0-9]{1,3}$/", GetHostByName("www.$domaine"))) ? true : false;
}

// Vérifie la validité de l'adresse.
function AdresseValide($adresse) {
	if(strlen($adresse) > 100) return false;
	$atom = "[!#-'*+\\-\\/-9=?A-Z^-~]+";
	$regex_adresse = "/^$atom(\\.$atom)*@$atom(\\.$atom)*\\.[a-zA-Z]{2,4}$/";
	if(!preg_match($regex_adresse, $adresse)) return false;
	// On sait qu'on a un @ et qu'il est bien placé.
	return HostExists(substr($adresse, strpos($adresse, '@')+1));
}

// Quoted Printable. Conforme au RFC 2045 - http://rfc.net/rfc2045.html ?
function QPencode($str, $iso_tag)
{
    global $is_quoted;
    $is_quoted = false;
    define('CRLF', "\r\n");
    
    $lines = preg_split("/\r?\n/", $str);
    $out     = '';
    
    foreach ($lines as $line)
    {
        $newpara = '';
        
        for ($j = 0; $j <= strlen($line) - 1; $j++)
        {
            $char = substr ( $line, $j, 1 );
            $ascii = ord ( $char ); 
            
            if ( $ascii < 32 || $ascii == 61 || $ascii > 126 ) 
            {
                 $char = '=' . strtoupper ( dechex( $ascii ) );
                 $is_quoted = true;
            }
            
            if ( ( strlen ( $newpara ) + strlen ( $char ) ) >= 76 ) 
            {
                $out .= $newpara . '=' . CRLF;   $newpara = '';
                $is_quoted = true;
            }
            $newpara .= $char;
        }
        $out .= $newpara;
    }
    $out = trim ( $out );   
    // Ici on perd la conformité RFC 2045
    if($is_quoted && $iso_tag) $out = "=?ISO-8859-1?Q?".ereg_replace("\\?", "=3F", $out)."?=";
    return $out;
}

// Filtre une chaîne entrée par l'utilisateur.
// On interdit tous les caractères non ISO-8859 (heu ,en gros)
function filtre_securite($s) {
	return preg_replace('/[^\\x20-\\x7f\\xa0-\\xff]/', '', $s);
}

function apostrophes($s) {
	return preg_replace('/\\\\(["\'])/', '$1', $s);
}



/*
 * Enregistrement dans une base de données;
 */
function enregistre_bdd() {
	global $db_login;
	global $db_password;
	global $db_nom_de_la_table;
	global $db_champs_a_enregistrer;
	global $db_enregistrement;
	global $message;
	global $separateur;

	if(!($dbLink = @mySql_connect('localhost', $db_login, $db_password)))
		return "Impossible de se connecter à la base de données.\n";
	mySql_select_db($db_login, $dbLink);

	// On crée la table et ses colonnes selon les besoins.
	if(!mySql_query("CREATE TABLE IF NOT EXISTS `$db_nom_de_la_table` (`n` INT UNSIGNED AUTO_INCREMENT, KEY `n` (`n`));", $dbLink))
	return "Erreur à la création de la table\n";

	$db_result = mySql_query("SHOW COlUMNS FROM `$db_nom_de_la_table`;", $dbLink);
	$db_champs = array();
	while ($row = mysql_fetch_array($db_result, MYSQL_NUM))
		$db_champs[$row[0]] = 1;
	$champs_a_ajouter = array();
	foreach($db_champs_a_enregistrer as $champ) {
		if(!$db_champs[$champ])
			array_push($champs_a_ajouter, "ADD `$champ` TEXT");
	}
	if(count($champs_a_ajouter) &&
		!mySql_query("ALTER TABLE `$db_nom_de_la_table` ".implode(', ', $champs_a_ajouter).";"))
		return "Erreur en ajoutant les champs à la table.\n";

	// Préparation des données à enregistrer.
	$noms = '(';
	$valeurs = 'VALUES (';
	foreach($db_enregistrement as $nom => $valeur) {
		$noms .= "`$nom`, ";
		$valeurs .= "'".mysql_real_escape_string($valeur, $dbLink)."', ";
	}
	$noms = substr($noms, 0, -2) . ')';
	$valeurs = substr($valeurs, 0, -2) . ')';

	// Insertion dans la base de données.
	if(!mySql_query("INSERT INTO `$db_nom_de_la_table` $noms $valeurs;", $dbLink))
		return "Erreur à l'enregistrement dans la table.\n$noms\n$valeurs";
	$message = "Courrier numéro$separateur".mySql_insert_id($dbLink)."\n" . $message;
	return "";
}

/*
 * Initialisations.
 */
$erreur = '';
$message = '';
$separateur = ' = ';
if($formater_pour_tableur)
	$separateur = "\t";
$horizontal_rule = str_repeat('-', 64);
$sujet = QPencode(apostrophes($sujet), true);
$db_enregistrement = array();

// Disponible seulement à partir de php 5.2
//$champs_a_enregistrer = array_fill_keys($db_champs_a_enregistrer, 1);
$hash_champs_a_enregistrer = array();
foreach($db_champs_a_enregistrer as $key)
	$hash_champs_a_enregistrer[$key] = 1;


/*
 * Vérification de la présence des champs obligatoires.
 * On tient compte de la présence d'étoiles au début
 * des noms de champs, pour la vérification en JS.
 */
foreach($champs_obligatoires as $champ) {
	$valeur = $_POST[$champ];
	if(!$valeur || preg_match("/^[\\n\\r]*(.)\\1*[\\n\\r]*$/", $valeur))
		$erreur .= "Le champ $champ est obligatoire.\n";
}
if($erreur) $erreur .= "\n";

/*
 * Récupération et préparation des données du formulaire.
 */
foreach($_POST as $key=>$value) {
	$lkey = strtolower($key);
	if($hash_champs_a_enregistrer[$key])
		$db_enregistrement[$key] = apostrophes($value);

	$ligne_a_envoyer = '';
	if($lkey === 'email') {
		// Adresse de l'expéditeur.
		if(AdresseValide(trim($value))) {
			$from = trim($value);
			$ligne_a_envoyer = $key.$separateur.$from . "\n";
		} else
			$erreur .= "Votre adresse email est invalide.\n";
	}
	elseif(($lkey === 'sujet') || ($lkey === 'subject')) {
		// Le sujet est limitée à 100 caractères pour éviter les buffer oveflows.
		$sujet = QPencode(apostrophes(filtre_securite(substr($value, 0, 100))), true);
		$ligne_a_envoyer = $key.$separateur.apostrophes(preg_replace("/\\r?\\n/", "\n\t", $value)) . "\n";
	} elseif($lkey === 'email2') {
		if($value !== $from)
			$erreur .= "Il y a une faute de frappe entre les deux adresses email.\n";
	} else {
		// N'importe quel autre élément du formulaire :
		if(is_array($value)) $value = implode("\n", $value);
		$ligne_a_envoyer = $key.$separateur.apostrophes(preg_replace("/\\r?\\n/", "\n\t", $value)) . "\n";
	}

	if($value || $envoyer_aussi_les_champs_vides)
		$message .= $ligne_a_envoyer;
}

if($message && count($variables_http)) $message .= "$horizontal_rule\n";
foreach($variables_http as $nom) {
	$message .= "$nom$separateur$_SERVER[$nom]\n";
	if($hash_champs_a_enregistrer[$nom])
		$db_enregistrement[$nom] = $_SERVER[$nom];
}


if(!$message) $erreur .= "Pas de données à envoyer\n";

/*
 * Envoi des résultats.
 */
if($to)
{
	// Option : enregistrement dans la base de données
	if (!$erreur && $db_login && $db_password && $db_nom_de_la_table && count($db_champs_a_enregistrer))
		$erreur .= enregistre_bdd();

	// On ajoute un en-tête du type "Envoyé le lundi 10 février 2007 à 15h03 par joe@saloon.fr"
	setlocale (LC_TIME, 'fr_FR');
	$message = "Envoyé le ".strftime("%A %d %B %Y à %Hh%M")." par $from\n$horizontal_rule\n$message";

	// Si on a une adresse de destinataire, on envoie un mail.
	$headers = "From: $from\r\nReturn-Path: $from\r\n";
	if(!$erreur &&!mail($to, $sujet, $message, $headers))
		$erreur = 'Problème technique lors de l\'envoi du mail. Pourtant il n\'y avait pas de souci dans le formulaire.';

	// On utilise include() plutôt que readfile() sinon on ne peut pas
	// mettre un fichier php.
	if($erreur) {
			$erreur = str_replace("\n", "<br />\n", $erreur);
				if((substr($page_erreur, -5) == '.html') || (substr($page_erreur, -4) == '.htm'))
					print ereg_replace("##+\\s*ERREUR\\s*##+", $erreur, file_get_contents($page_erreur));
				else
				include($page_erreur);
				}
	else include($page_ok); //'confirmation.html'  include
}
else
{
	// Pas d'adresse où envoyer le mail, on passe en mode DEBUG
	// et on renvoie au navigateur pour affichage direct.
	print "<h1>contact.php</h1>\n";
	print "<h2>Mode DEBUG</h2>\n";
	print "Aucune adresse de destinataire n'est précisée dans le fichier de configuration contact.config.php.\n";
	print "<pre>Redirection succès : <a href=\"$page_ok\">$page_ok</a>\n";
	print "Redirection erreur : <a href=\"$page_erreur\">$page_erreur</a>\n\n";
	if($erreur)
		print "ERREUR : $erreur\n\n";
	print "</pre>\nVoici le mail qui pourrait être envoyé (s'il n'y a pas d'erreur) :\n<pre>";
	print "De : $from\nSujet : $sujet\n\n$message</pre>";
}
?>