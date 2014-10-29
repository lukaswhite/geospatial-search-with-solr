<?php
// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

// Change this as required
$solr_config = array(
	'host' 		=> '127.0.0.1',
  'port' 		=> 8983,
	'path' 		=> '/solr/',
);

// Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/../views',
));

// Create the SOLR client
$app['solr'] = $app->share(function() use ($solr_config){
	return new \Solarium\Client($solr_config);
});

// Display the search form / run the search
$app->get('/', function (Request $request) use ($app) {

	$resultset = null;

	$query = $app['solr']->createSelect();
	$helper = $query->getHelper();

	$query->setRows(100);

	$query->addSort('score', 'asc');
	
	if (($request->get('lat')) && ($request->get('lng'))) {
		
		$latitude = $request->get('lat');
		$longitude = $request->get('lng');
		$distance = $request->get('dist');

		$query->createFilterQuery('distance')->setQuery(
				$helper->geofilt(
					'latlon', 
					doubleval($latitude),
					doubleval($longitude),
					doubleval($distance)
				)
			);

		$query->setQuery('{!func}' . $helper->geodist(
			'latlon', 
			doubleval($latitude), 
			doubleval($longitude)
		));

		$query->addField('_distance_:' . $helper->geodist(
			'latlon', 
			doubleval($latitude), 
			doubleval($longitude)
			)
		);

		$resultset = $app['solr']->select($query);

	}
		
	// Render the form / search results
	return $app['twig']->render('index.twig', array(
		'resultset' => $resultset,
	));

});

$app->run();
