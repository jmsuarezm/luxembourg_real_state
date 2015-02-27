<?
// This is a template for a PHP scraper on Morph (https://morph.io)
// including some code snippets below that you should find helpful

// require 'scraperwiki.php';
// require 'scraperwiki/simple_html_dom.php';
//
// // Read in a page
// $html = scraperwiki::scrape("http://foo.com");
//
// // Find something on the page using css selectors
// $dom = new simple_html_dom();
// $dom->load($html);
// print_r($dom->find("table.list"));
//
// // Write out to the sqlite database using scraperwiki library
// scraperwiki::save_sqlite(array('name'), array('name' => 'susan', 'occupation' => 'software developer'));
//
// // An arbitrary query against the database
// scraperwiki::select("* from data where 'name'='peter'")

// You don't have to do things with the ScraperWiki library. You can use whatever is installed
// on Morph for PHP (See https://github.com/openaustralia/morph-docker-php) and all that matters
// is that your final data is written to an Sqlite database called data.sqlite in the current working directory which
// has at least a table called data.
?>

<?php
require 'scraperwiki.php';
require 'scraperwiki/simple_html_dom.php';
ini_set('max_execution_time', 600);
setlocale(LC_ALL, 'fr_FR.UTF8');

//the firt time, we set manually the first page
$value = "/recherche/resultats/tr/by/ig/h,f";
//if $value is a valid page
while ($value != "") {
    $htmlCentre = scraperWiki::scrape("http://www.athome.lu" . $value);
    $domCentre = new simple_html_dom();
    $domCentre->load($htmlCentre);
    //find the announces in the current page and store the records
    findAnnounces ($domCentre);
    //look for the next page link
    $value = "";
    foreach($domCentre->find('.next') as $data){
        $value = $data->href;
    }
}

/***************** Functions *********************/
function findAnnounces($strDataDom){
    //for each page, there are up to 10 records, each of them marked with DOM class 'plus'
    foreach($strDataDom->find('.plus') as $data){
        $value = $data->href;
        //is it a valid URL for an announce?
        if (strrpos ($value , "www.athome.lu/")){
            //go into the announce
            $htmlContent = scraperWiki::scrape($value);
            //look for the start of the json record wich has all the information of the announce
            //and manually trim it to the correct json format
            $strStart = strpos($htmlContent, "initGoogleMap");
            $strEnd = strpos($htmlContent, "#containerGoogleMap");
    
            $strData = substr ($htmlContent, $strStart, $strEnd - $strStart - 6);     
            $strData = ltrim($strData, "initGoogleMap([");
            //is it UTF format? just in case we convert it   
            $strDataUTF = iconv('UTF-8', 'ASCII//TRANSLIT', $strData);
            //the function will transform the string into a json object and store it in the database
            storeJson($strDataUTF);
        }
    }
}

/*************************************************/
function storeJson($strData){
    $record=array(); 
    //decode the string
    $jsonVar = json_decode($strData);
    //if the decode ended with no error
    if (json_last_error() === JSON_ERROR_NONE) { 
        $record["id"] = $jsonVar -> id;
        $record["submitter"] = $jsonVar -> submitter;
        $record["inserted"] = $jsonVar -> inserted;
        $record["immotype"] = $jsonVar -> immotype;
        $record["price"] = $jsonVar -> price;
	$record["rent"] = $jsonVar -> rent;
        $record["location"] = $jsonVar -> location;
        $record["surface"] = $jsonVar -> surface;
        $record["city"] = $jsonVar -> city;
        $record["price_by_m2"] = $jsonVar -> price_by_m2;
        
        //save the record
        if ($record["price"] <> 0 or $record["rent"] <> 0 or $record["price_by_m2"] <> 0) {
           scraperwiki::save_sqlite(array('id'), $record);
        }
    }    
} 

?>
