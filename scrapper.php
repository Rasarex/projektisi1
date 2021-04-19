<?php
include 'simple_html_dom.php';
ini_set("allow_url_fopen", true);
$output_path = "data.csv";
$jpg_path = "";

class Descriptor
{
    var $title;
    var $authors;
    var $desc;
    var $link;
    var $price;
}


$html = "https://merlin.pl/catalog/ksiazki-m10349074/";
$mainhtml = "https://merlin.pl/";
$page = file_get_html($html);
$categories = [];
foreach ($page->find(".filter-item,.b-filters__item-label-level-1") as $elem) {
    if ($elem->getAttribute("data-value") === false)
        continue;
    $categories[]  = $elem->getAttribute("data-value");
}

function get_book_links($link)
{
    $page = file_get_html($link);
    $links = [];
    foreach ($page->find(".b-products-list__title") as $el) {
        $maybelink = $el->getAttribute("href");
        if ($maybelink === false) {
            continue;
        }
        $links[] = substr($maybelink, 1);
    }
    return $links;
}

function get_book_descriptor($book_link)
{

    $html = $GLOBALS['mainhtml'];
    $descriptor = new Descriptor();
    $pagehtml = $html . $book_link;
    // var_dump($pagehtml);
    $page = file_get_html($pagehtml);
    $product_name_wrapper = $page->find(".product_name_wrapper")[0];
    $descriptor->title = trim($product_name_wrapper->children(0)->plaintext);
    $authors = $product_name_wrapper->find(".product-brand");
    foreach ($authors as $author) {
        $descriptor->authors[] = trim($author->plaintext);
    }
    $descriptor->desc = $page->find("#con_tab-1")[0]->children(0)->plaintext;
    $descriptor->link  = substr($page->find("#mainImage")[0]->src, 2);
    $descriptor->price = $page->find("#product-price")[0]->plaintext;
    return $descriptor;
    //
}
function combine_categories($categories)
{
    $combined_categories = "?categories_ids=";
    foreach ($categories as $categorie) {
        $combined_categories = $combined_categories . strval($categorie) . "-";
    }
    $combined_categories = rtrim($combined_categories, "-");
    return $combined_categories;
}
function main()
{
    $categories = combine_categories($GLOBALS["categories"]);
    $book_links = get_book_links($GLOBALS["html"] . $categories);
    // $descriptors = [];
    $fileh = fopen($GLOBALS["output_path"], "w");
    fwrite($fileh, "title,authors,desc, link,price\n");

    //TODO  Page flow
    //TODO  Start , end position
    $i = 0;
    foreach ($book_links as $link) {
        if ($i == 1000) {
            break;
        }
        $descriptor = get_book_descriptor($link);
        $old = $descriptor->link;
        $descriptor->link = "media" . substr($old, strrpos($old, "/"));
        $old = "https://" . $old;
        $descriptor->authors = "[" . implode(",", $descriptor->authors) . "]";
        copy($old,  __DIR__ . "/" . $descriptor->link);
        fputcsv($fileh, (array)$descriptor, ",");

        sleep(1);
        $i++;
    }
}
main();
