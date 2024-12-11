<?php

use Expando\LocoGraphQLPackage\Exceptions\LoginException;

require_once 'boot.php';

if (($_POST['send'] ?? null) || ($_GET['code'] ?? null)) {

    if (($_POST['send'] ?? null)) {
        $_SESSION['client_data'] = $_POST;
    }

    try {
        $login = new \Expando\LocoGraphQLPackage\Login(
            $_SESSION['client_data']['client_id'],
            $_SESSION['client_data']['client_secret'],
            $_SESSION['client_data']['redirect_url'],
            $_SESSION['client_data']['app_url']
        );
        $login->addScope('products');
        $login->addScope('connections');
        $login->addScope('translations');
        $token = $login->getToken();
    } catch (LoginException $e) {
        die($e->getMessage());
    }

    if ($token !== null)
    {
        // save to session for example
        $_SESSION['app_token'] = $token;
        header('Location: index.php');
    }
}

?>

<form method="post">
    <div>
        <label>
            App URL<br />
            <input type="text" name="app_url" value="https://loco-app.expan.do"  />
        </label>
    </div>
    <div>
        <label>
            Client ID<br />
            <input type="text" name="client_id" value=""  />
        </label>
    </div>
    <div>
        <label>
            Client secret<br />
            <input type="text" name="client_secret" value=""  />
        </label>
    </div>
    <div>
        <label>
            Redirect URL<br />
            <input type="text" name="redirect_url" value="http://loco-graphql-package.local/redirect.php"  />
        </label>
    </div>
    <div>
        <input type="submit" name="send" value="send" />
    </div>
</form>
