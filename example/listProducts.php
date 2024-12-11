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
                          products(connectionIdExport: $connectionId, first: 2, after: $after) {
                            edges {
                              node {
                                productId
                                status
                                identifier
                                code
                                url
                                imageId
                                translations {
                                  language
                                  title
                                }
                                images {
                                  imageId
                                  url
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


            foreach ($response['data']['products']['edges'] as $edge) {
                $product = $edge['node'];

                echo '<pre>';
                    print_r($product);
                echo '</pre>';
            }

            if (!($response['data']['products']['pageInfo']['hasNextPage'] ?? false)) {
                break;
            }
            $after = $response['data']['products']['pageInfo']['endCursor'];
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
