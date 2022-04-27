
<?php
session_start();

include "twitteroauth.php";

define('CONSUMER_KEY','Notre comsumer key');
define('CONSUMER_SECRET' ,'Notre consumer secret');
define("OAUTH_CALLBACK", "http://votresite.fr/callback.php");

/* Créer une connexion avec Twitter */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);

$urlRedi = OAUTH_CALLBACK;

/* On demande les tokens à Twitter, et on passe notre url de callback */
$request_token = $connection->getRequestToken($urlRedi);

/* On sauvegarde ces informations en session */
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

/* On vérifie que notre requête précédente a correctement fonctionné */
switch ($connection->http_code) {
case 200:
/* On construit l'URL de callback avec les tokens en paramètres */
$url = $connection->getAuthorizeURL($token);
header('Location: ' .$urlRedi);
break;
default:
$contenu= '<div class="error">Impossible de se connecter à twitter...</div>';
break;
}
?>

 

Ce script nous permet donc de nous connecter à notre application et qui nous demandera logiquement l'autorisation de nous connecter à celle-ci ! Après cela, Twitter nous renvoie vers l'url de callback que nous avons spécifiée tout à l'heure. Voici notre script callback.php :

<?php

session_start();

include "twitteroauth.php";

define('CONSUMER_KEY','Notre comsumer key');
define('CONSUMER_SECRET' ,'Note consumer secret');
define("OAUTH_CALLBACK", "http://votresite.fr/callback.php");

$isLoggedOnTwitter = false;

if (!empty($_SESSION['access_token']) && !empty($_SESSION['access_token']['oauth_token']) && !empty($_SESSION['access_token']['oauth_token_secret'])) {

// On récupère les tokens, nous sommes identifiés.
$access_token = $_SESSION['access_token'];

/* On créé la connexion avec Twitter en fournissant les tokens d'accès en paramètres.*/
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* On récupère les informations sur le compte Twitter du visiteur */
$twitterInfos = $connection->get('account/verify_credentials');
$isLoggedOnTwitter = true;
}

elseif(isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] === $_REQUEST['oauth_token']) {

// Les tokens d'accès ne sont pas encore stockés, il faut vérifier l'authentification
/* On créé la connexion avec Twitter en fournissant les tokens d'accès en paramètres.*/
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

/* On vérifie les tokens et récupère le token d'accès */
$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* On stocke en session les tokens d'accès et on supprime ceux qui ne sont plus utiles. */
$_SESSION['access_token'] = $access_token;
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

if (200 == $connection->http_code) {
$twitterInfos = $connection->get('account/verify_credentials');
$isLoggedOnTwitter = true;

}
else {
$isLoggedOnTwitter = false;
}

}
else {
$isLoggedOnTwitter = false;
}

?>

 

A partir de là, nous avons enfin récupéré nos deux tokens d'accès $access_token['oauth_token'] et $access_token['oauth_token_secret']

Comme nous avons besoin de garder ces 2 tokens pour pourvoir publier un tweet, nous devons les enregistrer quelque part (base, fichier, etc) ou directement les passer en paramètre de notre fonction de post !

Il nous reste plus qu'à créer une dernière fonction qui nous permettra de tweeter :

function postToMyTwitter($message){

include "twitteroauth.php";

$token = ( notre variable récupérée dans callback.php : $access_token['oauth_token']);
$token_secret = ( notre variable $access_token['oauth_token_secret']);

$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $token, $token_secret);
$twitterInfos = $connection->get('account/verify_credentials');

if (200 == $connection->http_code) {
$parameters = array('status' => $message);
$status = $connection->post('statuses/update', $parameters);
}
}

 

Et enfin pour pouvoir publier "Bonjour tout le monde" sur notre compte Twitter, il suffira d'appeler la fonction de cette manière

postToMyTwitter('Bonjour tout le monde ');