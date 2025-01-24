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
                'text1' => $_POST['text1'],
                'text2' => $_POST['text2'],
            ],
            //'sourceUrl' => $_POST['url'] ?? null,
        ];

        $payload = [
            'query' => '
                mutation createOrUpdateCustomContent($connectionId: Int!, $input: [CustomContentInput!]) {
                  createOrUpdateCustomContent(connectionIdImport: $connectionId, input: $input) {
                    contents {
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

        foreach ($response['data']['createOrUpdateCustomContent']['contents'] as $article) {
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
            Text1<br />
            <textarea name="text1"><?php echo $_POST['text1'] ?? 'Proč je Loco nejlepší překladová aplikace ?' ?></textarea>
        </label>
    </div>
    <div>
        <label>
            Text2<br />
            <textarea name="text2"><?php echo $_POST['text2'] ?? 'Proč je Loco nejlepší překladová aplikace 2?' ?></textarea>
        </label>
    </div>

    <div>
        <input type="submit" name="send" value="send" />
    </div>
</form>
