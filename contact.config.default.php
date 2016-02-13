<? /*************************************************************************
	* Doit contenir un champ nommé "email" et un champ nommé "sujet".
	*************************************************************************/

/**************************************************************************
		configuration de base
 **************************************************************************/

/* Set the right smtp */

ini_set('SMTP', '<smtp>');

/* Adresse email du destinataire du formulaire. On peut donner plusieurs
 * adresses séparées par des virgules.
 * Exemples : $to = 'nom.prenom@fournisseur.fr';
 *            $to = 'dest1@fournisseur.fr, dest2@fournisseur.fr';
 */
$to = '<mail>';

/* Liste des champs obligatoires.
 * Mettre entre guillemets les noms des éléments de formulaires, séparés par des virgules.
 * Respecter les majuscules/minuscules !
 * Exemples :
 *		$champs_obligatoires = array("email", "sujet", "nom", "prenom", "adresse");
 *		$champs_obligatoires = array();			<-- aucun champ obligatoire
 */
$champs_obligatoires = array();




/**************************************************************************
		configuration avancée - options
 **************************************************************************/
// Si vous voulez récupérer plus facilement les données dans un tableur
// par copier-coller, mettez à 1 cette variable :
$formater_pour_tableur = 0;

// Par défaut les champs non obligatoires vides ne sont pas envoyés dans le mail.
// Mettez 1 si vous tenez à les recevoir quand-même (sous forme "nom=" suivi de rien).
$envoyer_aussi_les_champs_vides = 1;

// Vers quelle page html sera redirigé le visiteur après l'envoi du formulaire ?
$page_ok = "confirmation.php";

// Quelle page html afficher en cas d'erreur ?
$page_erreur = "contacterreur.php";

// Mettre éventuellement ici une adresse d'expéditeur si elle n'est pas
// précisée dans le formulaire.
// Sans expéditeur, le mail risque d'être rejeté par les passerelles anti-spam
// et ne jamais arriver à destination.
$from = 'mob-60@hotmail.fr';

// Mettre éventuellement ici un sujet de mail s'il n'est pas précisé dans
// le formulaire.
$sujet = "Formulaire du site";


// Mettre ici les noms des variables HTTP à envoyer avec le mail, entre guillemets
// et séparés par des virgules.
// Noms possibles : voir les variables de serveur sur
// http://www.php.net/manual/fr/reserved.variables.php
// Exemple : $variables_http = array('REMOTE_ADDR', 'HTTP_USER_AGENT');
$variables_http = array();

// Option : enregistrement dans une base de données.
// Indiquer le login et le mot de passe de la base de données.
// Indiquer aussi le nom des champs et des variables HTTP à enregistrer
// dans la table (Ne pas utiliser le nom de champ "n").
$db_login = '<login>';
$db_password = '<pwd>';
$db_champs_a_enregistrer = array("email", "sujet");
$db_nom_de_la_table = 'contact.php';

?>