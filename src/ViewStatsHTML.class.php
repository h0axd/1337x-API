<?php 
class ViewStatsHTML{

	private $dbh;
	private $MODE="HOME"; // $this->MODE="RESULTS"; // $this->MODE="AJAX";
	private $totalSearches=0;
	private $next=1;
	private $perPage=10;
	private $imdb;
	private $_1337x_id;
	private $title=[];

	public function __construct(){
		$this->dbh=dbhandler::getInstance();
	}

	public function viewStatsSummary($next,$perPage){

		$this->showSummary($next,$perPage);
	}

	public function viewResults($imdb){	
	
		$this->imdb=$imdb;
		$this->showResults($imdb);
	}
	
	public function viewTorrent( $imdb, $_1337x_id ){
	
		$this->imdb=$imdb;
		$this->$_1337x_id=$_1337x_id;
		$this->showTorrent( $imdb, $_1337x_id );
	
	}
	
	//Accepts AJAX MODE | live update
	public function showTotalStats($mode){ //sets $this->totalSearches for global useage

		if ($mode==="AJAX") $this->MODE="AJAX";

		$selectquery ="select count(*) from 1337x.search_summary";
		$stmt = $this->dbh->dbh->prepare($selectquery);
		$stmt->execute();
		$searches = $stmt->fetchColumn();
		$this->totalSearches=$searches;

		$selectquery ="select count(*) from 1337x.search_results";
		$stmt = $this->dbh->dbh->prepare($selectquery);
		$stmt->execute();
		$results = $stmt->fetchColumn();

		$selectquery ="select count(*) from 1337x.1337xtorrents";
		$stmt = $this->dbh->dbh->prepare($selectquery);
		$stmt->execute();
		$torrents = $stmt->fetchColumn();

		//if ($this->MODE!=="AJAX") print('<div id="update-stats-cont">'); // update-stats-cont already loaded in HTML page. 
		if ($this->MODE==="AJAX"):		
			print('<div id="update-stats">');
			print ('<div class="show-stats"><span "live-update">'.$searches.'</span> searches perfomed for imdb titles</div>');
			print ('<div class="show-stats"><span "live-update">'.$results.'</span> torrent results with seeds '.MIN_SEEDS.'</div>');
			print ('<div class="show-stats"><span "live-update">'.$torrents.'</span> torrents imported to db </div>');

			if ($this->MODE==="AJAX") {
				$files = new FilesystemIterator( '../JSON', FilesystemIterator::SKIP_DOTS);
				print '<div class="show-stats"><span "live-update"="">'.iterator_count($files).'</span> JSON files with torrent info</div>';
			}
			print('</div>');
		endif;
		
		//if ($this->MODE!=="AJAX") print('</div>');		
	}
	
	private function checkHealth($result){
		/*
		*	Use this function find results that belong to more than one imdb code 
		*   There are movies like Max(2015) and Mad Max: Fury Road(2015) which collect the same torrents
		*/
		//foreach ($results as $result):

			// First collect all 1337x_ids whitch match current imdb code
			$selectquery="select imdb, 1337x_id from 1337x.search_results WHERE imdb=:imdb";
			if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() );} 
			$stmt->bindParam(':imdb', $result['imdb'] );
			if ( $stmt->execute() ) {
				$_1337x_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}

			// Then foreach 1337x_id find records where imdb!= from current imdb code

			$collectMismatchIMDB=[];
			foreach ($_1337x_ids as $id ):

				$selectquery="select imdb, 1337x_id from 1337x.search_results WHERE imdb!=:imdb AND 1337x_id=:1337x_id";

				if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() );} 
				$stmt->bindParam(':imdb', $result['imdb'] );
				$stmt->bindParam(':1337x_id', $id['1337x_id'] );				
				if ( $stmt->execute() ) {
					$_1337x_ids_mismatch = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$counter=0;
					foreach ($_1337x_ids_mismatch as $mismatch){
						array_push($collectMismatchIMDB,$mismatch);
						//if ( ++$counter<5 ){var_dump($mismatch);}
					}
				}
				
			endforeach;
			

			// Collect imdb codes mismatch
			$imdbCodes=[];
			foreach ( $collectMismatchIMDB as $current ){
				//print ('<br>Found mismatch for '.$current['imdb'] );
				array_push($imdbCodes,$current['imdb']);
			}
			$imdbGroup=array_unique($imdbCodes);
			
			//print ('<br>Found mismatch for '.$result['imdb']." total results: ".count($collectMismatchIMDB)."<br>" );
			$mismatches=[];
			$mismatches['total']=count($collectMismatchIMDB);
			$mismatches['imdb']=$imdbGroup;
			return $mismatches;

		//endforeach;
	}

	private function showSummary($next,$perPage){  //called from public viewStatsSummary()

		if ($this->next!==$next) $this->next=$next;
		if ($this->perPage!==$perPage) $this->perPage=$perPage;
		$next=(int)$this->next;
		$perPage=(int)$this->perPage;
		
		/*AND imdb.movies_list.yearmovie=2014 */ 
		/*AND moviename LIKE '%Lord of the%' */

		$selectquery ="select * from 1337x.search_summary JOIN imdb.movies_list ON search_summary.imdb=imdb.movies_list.imdb 

		/*ORDER BY activeTorrents DESC LIMIT :nextResults, :perPage */
		";
		print ($selectquery);
		
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { 
			var_dump ( $dbh->dbh->errorInfo() );
		} 

		$stmt->bindParam(':nextResults', $next , PDO::PARAM_INT );
		$stmt->bindParam(':perPage', $perPage , PDO::PARAM_INT );
		//$stmt->bindValue(':nextResults', $next , PDO::PARAM_INT );

		if ( $stmt->execute() ) { 

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if (count($rows)>0):
				$this->printSummaryTable($rows);
			else:
				print ("<h2>No results. Check your query.</h2>");
			endif;
		}
		
		//var_dump($stmt->errorInfo());
	}

	private function showResults($imdb){
	
		$this->MODE="RESULTS";

		$selectquery ="select * from 1337x.search_summary JOIN imdb.movies_list ON search_summary.imdb=imdb.movies_list.imdb WHERE 1 AND imdb.movies_list.imdb=:imdb";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 
		
		$stmt->bindParam(':imdb', $imdb );

		if ( $stmt->execute() ) {
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$this->printSummaryTable($rows);
			$this->title=$rows[0];
		}
		
		//$imdb=str_replace("tt","",$imdb); // remove tt from imdb code
		$selectquery="select * from 1337x.search_results WHERE imdb=:imdb ORDER BY CAST(`seeds` as UNSIGNED) DESC";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 

		$stmt->bindParam(':imdb', $imdb );
		
		print ($imdb);

		if ( $stmt->execute() ) {
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		
		if (count($results)>0){
		
			//$collect1337x_ids=[];
			$torrents=[];
			foreach ( $results as $result ):

				//array_push($collect1337x_ids,$result['1337x_id']);
				$selectquery="select * from 1337x.1337xtorrents 
				JOIN 1337x.search_results ON  1337x.search_results.1337x_id=1337xtorrents.1337x_id AND 1337x.search_results.imdb=1337xtorrents.imdbmatch 
				WHERE 1337xtorrents.1337x_id=:1337x_id AND imdbmatch=:imdb ORDER BY 1337xtorrents.seeds DESC";

				if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 
				$id=$result['1337x_id'];
				$stmt->bindParam(':1337x_id', $id );
				$stmt->bindParam(':imdb', $imdb );
				if ( $stmt->execute() ) {
					$torrent = $stmt->fetchAll(PDO::FETCH_ASSOC);
					if (count($torrent)>0)array_push($torrents, $torrent[0]);
				}else {
					var_dump ( $stmt->errorInfo() );
				}

			endforeach;
			
			// Swap the two views below
			$this->printTorrents($torrents);
			//$this->printResults($results, "torrents");
		
		}
		else print ('<h3>No results#</h3>');
		
	}
	
	private function printTorrents($torrents){
		if (count($torrents)>0):
		
			print ('<h3>'.count($torrents).' Torrents collected (match imdb in torrent page) </h3>');
			$counter=0;
			foreach ( $torrents as $torrent ):

				//print('<pre>');var_dump($torrent);print('</pre>');
				//print ( ++$counter.". ".$torrent[0]['titlename']."<br>");
				print ( ++$counter.". ");
				print ('<a rel="noreferrer" target="_blank" href="https://1337x.to/torrent/'.$torrent['1337x_id'].'/'.$torrent['link'].'/">'.$torrent['link'].'</a>');
				print ("<br>");

			endforeach; // torrents
		
		endif; //count>0
	}
	
	private function showTorrent( $imdb, $_1337x_id ){

		$this->MODE="TORRENT";

		$selectquery ="select * from 1337x.search_summary JOIN imdb.movies_list ON search_summary.imdb=imdb.movies_list.imdb WHERE 1 AND imdb.movies_list.imdb=:imdb";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 
		
		$stmt->bindParam(':imdb', $imdb );

		if ( $stmt->execute() ) {
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$this->printSummaryTable($rows);
		}
		
		//$imdb=str_replace("tt","",$imdb); // remove tt from imdb code
		$selectquery="select * from 1337x.search_results WHERE imdb=:imdb AND 1337x_id=:1337x_id";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 

		$stmt->bindParam(':imdb', $imdb );
		$stmt->bindParam(':1337x_id', $_1337x_id );

		if ( $stmt->execute() ) {
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (count($results)>0) $this->printResults($results, "torrents");
			else print ('<h3>No results</h3>');
		}


		$selectquery="select * from 1337x.1337xtorrents where 1337x_id=:1337x_id";
		if ( !$stmt = $this->dbh->dbh->prepare($selectquery) ) { var_dump ( $dbh->dbh->errorInfo() ); } 

		$stmt->bindParam(':1337x_id', $_1337x_id );

		if ( $stmt->execute() ) {
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if (count($results)>0) $this->printTorrent($results);
			else print ('<h3>No results</h3>');
		}
		//var_dump($stmt->errorInfo());
	}

	private function printTorrent( $results ){
	
		foreach ($results as $r ){
			print('<pre class="torrent-info">');
				//print_r($r);
				foreach ($r as $k=>$v){
					if ( $k==='links'){
						print ( $k." => " );
						print_r(unserialize($v));
					} 
					else if ( $k==='images'){
						print ( $k." => " );
						print_r(unserialize($v));
					} 

					else {
						print $k." => ".$v.n;
					}
				}
			print('</pre>');
		}
	}
	// Results page
	private function printResults( $rows , $divClass ){
	
		$getKeys=array_keys($rows[0]);
		
		print('<div id="view-json">');
		print('</div>');
		print ('<div id="folder-'.$this->imdb.'" class="download-torrent-pages">Download torrent HTML pages</div>');
		print ('<pre id="terminal"><div id="download-torrent-results"></div></pre>');
		
		print ('<div class="'.$divClass.'">');
		print ('<h3>All 1337x.search_results: '.count($rows).'</h3>');
		
		/*
			Create a new seach instance just to get download link for search results page.
		*/
		$search = new SearchResults1337x();
		print ("Search URL: ".$search->getDownloadURL($this->title));
		
		print ('<table>');
		print ('<thead><tr>');
		print('<td>');print('</td>');
		foreach ( $getKeys as $key ){print('<td>');	print($key);print('</td>');}
		print ('</tr></thead>');

		print ('<tbody>');
		$diff=false;
		$counter=0;
		foreach ( $rows as $row ){

			if (!$diff){print ('<tr class="result-entry diff" id="'.$row['link'].'">');$diff=true;}
			else if ($diff){print ('<tr class="result-entry" id="'.$row['link'].'">');$diff=false;}
			
			print('<td>');print( ++$counter );print('</td>');
			
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
			print('<td>');print ('<a href="view-stats.php?imdb='.$this->imdb.'&1337x_id='.$row['1337x_id'].'">');print("view torrent");print ('</a>');print('</td>');
			print ('</tr>');
		}
		print ('</tbody>');
		print ('</table>');
		print ('</div>');
	
	}
	
	// HOME Page
	private function printSummaryTable( $rows ){

		$getKeys=array_keys($rows[0]);
		
		print ('<div class="results">');
		print ('<div class="results-header">');
			print ('<h3 class="white">1337x.search_summary '.count($rows).' of '.$this->totalSearches.' title searches.</h3>');
			
			if ( $this->MODE === "HOME"):
				print ('<a href="view-stats.php?next='.($this->next-50).'&perPage=50">-<span class="small-text">50</span></a>');
				print ('<a href="view-stats.php?next='.($this->next-10).'&perPage=10">-prev <span class="small-text">10</span></a>');
				print ('<a href="view-stats.php?next='.($this->next+10).'&perPage=10">+next <span class="small-text">10</span></a>');
				print ('<a href="view-stats.php?next='.($this->next+50).'&perPage=50">+ <span class="small-text">50</span></a>');
			endif;

		print ('</div>');
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
			if ($this->MODE ==='HOME')$cellsHead.='<td>results</td>';
			if ($this->MODE ==='HOME')$cellsHead.='<td>common</td>';			
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
				if ($this->MODE ==='HOME')$cells .= '<td class="view-results white small-text underline" id="'.$row['imdb'].'">view results</td>';
				
				if ($this->MODE ==='HOME'){}
				
				$health=$this->checkHealth($row);
				if($health['total']>0){
					$cells .= '<td class="view-health white small-text underline">'.$health['total'].' results</td>';
				} else {
					$cells .= '<td class="white small-text">OK</td>';
				}

				
			print $cells;
			print ('</tr>');			
		}
		print ('</tbody>');
		print ('</table>');
		print ('</div>');
	}
}
?>
