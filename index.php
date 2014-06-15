<?php

	header('Content-Type: text/html; charset=utf-8');

	// Function to limit long titles
	
	function character_limiter($str, $n = 500, $end_char = '&#8230;') {
		if (strlen($str) < $n) {
			return $str;
		}

		$str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

		if (strlen($str) <= $n) {
			return $str;
		}

		$out = "";
		foreach (explode(' ', trim($str)) as $val) {
			$out .= $val.' ';

			if (strlen($out) >= $n) {
				$out = trim($out);
				return (strlen($out) == strlen($str)) ? $out : $out.$end_char;
			}
		}
	}

	// Function to get redirect location of the OpenCultuurData Resolver URLs
	// We use this as a temporary solution to get smaller sized images from Rijksmuseum

	function resolve_url($url) {

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		$output = curl_exec($ch);

		preg_match_all('/^Location:(.*)$/mi', $output, $matches);

		$redirect_url = !empty($matches[1]) ? trim($matches[1][0]) : $url;

		return $redirect_url;

	}

	// Default values, default size 42 because... well, it's the answer to everything

	$total = 0;
	$size = 42;
	$page = 1;
	$datefrom = '0000-01-01';
	$dateto = date('Y-m-d');

	$q = '';
	$author = '';
	$collection = '';
	$yearfrom = '';
	$yearto = '';

	// Is the search through the extended form and show it after response? Default no.

	$bln_extended = FALSE;

	$media_content_type_terms = array('image/jpeg','image/gif','image/png');
	$array_search = array();

	if (isset($_GET['q']) && $_GET['q'] != '') {

		if (isset($_GET['q']) && $_GET['q'] != '') {
			$q = urldecode($_GET['q']);
		}

		if (isset($_GET['page']) && $_GET['page'] != '') {
			$page = intval($_GET['page']);
		}

		$offset = ($page - 1) * $size;

		// We are only interested in hits with images, so default filter on media_content_type

		$filters = array();
		$filters['media_content_type'] = array('terms' => $media_content_type_terms);

		// We want to know the count per collection, so we use the facets

		$facets = array();
		$facets['collection'] = array();

		if (isset($_GET['collection']) && $_GET['collection'] != '') {
			$collection = urldecode($_GET['collection']);
			$filters['collection'] = array('terms' => array($collection));
			$bln_extended = TRUE;
		}

		if (isset($_GET['author']) && $_GET['author'] != '') {
			$author = urldecode($_GET['author']);
			$filters['author'] = array('terms' => array($author));
			$bln_extended = TRUE;
		}

		if (isset($_GET['yearfrom']) && $_GET['yearfrom'] != '') {
			$yearfrom = $_GET['yearfrom'];
			$datefrom = $yearfrom.'-01-01';
			$bln_extended = TRUE;
		}

		if (isset($_GET['yearto']) && $_GET['yearto'] != '') {
			$yearto = $_GET['yearto'];
			$dateto = $yearto.'-12-31';
			$bln_extended = TRUE;
		}

		$filters['date'] = array('from' => $datefrom, 'to' => $dateto);

		$data = array(
			'query' => $q,
			'filters' => $filters,
			'facets' => $facets,
			'size' => $size,
			'from' => $offset
			);

		$data_string = json_encode($data);

		$ch = curl_init('http://api.opencultuurdata.nl/v0/search');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string))
		);
		 
		$json = curl_exec($ch);

		$array_search = json_decode($json, TRUE);

		/*

		echo '<pre>';
		//print_r($array_search);

		print_r($array_search['facets']['collection']['terms']);

		echo '</pre>';

		exit;

		*/

		$total = $array_search['hits']['total'];

		$data_query = array();
		$data_query['q'] = $q;

		if ($author != '') {
			$data_query['author'] = $author;
		}

		if ($collection != '') {
			$data_query['collection'] = $collection;
		}

		if ($yearfrom != '') {
			$data_query['yearfrom'] = $yearfrom;
		}

		if ($yearto != '') {
			$data_query['yearto'] = $yearto;
		}

		$count_pages = ceil($total/ $size);

	}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="utf-8">
	<title>Open Cultuur Data API</title>
	<meta name="author" content="Frank Sträter">
	<meta name="description" content="Open Cultuur Data API Images Search">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- We need to include the CSS instead of using CSS through CDN for IE8 support -->
	<link href="bootswatch.css" rel="stylesheet">
	<style type="text/css">
		body {
			padding-top: 70px;
			padding-bottom: 70px;
		}
		.thumbnail {
			min-height: 650px;
		}
		.thumb-image {
			display: block;
			height: 400px;
			background-repeat: no-repeat;
			background-position: center center;
			background-size: cover;
		}
		#facets, #uitgebreid {
			max-width: 610px;
		}
	</style>
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->
</head>

<body>

	<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse-1">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">Open Cultuur Data API</a>
			</div>

			<div class="collapse navbar-collapse" id="navbar-collapse-1">
				<form class="navbar-form navbar-left" role="search" method="get">
					<div class="form-group">
						<input type="text" class="form-control" name="q" value="<?= $q ?>">
					</div>
					<button type="submit" class="btn btn-default">Zoeken</button>
				</form>
				<ul class="nav navbar-nav">
					<li><a href="#" data-toggle="collapse" data-target="#uitgebreid">Uitgebreid</a></li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li><a href="http://www.opencultuurdata.nl/">Home</a></li>
					<li><a href="http://www.opencultuurdata.nl/harvest/">Harvest</a></li>
					<li><a href="http://docs.opencultuurdata.nl/">Docs</a></li>
				</ul>
			</div>
		</div>
	</nav>

	<div class="container">

<?php
	
	// Facets collection

	if ($collection == '') {

		echo '<ul id="facets" class="list-group">'.PHP_EOL;

		foreach($array_search['facets']['collection']['terms'] as $item) {
			echo '<li class="list-group-item"><span class="badge">'.$item['count'].'</span><a href="?q='.$q.'&amp;collection='.urlencode($item['term']).'">'.$item['term'].'</a></li>';
		}

		echo '</ul>'.PHP_EOL;

	}

?>

		<div id="uitgebreid" class="collapse<?= ($bln_extended) ? ' in' : '' ?>">

			<form class="form-horizontal" role="form" method="get">

				<div class="form-group">
					<label for="qm" class="col-sm-2 control-label">Zoekterm</label>
					<div class="col-sm-10">
						<input type="text" class="form-control" id="qm" name="q" value="<?= $q ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="author" class="col-sm-2 control-label">Auteur</label>
					<div class="col-sm-10">
						<input type="text" class="form-control" id="author" name="author" value="<?= $author ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="collection" class="col-sm-2 control-label">Collectie</label>
					<div class="col-sm-10">
						<input type="text" class="form-control" id="collection" name="collection" value="<?= $collection ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="yearfrom" class="col-sm-2 control-label">Jaar</label>
					<div class="col-sm-10">
						<input type="number" class="form-control" id="yearfrom" name="yearfrom" value="<?= $yearfrom ?>" style="width:180px">
					</div>
				</div>
				<div class="form-group">
					<label for="yearto" class="col-sm-2 control-label">t/m</label>
					<div class="col-sm-10">
						<input type="number" class="form-control" id="yearto" name="yearto" value="<?= $yearto ?>" style="width:180px">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
						<button type="submit" class="btn btn-default">Zoeken</button>
					</div>
				</div>

			</form>

		</div>

		<div class="row"> 

<?php

	if (count($array_search) > 0) {

		foreach ($array_search['hits']['hits'] as $item) {

			$item_ocd_id = $item['_id'];
			$item_media_urls = $item['_source']['media_urls'];
			$item_collection = $item['_source']['meta']['collection'];
			$item_html_url = $item['_source']['meta']['original_object_urls']['html'];
			$item_ocd_url =  $item['_source']['meta']['ocd_url'];
			
			$item_title = '';
			$item_author = '';
			$item_date = '';

			if (isset($item['_source']['title'])) {
				$item_title = $item['_source']['title'];
			}

			if (isset($item['_source']['authors'])) {
				$item_author = $item['_source']['authors'][0];
			}

			if (isset($item['_source']['date'])) {
				$item_date = substr($item['_source']['date'],0,4);
			}

?>
			<div class="col-sm-6 col-md-4">
				<div class="thumbnail">
					<?php

						foreach ($item_media_urls as $media_item) {

							// Skip the non-image media urls (for example Openbeelden videos)

							if (!in_array($media_item['content_type'], $media_content_type_terms)) {
								continue;
							}

							// Pick the 500px image (Beeldbank Nationaal Archief)

							if (isset($media_item['width']) && $media_item['width'] == 500) {
								$img_url = $media_item['url'];
								break;
							}

							// or pick the last image left (for example Rijksmuseum, Openbeelden)

							$img_url = $media_item['url'];
						}

						// Get the Rijksmuseum thumbnail version
						
						if ($item_collection == 'Rijksmuseum') {
							$img_url = resolve_url($img_url);
							$img_url = str_replace('%3Ds0', '=s450', $img_url);
						}
						
					?>
					<a href="<?= $item_html_url ?>" class="thumb-image" style="background-image: url('<?= $img_url ?>')"></a>
					<div class="caption">
						<h4><?= character_limiter($item_title,100) ?></h4>
						<p><?= $item_author ?>, <?= $item_date ?></p>
						<p><?= $item_collection ?></p>
						<hr>
						<p><small><a href="<?= $item_ocd_url ?>"><?= $item_ocd_id ?></a></small></p>
					</div>
				</div>
			</div>
<?php
		}	
	}
?>

		</div>

<?php

	if ($count_pages > 1) {

		$start_pagination = ((ceil($page/6) - 1) * 6) + 1;
		$end_pagination = $start_pagination + 5;

		$query = http_build_query($data_query, '', '&amp;');

?>
		<div class="text-center">
			<ul class="pagination">
				<li<?= ($start_pagination == 1) ? ' class="disabled"' : '' ?>><a href="?<?= $query ?>&amp;page=<?= $start_pagination-1 ?>">&laquo;</a></li>
<?php

	for ($i=$start_pagination;$i<=$end_pagination;$i++) {

		$request_uri = '?'.$query.'&amp;page='.$i;

		if ($page == $i) {
			$class = ' class="active"';
		} else {
			$class = '';
		}
	
		echo '<li'.$class.'><a href="'.$request_uri.'">'.$i.'</a></li>'.PHP_EOL;

	}
?>
				<li<?= ($end_pagination == $count_pages) ? ' class="disabled"' : '' ?>><a href="?<?= $query ?>&amp;page=<?= $end_pagination+1 ?>">&raquo;</a></li>
			</ul>
		</div>
<?php	
	}
?>

		<p class="text-muted text-center">Design &amp; Development by Frank Sträter for Opencultuurdata.nl</p>
		
	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>

</body>
</html>