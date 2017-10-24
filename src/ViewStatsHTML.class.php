<?php 
class ViewStatsHTML{

	private $dbh;
	private $MODE="SUMMARY";

	public function __construct(){
		$this->dbh=dbhandler::getInstance();
	}

	public function viewStats(){

		$this->showSummary();
	}

	public function viewResults($imdb){

		$this->showResults($imdb);
		
	}

	private function showSummary(){
		$selectquery ="select * from 1337x.search_summary JOIN imdb.movies_list 
		ON search_summary.imdb=imdb.movies_list.imdb 
		/*AND imdb.movies_list.yearmovie=2014 */ 
		ORDER BY totalTorrents DESC
		";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { 
			//var_dump ( $dbh->dbh->errorInfo() );
		} 
		if ( $stmt->execute() ) { 

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if (count($rows)>0):				
				$this->printSummaryTable($rows);
			else:
				print ("No results. Check your query.");
			endif;
		}	
	}

	private function showResults($imdb){
	
		$this->MODE="RESULTS";

		$selectquery ="select * from 1337x.search_summary JOIN imdb.movies_list ON search_summary.imdb=imdb.movies_list.imdb WHERE 1 AND imdb.movies_list.imdb=:imdb";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 
		
		$stmt->bindParam(':imdb', $imdb );

		if ( $stmt->execute() ) {
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$this->printSummaryTable($rows);
		}
		
		//$imdb=str_replace("tt","",$imdb); // remove tt from imdb code
		$selectquery="select * from 1337x.search_results WHERE imdb=:imdb ORDER BY CAST(`seeds` as UNSIGNED) DESC";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 

		$stmt->bindParam(':imdb', $imdb );

		if ( $stmt->execute() ) {
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (count($results)>0) $this->printResults($results, "torrents");
			else print ('<h3>No results</h3>');
		}
	}

	private function printResults( $rows , $divClass ){
	
		$getKeys=array_keys($rows[0]);
		
		print ('<div class="'.$divClass.'">');
		print ('<h3>Total: '.count($rows).'</h3>');
		print ('<table>');
		print ('<thead><tr>');
		foreach ( $getKeys as $key ){print('<td>');	print($key);print('</td>');}
		print ('</tr></thead>');

		print ('<tbody>');
		$diff=false;		
		foreach ( $rows as $row ){

			if (!$diff){print ('<tr class="result-entry diff" id="'.$row['link'].'">');$diff=true;}
			else if ($diff){print ('<tr class="result-entry" id="'.$row['link'].'">');$diff=false;}
			foreach ( $row as $k=>$v){print('<td>');print($v);print('</td>');}
			/*
				$cells = '<td>'.$row['imdb'].'</td>';
				$cells .= '<td>'.$row['totalPages'].'/'.$row['activePages'].'</td>';
				$cells .= '<td>'.$row['totalTorrents'].'/'.$row['activeTorrents'].'</td>';
				$cells .= '<td>'.$row['last_checked'].'</td>';
				$cells .= '<td>'.$row['category'].'</td>';
				$cells .= '<td>'.$row['id'].'</td>';
				$cells .= '<td>'.$row['moviename'].'</td>';
				$cells .= '<td>'.$row['yearmovie'].'</td>';
				$cells .= '<td>'.$row['rating'].'</td>';
			print $cells;
			*/
			print ('</tr>');			
		}
		print ('</tbody>');
		print ('</table>');
		print ('</div>');
	
	}
	
	private function printSummaryTable( $rows ){

		$getKeys=array_keys($rows[0]);
		
		print ('<div class="results">');
		print ('<h3>Total: '.count($rows).' title searches.</h3>');
		print ('<table>');
		print ('<thead><tr>');		
		//foreach ( $getKeys as $key ){print('<td>');	print($key);print('</td>');}
		//imdb	totalPages	activePages	totalTorrents	activeTorrents	last_checked	category	id	moviename	yearmovie	rating	enabled
			$cellsHead='<td>imdb</td>';
			$cellsHead.='<td>moviename</td>';
			$cellsHead.='<td>activePages</td>';
			$cellsHead.='<td>activeTorrents</td>';
			$cellsHead.='<td>checked</td>';
			$cellsHead.='<td>cat</td>';
			$cellsHead.='<td>id</td>';
			$cellsHead.='<td>yearmovie</td>';
			$cellsHead.='<td>rating</td>';
			if ($this->MODE ==='SUMMARY')$cellsHead.='<td>@</td>';
		print ($cellsHead);
		print ('</tr></thead>');

		print ('<tbody>');
		$diff=false;		
		foreach ( $rows as $row ){

			//id="'.$row['imdb'].'"
			if (!$diff){print ('<tr class="sum-entry diff" >');$diff=true;}
			else if ($diff){print ('<tr class="sum-entry" >');$diff=false;}
			//foreach ( $row as $k=>$v){print('<td>');print($v);print('</td>');}
				$last_checked=timeDifference($row['last_checked']);

				$imdbLinkA='<a target="_blank" href="http://www.imdb.com/title/'.$row['imdb'].'/">';
				$cells = '<td class="imdb">'.$imdbLinkA.$row['imdb'].'</a></td>';
				$cells .= '<td class="moviename">'.$row['moviename'].'</td>';
				$cells .= '<td><span class="green-light">'.$row['activePages'].'</span>'.'/ '.$row['totalPages'].'</td>';
				$cells .= '<td><span class="green-light">'.$row['activeTorrents'].'</span>'.'/'.$row['totalTorrents'].'</td>';
				$cells .= '<td>'.$last_checked.'</td>';
				$cells .= '<td>'.$row['category'].'</td>';
				$cells .= '<td>'.$row['id'].'</td>';
				$cells .= '<td>'.$row['yearmovie'].'</td>';
				$cells .= '<td>'.$row['rating'].'</td>';
				if ($this->MODE ==='SUMMARY')$cells .= '<td class="view-results white small-text underline" id="'.$row['imdb'].'">view results</td>';
			print $cells;
			print ('</tr>');			
		}
		print ('</tbody>');
		print ('</table>');
		print ('</div>');
	}
}
?>