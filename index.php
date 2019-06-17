<?php session_start(); ?>
<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="UTF-8">
	<title>Kniha</title>
</head>
<body>
<?php
require_once 'functions.php';

/** @var mysqli $con - Nastavení databázového připojení */
$con = new mysqli("127.0.0.1", "login", "pass", "guestbook_demo");
if ($con->connect_error) {
	die("Connection failed: " . $con->connect_error);
}

$isLogged = false;

/** Pokud je uživatel přihlášen */
if ($_SESSION['login'] ?? null) {
	$isLogged = true;
	/** Pokud je odeslán formulář se zprávou tlačítkem bez escapování */
	if (isset($_POST['submit'])) {
		$message = $_POST['message'];
		if ($message !== null && $message !== '') {
			$user = $_SESSION['user'] ?? null;
			$sql_statement = "INSERT INTO `guestbook` (`user`, `message`) VALUES ('$user', '$message')";
			$con->query($sql_statement);
		}
	}

	/** Pokud je odeslán formulář tlačítkem s escapováním */
	if (isset($_POST['submitEscaped'])) {
		$message = htmlspecialchars(xss_clean($_POST['message']));
		if ($message !== null && $message !== '') {
			$user = htmlspecialchars(xss_clean($_SESSION['user'] ?? null));
			$sql_statement = "INSERT INTO `guestbook` (`user`, `message`) VALUES ('$user', '$message')";
			$con->query($sql_statement);
		}
	}

	/** Výpis zpráv v tabulce */
	$query = $con->query('SELECT * FROM `guestbook` ORDER BY id DESC ');
	/** Vypisovat zprávy escapovaně? */
	echo '<table border="1">';
	echo '<tr><td>ID</td><td>Uživatel</td><td>Zpráva</td></tr>';
	if (($_GET['nonescape'] ?? false) === 'true') {
		/** Neescapovaný výpis */
		while ($result = mysqli_fetch_array($query)) {
			echo '<tr>';
			echo '<td>' . ($result['id'] ?? '-') . '</td><td>' . ($result['user'] ?? '-') . '</td><td>' . ($result['message'] ?? '-') . '</td>';
			echo '</tr>';
		}
	} else {
		/** Escapovaný výpis */
		while ($result = mysqli_fetch_array($query)) {
			echo '<tr>';
			echo '<td>' . (htmlspecialchars($result['id'] ?? '-')) . '</td><td>' . (htmlspecialchars($result['user'] ?? '-')) . '</td><td>' . (htmlspecialchars($result['message'] ?? '-')) . '</td>';
			echo '</tr>';
		}
	}
	echo '</table>';

	$con->close();
} else {
	/** Pokud uživatel není přihlášen, zpracujeme přihlášení */
	/** @var array $availableLogins - Dostupné přihlašování ve formátu 'login' => 'heslo' */
	$availableLogins = [
		'Franta' => 'a',
		'Lojza' => 'b',
		'Jirka' => 'c'
	];

	/** Po odeslání přihlašovacího formuláře se ověří, zda-li je přihlášení platné */
	if (isset($_POST['submitLogin'])) {
		if (array_key_exists(htmlspecialchars(xss_clean($_POST['login'])), $availableLogins)) {
			$loginEntity = ($availableLogins[htmlspecialchars(xss_clean($_POST['login']))] ?? null);
			if ($loginEntity === htmlspecialchars(xss_clean($_POST['password']))) {
				$_SESSION['login'] = true;
				$_SESSION['user'] = htmlspecialchars(xss_clean($_POST['login']));
			}
		}
		header('Location: .');
	}
}

/** Odhlášení */
if ($isLogged && ($_GET['logout'] ?? false) === 'true') {
	session_destroy();
	header('Location: index.php');
}

?>


<?php
/** Pokud je uživatel přihlášený, má možnost odeslat novou zprávu.
 *  Jinak se mu vypíše přihlašovací formulář
 */
?>
<?php if ($isLogged) { ?>
	Vítejte <?php echo xss_clean($_SESSION['user'] ?? null) ?><a href="index.php?logout=true">Odhlásit</a>
	<div>
		<a href="index.php?nonescape=true">Výpis Bez escapování</a>
		<a href="index.php?nonescape=false">S escapováním</a>
	</div>
	<form method="post">
		<div><textarea name="message" placeholder="Zpráva"></textarea></div>
		<div><input type="submit" name="submit" value="Odeslat neescapovaně"></div>
		<div><input type="submit" name="submitEscaped" value="Odeslat zaescapované"></div>
	</form>
<?php } else { ?>
	<div>
		Dostupné přihlášení:
		<div>'Franta' => 'a'</div>
		<div>'Lojza' => 'b'</div>
		<div>'Jirka' => 'c'</div>
	</div>
	<form method="post">
		<div><input name="login" placeholder="Login"></div>
		<div><input name="password" placeholder="Heslo"></div>
		<div><input type="submit" name="submitLogin" value="Přihlásit"></div>
	</form>
<?php } ?>


</body>
</html>
