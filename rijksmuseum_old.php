<?php

	header('Content-Type: text/html; charset=utf-8');

	$total = 0;
	$size = 10;
	$count_pages = 0;
	$p = 0;

	$q = '';
	$array_search = array();

	$url = 'https://www.rijksmuseum.nl/api/nl/collection?key=z2IVtKQT&format=json&imgonly=True';

	if (isset($_GET['q']) && $_GET['q'] != '') {
		$q = urlencode($_GET['q']);
		$url .= '&q='.$q;
	} else {
		$url .= '&toppieces=True';
	}

	if (isset($_GET['p']) && $_GET['p'] != '') {
		$p = intval($_GET['p']);
	}

	$url .= '&p='.$p;
	$url .= '&ps='.$size;


	//echo $url;exit;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$json = curl_exec($ch);
	curl_close($ch);

	$array_search = json_decode($json, TRUE);

	$count_pages = ceil($array_search['count']/ $size);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="utf-8">
	<title>Rijksmuseum</title>
	<meta name="author" content="Frank Sträter">
	<meta name="description" content="Rijksmuseum">
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
</head>

<body>

	<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header hidden-xs">
				<a class="navbar-brand" href="#">RIJKS MUSEUM</a>
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

		foreach ($array_search['artObjects'] as $item) {

			if (is_null($item['webImage']['url'])) {
				continue;	
			}

?>

			<div class="card">
				<a href="<?= $item['links']['web'] ?>"><img src="<?= str_replace('=s0', '=s450', $item['webImage']['url']) ?>" class="card-image"></a>
				<div class="card-caption">
					<h4 class="card-title"><?= $item['title'] ?></h4>
					<p class="card-text"><?= $item['principalOrFirstMaker'] ?></p>
				</div>
			</div>
			
<?php
		}	
?>
		</div>

<?php

	if ($count_pages > 1) {

		$start_pagination = ((ceil($p/5) - 1) * 5) + 1;
		$end_pagination = $start_pagination + 4;

		if ($end_pagination > $count_pages) {
			$end_pagination = $count_pages;
		}

		$query = 'q='.urlencode($q);

?>
		<div class="text-center">
			<ul class="pagination">
				<li<?= ($start_pagination == 1) ? ' class="disabled"' : '' ?>><a href="?<?= $query ?>&amp;p=<?= $start_pagination-1 ?>">&laquo;</a></li>
<?php

	for ($i = $start_pagination; $i <= $end_pagination; $i++) {

		$request_uri = '?'.$query.'&amp;p='.$i;

		if ($p == $i) {
			$class = ' class="active"';
		} else {
			$class = '';
		}
	
		echo '<li'.$class.'><a href="'.$request_uri.'">'.$i.'</a></li>'.PHP_EOL;

	}
?>
				<li<?= ($end_pagination == $count_pages) ? ' class="disabled"' : '' ?>><a href="?<?= $query ?>&amp;p=<?= $end_pagination+1 ?>">&raquo;</a></li>
			</ul>
		</div>
<?php	
	}
?>
		
	</div>

</body>
</html>