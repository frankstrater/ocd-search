<?php

	header('Content-Type: text/html; charset=utf-8');

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
	$size = 11;
	$count_pages = 0;
	$page = 1;

	$q = '';
	$collection = '';

	$media_content_type_terms = array('image/jpeg','image/jpg','image/gif','image/png');

	$array_search = array();

	// We want to know the count per collection, so we use the facets

	$facets = array();
	$facets['collection'] = new stdClass;

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

		if (isset($_GET['source']) && $_GET['source'] != '') {
			header('Content-Type: application/json; charset=utf-8');
			echo $json;exit;
		}

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
	<title>Open Cultuur Data</title>
	<meta name="author" content="Frank Sträter">
	<meta name="description" content="Open Cultuur Data">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link href="//fonts.googleapis.com/css?family=RobotoDraft" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet">
	<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
	<link href="assets/css/screen.css" rel="stylesheet">
	<!--[if lte IE 9]>
		<style type="text/css">
			.container {
				max-width: 600px;
			}
		</style>
	<![endif]-->

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	<link href="assets/fancybox/jquery.fancybox.css?v=2.1.5" rel="stylesheet">
	<script src="assets/fancybox/jquery.fancybox.pack.js?v=2.1.5"></script>
</head>

<body>

	<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header hidden-xs">
				<a class="navbar-brand" href="#"><img src="assets/img/logo.png"></a>
			</div>
			<form class="navbar-form" role="search" method="get">
				<div class="form-group">
					<div class="input-group">
						<input type="text" name="q" value="<?= $q ?>" class="form-control">
						<span class="input-group-btn">
							<button class="btn btn-default" type="submit"><span class="fa fa-search"></span></button>
						</span>
					</div>
				</div>
			</form>
		</div>
	</nav>

	<div class="container">
		<div class="masonry">
			
<?php

	if ($count_pages > 0) {

?>
			<div class="panel panel-default">
		 		<ul class="list-group">
<?php

		foreach($array_search['facets']['collection']['terms'] as $item) {
			echo '<li class="list-group-item">';
			echo '<span class="badge">'.$item['count'].'</span>';
			echo '<a href="?q='.$q.'&amp;collection='.urlencode($item['term']).'">'.$item['term'].'</a>';
			echo '</li>'.PHP_EOL;
		}

		if ($collection != '') {
			echo '<li class="list-group-item"><a href="?q='.$q.'"><span class="fa fa-long-arrow-left"></span> Alle collecties</a>'.PHP_EOL;
		}

?>
				</ul>
			</div>
<?php

	}

?>
			
<?php

	if ($count_pages > 0) {

		foreach ($array_search['hits']['hits'] as $item) {

			$item_ocd_id = $item['_id'];
			$item_media_urls = $item['_source']['media_urls'];
			$item_collection = $item['_source']['meta']['collection'];
			$item_html_url = reset($item['_source']['meta']['original_object_urls']);
			$item_ocd_url =  $item['_source']['meta']['ocd_url'];
			
			$item_title = '';
			$item_author = '';
			$item_year = '';

			if (isset($item['_source']['title'])) {
				$item_title = $item['_source']['title'];
			}

			if (isset($item['_source']['authors'])) {
				$item_author = join($item['_source']['authors'], '<br>');
			}

			if (isset($item['_source']['date'])) {
				$item_year = substr($item['_source']['date'],0,4);
			}

?>

			<div class="card">
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
				<a class="fancybox" data-fancybox-type="iframe" href="view.php?url=<?= $item_ocd_url ?>"><img class="card-image" src="<?= $img_url ?>"></a>
				<div class="card-caption">
					<h4 class="card-title"><?= $item_title ?> <small><?= $item_year ?></small></h4>
					<p class="card-text"><?= $item_author ?></p>
				</div>
				<div class="card-footer">
					<a href="<?= $item_html_url ?>"><?= $item_collection ?></a>
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
		
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			$(".fancybox").fancybox({
				padding     : 0,
				fitToView	: false,
				width		: '95%',
				height		: '95%',
				autoSize	: false,
				closeClick	: false,
				openEffect	: 'none',
				closeEffect	: 'none'
			});
		});
	</script>

</body>
</html>