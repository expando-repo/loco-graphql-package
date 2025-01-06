<?php
    require_once 'boot.php';

    if (empty($_SESSION['client_data'])) {
        header('Location: redirect.php');
        exit;
    }

    $app = new \Expando\LocoGraphQLPackage\App();
    $app->setToken($_SESSION['app_token'] ?? null);
    $app->setUrl($_SESSION['client_data']['app_url']);
    if ($app->isTokenExpired()) {
        $app->refreshToken($_SESSION['client_data']['client_id'], $_SESSION['client_data']['client_secret']);
        if ($app->isLogged()) {
            $_SESSION['app_token'] = $app->getToken();
        }
    }
?>

<?php if (!$app->isLogged()) { ?>
    <a href="redirect.php">Login (get token)</a>
<?php } else { ?>
    <ul>
        <li><a href="addProduct.php">add/update product</a></li>
        <li><a href="listProducts.php">list products</a></li>
        <li></li>
        <li><a href="addArticle.php">add/update article</a></li>
        <li></li>
        <li><a href="logout.php">logout (delete token)</a></li>
    </ul>
<?php } ?>
