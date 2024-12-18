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

        $product = [
            'identifier' => $_POST['identifier'],
            'code' => $_POST['code'],
            'imageIdentifier' => ($_POST['images'][0] ?? null ? md5($_POST['images'][0]) : null),
            'sourceText' => [
                'title' => $_POST['product_title'],
                'description' => $_POST['product_description'],
                'description2' => $_POST['product_description2'] ?? null,
                'seoTitle' => $_POST['product_seo_title'] ?? null,
                'seoDescription' => $_POST['product_seo_description'] ?? null,
            ],
            'sourceUrl' => $_POST['url'] ?? null,
        ];

        foreach ($_POST['brands'] ?? [] as $k => $brand) {
            if ($brand) {
                $product['brands'][] = [
                    'brand' => $brand,
                    'identifier' => ($_POST['brand_identifier'][$k] ?: md5($brand)),
                    'sourceText' => [
                        'seoTitle' => ($_POST['brand_seo_title'][$k] ?? null),
                        'seoDescription' => ($_POST['brand_seo_description'][$k] ?? null),
                        'seoKeywords' => ($_POST['brand_seo_keywords'][$k] ?? null),
                    ],
                ];
            }
        }


        foreach ($_POST['tags'] ?? [] as $k => $tag) {
            if ($tag) {
                $product['tags'][] = [
                    'identifier' => ($_POST['tag_identifier'][$k] ?: md5($tag)),
                    'sourceText' => [
                        'name' => $tag,
                        'description' => ($_POST['tag_description'][$k] ?? null),
                        'seoTitle' => ($_POST['tag_seo_title'][$k] ?? null),
                        'seoDescription' => ($_POST['tag_seo_description'][$k] ?? null),
                        'seoKeywords' => ($_POST['tag_seo_keywords'][$k] ?? null),
                    ],
                ];
            }
        }


        $i = 0;
        foreach ($_POST['images'] ?? [] as $k => $url) {
            if ($url) {
                $product['images'][] = [
                    'identifier' => md5($url),
                    'src' => $url,
                    'sourceText' => [
                        'alt' => ($_POST['image_alt'][$k] ?? null),
                    ],
                ];
            }
        }

        foreach ($_POST['categories'] ?? [] as $k => $value) {
            if ($value) {
                $product['categories'][] = [
                    'identifier' => $_POST['category_identifier'][$k] ?: md5($value),
                    'sourceText' => [
                        'title' => $value,
                        'seoTitle' => ($_POST['category_seo_title'][$k] ?? null),
                        'seoDescription' => ($_POST['category_seo_description'][$k] ?? null),
                        'seoKeywords' => ($_POST['category_seo_keywords'][$k] ?? null),
                    ],
                ];
            }
        }



        foreach ($_POST['variant'] ?? [] as $value) {
            if ($value['title'] ?? null) {

                $variant = [
                    'identifier' => $value['identifier'],
                    'stock' => (float) $value['stock'],
                    'price' => (float) $value['price'],
                    'ean' => $value['ean'],
                    'imageIdentifier' => md5($value['image']),
                    'sourceText' => [
                        'title' => $value['title'],
                        'description' => ($value['description']),
                    ],
                ];

                foreach ($value['options_variant'] ?? [] as $k => $option) {
                    if ($option['name'] && $option['value']) {
                        $variant['options'][] = [
                            'identifier' => (($option['identifier'] ?? null) ?: md5($option['name'])),
                            'sourceText' => [
                                'name' => $option['name'],
                            ],
                            'values' => [
                                [
                                    'identifier' => (($option['identifier_value'] ?? null) ?: md5($option['value'])),
                                    'sourceText' => [
                                        'name' => $option['value'],
                                    ],
                                ]
                            ]
                        ];
                    }
                }


                $product['variants'][] = $variant;

            }
        }

        $payload = [
            'query' => '
                mutation createOrUpdateProduct($connectionId: Int!, $input: [ProductInput!]) {
                  createOrUpdateProduct(connectionIdImport: $connectionId, input: $input) {
                    products {
                      identifier
                    }
                  }
                }
            ',
            'variables' => [
                'connectionId' => (int) $_POST['connection_id'],
                'input' => [$product],
            ],
        ];

        try {
            $response = $app->graphQL($payload);
        } catch (\Expando\LocoGraphQLPackage\Exceptions\AppException $e) {
            var_dump($e->getMessage());
            exit;
        }

        foreach ($response['data']['createOrUpdateProduct']['products'] as $product) {
            echo 'Product ID: ' . $product['identifier'] . '<br /><br />';
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
            Code<br />
            <input type="text" name="code" value="<?php echo $_POST['code'] ?? 'ABC1234' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Product title<br />
            <input type="text" name="product_title" value="<?php echo $_POST['product_title'] ?? 'Pánská košile' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Product description<br />
            <textarea name="product_description"><?php echo $_POST['product_description'] ?? 'Tato košile má klasický rovný střih a dlouhé rukávy se zapínáním na klasické knoflíky. Volnější rovný střih je vhodný pro muže všech typů postav. Poskytne vám dostatek prostoru pro pohodlné nošení a navíc dokáže šikovně skrýt i případné nedokonalosti postavy. Pokud dáváte v módě přednost klasickému stylu, pak je pro vás tato košile ideální volbou. Díky univerzálnímu střihu a elegantnímu balení může být zároveň jedinečným dárkem, kterým muže opravdu potěšíte.' ?></textarea>
        </label>
    </div>
    <div>
        <label>
            Product description2<br />
            <textarea name="product_description2"></textarea>
        </label>
    </div>
    <div>
        <label>
            Product seo title<br />
            <input type="text" name="product_seo_title" value="<?php echo $_POST['product_seo_title'] ?? 'Pánská košile' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Product seo description<br />
            <input type="text" name="product_seo_description" value="<?php echo $_POST['product_seo_description'] ?? 'Klasická elegantní pánská košile s rovným střihem a dlouhými rukávy.' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Url produktu<br />
            <input type="text" name="url" value="<?php echo $_POST['url'] ?? 'https://www.willsoor.cz/p/13946-panska-klasicka-kosile-s-tmave-modrym-kostkovanym-vzorem/' ?>"  />
        </label>
    </div>
    <br />
    <br />
    <div>
        <label>
            Brand 1<br />
            <input type="text" name="brand_identifier[]" placeholder="identifier" value="<?php echo $_POST['brand_identifier'][0] ?? '' ?>"  />
            <input type="text" name="brands[]" value="<?php echo $_POST['brands'][0] ?? 'Willsoor' ?>"  />
            <input type="text" name="brand_description[]" placeholder="Popis značky" value="<?php echo $_POST['brand_description'][0] ?? 'Popis značky 1' ?>"  />
            <input type="text" name="brand_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['brand_seo_title'][0] ?? 'Výrobek značky Willsoor' ?>"  />
            <input type="text" name="brand_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['brand_seo_description'][0] ?? 'Tato stránka zobrazuje výrobky značky Willsoor' ?>"  />
            <input type="text" name="brand_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['brand_seo_keywords'][0] ?? 'Výrobek, značka, Willsoor' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Brand 2<br />
            <input type="text" name="brand_identifier[]" placeholder="identifier" value="<?php echo $_POST['brand_identifier'][1] ?? '' ?>"  />
            <input type="text" name="brands[]" value="<?php echo $_POST['brands'][1] ?? 'Expando' ?>"  />
            <input type="text" name="brand_description[]" placeholder="Popis značky" value="<?php echo $_POST['brand_description'][1] ?? 'Popis značky 2' ?>"  />
            <input type="text" name="brand_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['brand_seo_title'][1] ?? 'Výrobek značky Expando' ?>"  />
            <input type="text" name="brand_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['brand_seo_description'][1] ?? 'Tato stránka zobrazuje výrobky značky Expando' ?>"  />
            <input type="text" name="brand_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['brand_seo_keywords'][1] ?? 'Výrobek, značka, Expando' ?>"  />
        </label>
    </div>
    <br />
    <br />
    <div>
        <label>
            Tag 1<br />
            <input type="text" name="tag_identifier[]" placeholder="identifier" value="<?php echo $_POST['tag_identifier'][0] ?? '' ?>"  />
            <input type="text" name="tags[]" value="<?php echo $_POST['tags'][0] ?? 'Sleva' ?>"  />
            <input type="text" name="tag_description[]" placeholder="Tag Description" value="<?php echo $_POST['tag_description'][0] ?? 'Popis štítku' ?>"  />
            <input type="text" name="tag_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['tag_seo_title'][0] ?? 'Produkt ve slevě' ?>"  />
            <input type="text" name="tag_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['tag_seo_description'][0] ?? 'Tato stránka zobrazuje produkty ve slevě' ?>"  />
            <input type="text" name="tag_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['tag_seo_keywords'][0] ?? 'Slevy, produkty' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Tag 2<br />
            <input type="text" name="tag_identifier[]" placeholder="identifier" value="<?php echo $_POST['tag_identifier'][1] ?? '' ?>"  />
            <input type="text" name="tags[]" value="<?php echo $_POST['tags'][1] ?? 'Novinka' ?>"  />
            <input type="text" name="tag_description[]" placeholder="Tag Description" value="<?php echo $_POST['tag_description'][1] ?? 'Popis druhého štítku' ?>"  />
            <input type="text" name="tag_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['tag_seo_title'][1] ?? 'Nový produkt' ?>"  />
            <input type="text" name="tag_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['tag_seo_description'][1] ?? 'Tato stránka zobrazuje novinky' ?>"  />
            <input type="text" name="tag_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['tag_seo_keywords'][1] ?? 'Novinky, produkty' ?>"  />
        </label>
    </div>
    <br />
    <br />
    <div>
        <label>
            Category 1<br />
            <input type="text" name="category_identifier[]" placeholder="identifier" value="<?php echo $_POST['category_identifier'][0] ?? '' ?>"  />
            <input type="text" name="categories[]" value="<?php echo $_POST['categories'][0] ?? 'WILLSOOR' ?>"  />
            <input type="text" name="category_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['category_seo_title'][0] ?? 'Kategorie značky Willsoor' ?>"  />
            <input type="text" name="category_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['category_seo_description'][0] ?? 'Tato stránka zobrazuje produkty willsoor.' ?>"  />
            <input type="text" name="category_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['category_seo_keywords'][0] ?? 'Kategorie, produkty, willsoor' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Category 2<br />
            <input type="text" name="category_identifier[]" placeholder="identifier" value="<?php echo $_POST['category_identifier'][1] ?? '' ?>"  />
            <input type="text" name="categories[]" value="<?php echo $_POST['categories'][1] ?? 'PÁNSKÉ KOŠILE' ?>"  />
            <input type="text" name="category_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['category_seo_title'][1] ?? 'Kategorie pánských košil' ?>"  />
            <input type="text" name="category_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['category_seo_description'][1] ?? 'Tato stránka zobrazuje kategorii pánské košile' ?>"  />
            <input type="text" name="category_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['category_seo_keywords'][1] ?? 'Kategorie, produkty, pánské, košile' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Category 3<br />
            <input type="text" name="category_identifier[]" placeholder="identifier" value="<?php echo $_POST['category_identifier'][2] ?? '' ?>"  />
            <input type="text" name="categories[]" value="<?php echo $_POST['categories'][2] ?? 'KLASICKÉ KOŠILE' ?>"  />
            <input type="text" name="category_seo_title[]" placeholder="SEO Title" value="<?php echo $_POST['category_seo_title'][2] ?? 'Kategorie klasických košil' ?>"  />
            <input type="text" name="category_seo_description[]" placeholder="SEO Description" value="<?php echo $_POST['category_seo_description'][2] ?? 'Tato stránka zobrazuje kategorii klasické košile' ?>"  />
            <input type="text" name="category_seo_keywords[]" placeholder="SEO Keywords" value="<?php echo $_POST['category_seo_keywords'][2] ?? 'Kategorie, produkty, klasické, košile' ?>"  />
        </label>
    </div>
    <br />
    <br />
    <div>
        <label>
            Image URL 1<br />
            <input type="text" name="images[]" value="<?php echo $_POST['images'][0] ?? 'https://www.willsoor.cz/images/produkty/thumb/13946img_9263_4_1.jpg' ?>"  />
            <input type="text" name="image_alt[]" value="<?php echo $_POST['image_alt'][0] ?? 'Fotka pánské košile 1' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Image URL 2<br />
            <input type="text" name="images[]" value="<?php echo $_POST['images'][1] ?? 'https://www.willsoor.cz/images/produkty/thumb2/13946img_9265_5_1.jpg' ?>"  />
            <input type="text" name="image_alt[]" value="<?php echo $_POST['image_alt'][1] ?? 'Fotka pánské košile 2' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Image URL 3<br />
            <input type="text" name="images[]" value="<?php echo $_POST['images'][2] ?? '' ?>"  />
            <input type="text" name="image_alt[]" value="<?php echo $_POST['image_alt'][2] ?? 'Fotka pánské košile 3' ?>"  />
        </label>
    </div>
    <br />
    <br />
    <h2>Varianta 1</h2>
    <div>
        <label>
            Identifier<br />
            <input type="text" name="variant[1][identifier]" value="<?php echo $_POST['variant'][1]['identifier'] ?? 'KXXL24' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Title<br />
            <input type="text" name="variant[1][title]" value="<?php echo $_POST['variant'][1]['title'] ?? 'Pánská košile modrá XXL' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Description<br />
            <textarea name="variant[1][description]"><?php echo $_POST['variant'][1]['description'] ?? '' ?></textarea>
        </label>
    </div>
    <div>
        <label>
            Price<br />
            <input type="text" name="variant[1][price]" value="<?php echo $_POST['variant'][1]['price'] ?? '1526' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Stock<br />
            <input type="text" name="variant[1][stock]" value="<?php echo $_POST['variant'][1]['stock'] ?? '24' ?>"  />
        </label>
    </div>
    <div>
        <label>
            EAN<br />
            <input type="text" name="variant[1][ean]" value="<?php echo $_POST['variant'][1]['ean'] ?? '5901223208523' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Image URL<br />
            <input type="text" name="variant[1][image]" value="<?php echo $_POST['variant'][1]['image'] ?? 'https://www.willsoor.cz/images/produkty/thumb/13946img_9263_4_1.jpg' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option 1 (tvoří variantu)<br />
            <input type="text" name="variant[1][options_variant][1][identifier]" placeholder="identifier" value="<?php echo $_POST['variant'][1]['options_variant'][1]['identifier'] ?? '' ?>"  />
            <input type="text" name="variant[1][options_variant][1][name]" value="<?php echo $_POST['variant'][1]['options_variant'][1]['name'] ?? 'Barva' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option value 1 (tvoří variantu)<br />
            <input type="text" name="variant[1][options_variant][1][identifier_value]" placeholder="identifier" value="<?php echo $_POST['variant'][1]['options_variant'][1]['identifier_value'] ?? '' ?>"  />
            <input type="text" name="variant[1][options_variant][1][value]" value="<?php echo $_POST['variant'][1]['options_variant'][1]['value'] ?? 'Modrá' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option 2 (tvoří variantu)<br />
            <input type="text" name="variant[1][options_variant][2][identifier]" placeholder="identifier" value="<?php echo $_POST['variant'][1]['options_variant'][2]['identifier'] ?? '' ?>"  />
            <input type="text" name="variant[1][options_variant][2][name]" value="<?php echo $_POST['variant'][1]['options_variant'][2]['name'] ?? 'Velikost' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option value 2 (tvoří variantu)<br />
            <input type="text" name="variant[1][options_variant][2][identifier_value]" placeholder="identifier" value="<?php echo $_POST['variant'][1]['options_variant'][2]['identifier_value'] ?? '' ?>"  />
            <input type="text" name="variant[1][options_variant][2][value]" value="<?php echo $_POST['variant'][1]['options_variant'][2]['value'] ?? 'XXL' ?>"  />
        </label>
    </div>
    <br />
    <br />
    <h2>Varianta 2</h2>
    <div>
        <label>
            Identifier<br />
            <input type="text" name="variant[2][identifier]" value="<?php echo $_POST['variant'][2]['identifier'] ?? 'KXS24' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Title<br />
            <input type="text" name="variant[2][title]" value="<?php echo $_POST['variant'][2]['title'] ?? 'Pánská košile modrá XS' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Description<br />
            <textarea name="variant[2][description]"><?php echo $_POST['variant'][2]['description'] ?? '' ?></textarea>
        </label>
    </div>
    <div>
        <label>
            Price<br />
            <input type="text" name="variant[2][price]" value="<?php echo $_POST['variant'][2]['price'] ?? '1526' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Stock<br />
            <input type="text" name="variant[2][stock]" value="<?php echo $_POST['variant'][2]['stock'] ?? '14' ?>"  />
        </label>
    </div>
    <div>
        <label>
            EAN<br />
            <input type="text" name="variant[2][ean]" value="<?php echo $_POST['variant'][2]['ean'] ?? '5901223208523' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Image URL<br />
            <input type="text" name="variant[2][image]" value="<?php echo $_POST['variant'][2]['image'] ?? 'https://www.willsoor.cz/images/produkty/thumb/13946img_9263_4_1.jpg' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option 1 (tvoří variantu)<br />
            <input type="text" name="variant[2][options_variant][1][name]" value="<?php echo $_POST['variant'][2]['options_variant'][1]['name'] ?? 'Barva' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option value 1 (tvoří variantu)<br />
            <input type="text" name="variant[2][options_variant][1][value]" value="<?php echo $_POST['variant'][2]['options_variant'][1]['value'] ?? 'Modrá' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option 2 (tvoří variantu)<br />
            <input type="text" name="variant[2][options_variant][2][name]" value="<?php echo $_POST['variant'][2]['options_variant'][2]['name'] ?? 'Velikost' ?>"  />
        </label>
    </div>
    <div>
        <label>
            Option value 2 (tvoří variantu)<br />
            <input type="text" name="variant[2][options_variant][2][value]" value="<?php echo $_POST['variant'][2]['options_variant'][2]['value'] ?? 'XS' ?>"  />
        </label>
    </div>

    <div>
        <input type="submit" name="send" value="send" />
    </div>
</form>
