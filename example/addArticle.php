<?php

    require_once 'boot.php';

    $app = new \Expando\LocoGraphQLPackage\App();
    $app->setToken($_SESSION['app_token'] ?? null);
    $app->setUrl($_SESSION['client_data']['app_url']);
    if ($app->isTokenExpired()) {
        $app->refreshToken($_SESSION['client_data']['client_id'], $_SESSION['client_data']['client_secret']);
        if ($app->isLogged()) {
            $_SESSION['app_token'] = $app->getToken();
        }
    }

    if (!$app->isLogged()) {
        die('Translator is not logged');
    }

    if ($_POST['send'] ?? null) {

        if (!$_POST['connection_id']) {
            exit('Connection ID musí být vyplněno');
        }

        $article = [
            'identifier' => $_POST['identifier'],
            'sourceText' => [
                'title' => $_POST['title'],
                'perex' => $_POST['perex'],
                'text' => $_POST['text'],
            ],
            //'sourceUrl' => $_POST['url'] ?? null,
        ];

        $payload = [
            'query' => '
                mutation createOrUpdateArticle($connectionId: Int!, $input: [ArticleInput!]) {
                  createOrUpdateArticle(connectionIdImport: $connectionId, input: $input) {
                    articles {
                      identifier
                    }
                  }
                }
            ',
            'variables' => [
                'connectionId' => (int) $_POST['connection_id'],
                'input' => [$article],
            ],
        ];

        try {
            $response = $app->graphQL($payload);
        } catch (\Expando\LocoGraphQLPackage\Exceptions\AppException $e) {
            echo '<pre>';
            print_r($e);
            exit;
        }

        foreach ($response['data']['createOrUpdateArticle']['articles'] as $article) {
            echo 'Article ID: ' . $article['identifier'] . '<br /><br />';
        }
    }
?>

<form method="post">
    <div>
        <label>
            Connection ID<br />
            <input type="text" name="connection_id" value="<?php echo $_POST['connection_id'] ?? '' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Identifier<br />
            <input type="text" name="identifier" value="<?php echo $_POST['identifier'] ?? '13946' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Title<br />
            <input type="text" name="title" value="<?php echo $_POST['title'] ?? 'Proč je Loco nejlepší překladová aplikace ?' ?>"  />
        </label>
    </div>

    <div>
        <label>
            Perex<br />
            <input type="text" name="perex" value="<?php echo $_POST['perex'] ?? 'Více se dozvíte v článku' ?>"  />
        </label>
    </div>

    <div>
        <label>
            Description<br />
            <textarea name="text"><?php echo $_POST['text'] ?? 'Prostě to tak je !!' ?></textarea>
        </label>
    </div>

    <div>
        <input type="submit" name="send" value="send" />
    </div>
</form>
