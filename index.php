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

	// Default values

	$total = 0;
	$size = 12;
	$count_pages = 0;
	$page = 1;

	$q = '';
	$collection = '';

	$media_content_type_terms = array('image/jpeg','image/gif','image/png');

	$array_search = array();

	// We want to know the count per collection, so we use the facets

	$facets = array();
	$facets['collection'] = array();

	// We are only interested in hits with images, so default filter on media_content_type

	$filters = array();
	$filters['media_content_type'] = array('terms' => $media_content_type_terms);

	if (isset($_GET['q']) && $_GET['q'] != '') {

		if (isset($_GET['q']) && $_GET['q'] != '') {
			$q = urldecode($_GET['q']);
		}

		if (isset($_GET['collection']) && $_GET['collection'] != '') {
			$collection = urldecode($_GET['collection']);
			$filters['collection'] = array('terms' => array($collection));
		}

		if (isset($_GET['page']) && $_GET['page'] != '') {
			$page = intval($_GET['page']);
		}

		$offset = ($page - 1) * $size;

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
			'Content-Length: '.strlen($data_string),
			'X-Requested-With: XMLHttpRequest')
		);
		 
		$json = curl_exec($ch);

		$array_search = json_decode($json, TRUE);

		//print_r($array_search);exit;

		if (isset($array_search['hits']['total'])) {
			$total = $array_search['hits']['total'];
		}
		
		$count_pages = ceil($total/ $size);

	}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="utf-8">
	<title>Open Cultuur Data API Search</title>
	<meta name="author" content="Frank Sträter">
	<meta name="description" content="Open Cultuur Data API Search">
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
		#facets {
			max-width: 490px;
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

	if ($count_pages > 0) {
	
		// Facets collection

		echo '<ul id="facets" class="list-group">'.PHP_EOL;

		foreach($array_search['facets']['collection']['terms'] as $item) {
			echo '<li class="list-group-item">';
			echo '<span class="badge">'.$item['count'].'</span>';
			echo '<a href="?q='.$q.'&amp;collection='.urlencode($item['term']).'">'.$item['term'].'</a>';
			echo '</li>'.PHP_EOL;
		}

		if ($collection != '') {
			echo '<li class="list-group-item"><a href="?q='.$q.'">Alle collecties</a>'.PHP_EOL;
		}

		echo '</ul>'.PHP_EOL;

	}

?>
		<div class="row"> 
<?php

	if ($count_pages > 0) {

		foreach ($array_search['hits']['hits'] as $item) {

			$item_ocd_id = $item['_id'];
			$item_media_urls = $item['_source']['media_urls'];
			$item_collection = $item['_source']['meta']['collection'];
			$item_html_url = $item['_source']['meta']['original_object_urls']['html'];
			$item_ocd_url =  $item['_source']['meta']['ocd_url'];
			
			$item_title = '';
			$item_author = '';
			$item_year = '';

			if (isset($item['_source']['title'])) {
				$item_title = $item['_source']['title'];
			}

			if (isset($item['_source']['authors'])) {
				$item_author = $item['_source']['authors'][0];
			}

			if (isset($item['_source']['date'])) {
				$item_year = substr($item['_source']['date'],0,4);
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
						<p><?= $item_author ?> <?= $item_year ?></p>
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

		if ($end_pagination > $count_pages) {
			$end_pagination = $count_pages;
		}

		$query = 'q='.urlencode($q).'&amp;collection='.urlencode($collection);

?>
		<div class="text-center">
			<ul class="pagination">
				<li<?= ($start_pagination == 1) ? ' class="disabled"' : '' ?>><a href="?<?= $query ?>&amp;page=<?= $start_pagination-1 ?>">&laquo;</a></li>
<?php

	for ($i = $start_pagination; $i <= $end_pagination; $i++) {

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