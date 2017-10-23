<?php
class ImdbList extends dbhandler{

	private $rootList=[];
	
	function __construct() {
	}
   
	public function getMoviesList(){
		//print ("Getting movies list");
		//$selectquery ="SELECT * FROM movies_list WHERE 1 AND enabled=1 AND moviename LIKE '%Lord of the%' LIMIT 100";
		//$selectquery ="SELECT * FROM movies_list WHERE 1 AND enabled=1 AND imdb='tt0167260' LIMIT 100";
		//$selectquery ="SELECT * FROM movies_list WHERE 1 AND enabled=1 AND year=2016 LIMIT 1";
		//$selectquery ="SELECT * FROM movies_list WHERE 1 AND enabled=1 AND `year`=2015 AND moviename LIKE 'The%' LIMIT 100, 500;";
		//select count(*) from movies_list where yearmovie=2015 OR yearmovie=2014;
		//$selectquery ="SELECT * FROM movies_list WHERE 1 AND enabled=1 AND moviename='A Gringo Walks Into a Bar'";
		//$selectquery ="SELECT * FROM imdb.movies_list WHERE 1 AND enabled=1 AND `yearmovie`=2014 OR yearmovie=2015";
		$selectquery ="SELECT * FROM imdb.movies_list WHERE 1 AND enabled=1 AND moviename LIKE '%Lord of the Rings%'";
		$selectquery ="SELECT * FROM imdb.movies_list WHERE 1 AND enabled=1 AND moviename LIKE '%The matrix%' LIMIT 1";
		$dbh = $this->getInstance(); 
		if ( !$stmt = $dbh->dbh->prepare($selectquery) ) { 
			var_dump ( $dbh->dbh->errorInfo() );
		} 
		if ( $stmt->execute() ) { 
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $r ) { array_push( $this->rootList, $r); }
			//print ("\nFound: ".count($rows)." movies.\n");
			return $this->rootList;
		}
	}
	
	public function getTvshowsList(){

		$selectquery ="SELECT * FROM imdb.tvshows_list WHERE 1 AND enabled=1 LIMIT 1,1";
		//$selectquery ="SELECT * FROM imdb.tvshows_list WHERE 1 AND enabled=1 AND tvshowname='American Crime Story' LIMIT 1";

		$dbh = $this->getInstance(); 
		if ( !$stmt = $dbh->dbh->prepare($selectquery) ) { 
			var_dump ( $dbh->dbh->errorInfo() );
		} 
		if ( $stmt->execute() ) { 
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $r ) { array_push( $this->rootList, $r); }
			//print ("\nFound: ".count($rows)." movies.\n");
			return $this->rootList;
		}	
	}
	
}
?>
