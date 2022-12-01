<?php

/*
	Satire-vs-Reality API
	by @aaviator42
	2022-09-29
	
	Spits out a JSON array that contains:
	 - Satire The Onion headlines
	 - Real 'Not The Onion' headlines
	
	Satire articles are sourced from The Onion's website
	Real articles are sourced from Reddit: r/nottheonion/
*/


//Include parser for The Onion's HTML
require 'simple_html_dom.php';

header('Content-Type: application/json');

//cache system
if(file_exists('data_cache.json')){
	if(time()-filemtime('data_cache.json') < 1 * 3600) {
		//cache file younger than one hour
		//echo cached data and die
		$cachedData = file_get_contents('data_cache.json');
		echo $cachedData;
		die;
	}
}

//RSS feed of satire articles from The Onion
$satireSource = "https://www.theonion.com/politics/news-in-brief";

//JSON of NOT satire articles from Reddit: r/nottheonion/
//we use /top/?t=day to get top posts from the past 24 hours
//we use /top/?t=week to get top posts from the past week
$realitySource = "https://www.reddit.com/r/nottheonion/top/.json?sort=top&t=week";


//load satire site into array
//function from simple_html_dom.php
$satireRaw = file_get_html($satireSource);

//final list of satire articles
$satireArticles = array();

//generate list of satire articles
//we only want articles that belong to the political categories,
//because that's the kind of content that tends to be posted on r/nottheonion/
//and we want it to be hard to distinguish between reality and satire articles
foreach($satireRaw->find('H2') as $element){
	$itemFinal['title'] = $element->innertext;
	$itemFinal['link'] = $element->parent->href;
	array_push($satireArticles, $itemFinal);
}

//satire list size
//useful because we want an equal number of reality articles and satire articles
$satireListSize = count($satireArticles);


//final list of reality articles 
$realityArticles = array();

//fetch Reddit JSON
$rawReality = file_get_contents($realitySource);

//parse JSON into array 
$parsedReality = json_decode($rawReality, true);
$parsedReality = $parsedReality['data']['children']; //articles

//generate list of reality articles
for($i = 0; $i < $satireListSize; $i++){
	if(isset($parsedReality[$i])){
		//extract title, article link and reddit thread link and push to list
		$itemFinal['title'] = $parsedReality[$i]['data']['title'];
		$itemFinal['link'] = $parsedReality[$i]['data']['url'];
		$itemFinal['discussion'] = 'https://reddit.com' . $parsedReality[$i]['data']['permalink'];
		
		array_push($realityArticles, $itemFinal);
	}
}

//combine reality and satire lists
$combinedList = [	
					'updated' => date("d-m-Y H:i:s") . ' UTC', //UTC time
					'whodabest' => 'aaviator42',
					'satire' => $satireArticles, 
					'reality' => $realityArticles,
				];

//JSON to be printed
$finalJSON = json_encode($combinedList, JSON_PRETTY_PRINT);

//create cache file
file_put_contents('data_cache.json', $finalJSON, LOCK_EX);

echo $finalJSON;
die;

