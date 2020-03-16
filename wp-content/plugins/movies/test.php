<?php
function imdb_api( $id ) {
	$movie = file_get_contents( "http://www.omdbapi.com/?i=$id&apikey=5a10c86c" );
	return $movie;
}


$movie = imdb_api( 'tt7660850' );

var_dump($movie->Title);