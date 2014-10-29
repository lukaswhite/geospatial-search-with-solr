<?php
// Include the autoloader - don't forget to run composer install first
require '../vendor/autoload.php';

$solr_config = array(
	'host' 		=> '127.0.0.1',
  'port' 		=> 8983,
	'path' 		=> '/solr/',
);

// Create a Solarium client
$client = new \Solarium\Client($solr_config);

// Ping the server, make sure it's running
// create a ping query
$ping = $client->createPing();

// execute the ping query
try {
	$result = $client->ping($ping);
} catch (Solarium\Exception\HttpException $e) {
	print "Could not connect to SOLR. Check that it is running, and your configuration is correct.\n";
	die();
}

// open up the CSV
$csv_filepath = __DIR__ . '/../data/airports.dat';

$num_imported = 0;

$fp = fopen($csv_filepath, 'r');

// Now let's start importing
while (($row = fgetcsv($fp, 1000, ",")) !== FALSE) {

	// get an update query instance
	$update = $client->createUpdate();

	// Create a document
	$doc = $update->createDocument();    

	$doc->id = $row[0];
	$doc->name = $row[1];
	$doc->city = $row[2];
	$doc->country = $row[3];
	$doc->faa_faa_code = $row[4];
	$doc->icao_code = $row[5];
	$doc->altitude = $row[8];

	$doc->latlon = doubleval($row[6]) . "," . doubleval($row[7]);

	// Let's simply add and commit straight away.
	$update->addDocument($doc);
	$update->addCommit();

	// this executes the query and returns the result
	$result = $client->update($update);

	$num_imported++;

	// Sleep for a couple of seconds, lest we go too fast for SOLR
	sleep(2);

}

fclose($fp);

printf("Imported %d records\n", $num_imported);