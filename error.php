<?PHP
    session_start();
    $pageNotSet = false;
    if (!isset($_SESSION["error_last_page"]) || empty($_SESSION["error_last_page"])) {
        $_SESSION["error_last_page"] = "";
        $pageNotSet = true;
    }
?>
<!DOCTYPE html>
<html lang="nl">
    <head>
        <title>Damstede Lyceum</title>
        <script src='import/nprogress.js'></script>
        <link rel='stylesheet' href='import/nprogress.css' />
        <link rel="stylesheet" href="meldingen.css" />
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <link rel="icon" type="image/ico" href="favicon.ico" />
        <?PHP if (!$pageNotSet) { ?>
            <script>
            setTimeout(function() {
                window.location.href = window.location.origin + '<?PHP echo $_SESSION['error_last_page']; ?>';
            }, 15000);
            </script>`
        <?PHP } ?>
    </head>
    <body>
        <div class="msgcontainer">
            <h1 id="not-title">Er ging iets mis!</h1>
            <div id="content">
                <p>Er ging iets mis bij het ophalen van de meldingen. Binnen 15 seconden wordt het opnieuw geprobeerd.<br><br><small>Als deze melding constant in beeld blijft, meld dit dan bij de ICT-afdeling van Damstede.</small><br><br><small><small><i><?PHP if ($pageNotSet) { echo 'Kan niet opnieuw proberen (error_last_page is leeg)'; } else { echo $_SESSION["error_last_page"]; } ?></i></small></small></p>
            </div>
        </div>
    </body>
</html>