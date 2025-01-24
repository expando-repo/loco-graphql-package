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

        echo 'Products: <br />';
        $after = null;
        while(true) {

            try {
                $response = $app->graphQL([
                    'query' => '
                        query MyQuery ($connectionId: Int!, $after: String) {
                          articles(connectionIdExport: $connectionId, first: 2, after: $after) {
                            edges {
                              node {
                                articleId
                                status
                                identifier                                
                                translation {
                                  perex
                                  language
                                  title
                                  text
                                }
                              }
                            }
                            pageInfo {
                              hasNextPage
                              endCursor
                            }
                          }
                        }
                    ',
                    'variables' => [
                        'connectionId' => (int) $_POST['connection_id'],
                        'after' => $after,
                    ],
                ]);
            }
            catch (\Expando\LocoGraphQLPackage\Exceptions\AppException $e) {
                die($e->getMessage());
            }


            foreach ($response['data']['articles']['edges'] as $edge) {
                $product = $edge['node'];

                echo '<pre>';
                    print_r($product);
                echo '</pre>';
            }

            if (!($response['data']['articles']['pageInfo']['hasNextPage'] ?? false)) {
                break;
            }
            $after = $response['data']['articles']['pageInfo']['endCursor'];
        }

    }
?>

<form method="post">
    <div>
        <label>
            Connection ID<br />
            <input type="text" name="connection_id" value="<?php echo $_POST['connection_id'] ?? null ?>"  />
        </label>
    </div>
    <div>
        <input type="submit" name="send" value="send" />
    </div>
</form>
