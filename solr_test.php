<?php

$url[] = "collections/";
$data[] = '{
  "name": "techproducts",
  "numShards": 1,
  "replicationFactor": 1
}';


$url[] = "collections/techproducts/schema";
$data[] = '{
  "add-field": [
    {"name": "name", "type": "text_general", "multiValued": false},
    {"name": "cat", "type": "string", "multiValued": true},
    {"name": "manu", "type": "string"},
    {"name": "features", "type": "text_general", "multiValued": true},
    {"name": "weight", "type": "pfloat"},
    {"name": "price", "type": "pfloat"},
    {"name": "popularity", "type": "pint"},
    {"name": "inStock", "type": "boolean", "stored": true},
    {"name": "store", "type": "location"}
  ]
}';


$url[] = "collections/techproducts/update";
$data[] = '  [
  {
    "id" : "978-0641723445",
    "cat" : ["book","hardcover"],
    "name" : "The Lightning Thief",
    "author" : "Rick Riordan",
    "series_t" : "Percy Jackson and the Olympians",
    "sequence_i" : 1,
    "genre_s" : "fantasy",
    "inStock" : true,
    "price" : 12.50,
    "pages_i" : 384
  }
,
  {
    "id" : "978-1423103349",
    "cat" : ["book","paperback"],
    "name" : "The Sea of Monsters",
    "author" : "Rick Riordan",
    "series_t" : "Percy Jackson and the Olympians",
    "sequence_i" : 2,
    "genre_s" : "fantasy",
    "inStock" : true,
    "price" : 6.49,
    "pages_i" : 304
  }
]';

/*
 curl -H "Content-Type: application/json" \
       -X POST \
       -d @example/products.json \
       --url 'http://localhost:8983/api/collections/techproducts/update?commit=true'
	   */

	   
$data[] =  '{"set-property":{"updateHandler.autoCommit.maxTime":15000}}';
$url[] = 'collections/techproducts/config';
	   
	   
$c = 2;
	   
$host = "localhost";
$command = 
"curl --request POST \
--url http://".$host.":8983/api/".$url[$c]." \
--header 'Content-Type: application/json' \
--data '".$data[$c]."'
";

echo $command;
$command = "curl 'http://solr:8983/solr/techproducts/select?q=name%3Alightning'";

print exec($command);



?>