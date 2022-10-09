<?php

/*
	Satire-vs-Reality API
	by @aaviator42
	2022-09-29
	
	Spits out a JSON array that contains:
	 - Satire The Onion headlines
	 - Real 'Not The Onion' headlines
	
	Satire articles are sourced from The Onion's RSS feed
	Real articles are sourced from Reddit: r/nottheonion/
*/


header('Content-Type: application/json');

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
$satireSource = "https://www.theonion.com/rss";

//JSON of NOT satire articles from Reddit: r/nottheonion/
//we use /top/?t=day to get top posts from the past 24 hours
//we use /top/?t=week to get top posts from the past week
$realitySource = "https://www.reddit.com/r/nottheonion/top/.json?sort=top&t=week";


//parse satire feed into array
$parsedSatire = xml2array($satireSource);
$parsedSatire = $parsedSatire['rss']['channel']['item']; //articles

//final list of satire articles
$satireArticles = array();

//generate list of satire articles
//we only want articles that belong to the political categories,
//because that's the kind of content that tends to be posted on r/nottheonion/
//and we want it to be hard to distinguish between reality and satire articles
$acceptedCategories = ['politicians', 'politics', 'politicalphilosophy', 'politicalideologies'];
foreach($parsedSatire as $item){
	if(isset($item['category'][0]) && is_array($item['category'])){
		if(count(array_intersect($acceptedCategories, $item['category'])) > 0){
			//item is political!
			//extract title and link, and push to list
			$itemFinal['title'] = $item['title'];
			$itemFinal['link'] = $item['link'];
			
			array_push($satireArticles, $itemFinal);
		}
	}
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



//----------------
//xml2array function from https://www.php.net/manual/en/function.xml-parse.php#87920

function xml2array($url, $get_attributes = 1, $priority = 'tag')
{
    $contents = "";
    if (!function_exists('xml_parser_create'))
    {
        return array ();
    }
    $parser = xml_parser_create('');
    if (!($fp = @ fopen($url, 'rb')))
    {
        return array ();
    }
    while (!feof($fp))
    {
        $contents .= fread($fp, 8192);
    }
    fclose($fp);
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
        return; //Hmm...
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array ();
    foreach ($xml_values as $data)
    {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value))
        {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value;
        }
        if (isset ($attributes) and $get_attributes)
        {
            foreach ($attributes as $attr => $val)
            {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        if ($type == "open")
        {
            $parent[$level -1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current))))
            {
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else
            {
                if (isset ($current[$tag][0]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset ($current[$tag . '_attr']))
                    {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete")
        {
            if (!isset ($current[$tag]))
            {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            }
            else
            {
                if (isset ($current[$tag][0]) and is_array($current[$tag]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data)
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes)
                    {
                        if (isset ($current[$tag . '_attr']))
                        {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close')
        {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
}
