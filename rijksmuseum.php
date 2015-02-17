<?php

	header('Content-Type: text/html; charset=utf-8');

	$total = 0;
	$size = 12;
	$count_pages = 0;
	$p = 1;

	$q = '';
	$array_search = array();

	$url = 'https://www.rijksmuseum.nl/api/nl/collection?key=z2IVtKQT&format=json&imgonly=true';

	if (isset($_GET['q']) && $_GET['q'] != '') {
		$q = urlencode($_GET['q']);
		$url .= '&q='.$q;
	} else {
		$url .= '&toppieces=true';
	}

	if (isset($_GET['p']) && $_GET['p'] != '') {
		$p = intval($_GET['p']);
	}

	$url .= '&p='.$p;

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

	<style type="text/css">


		body {
			color: #212121;
    font-family: "RobotoDraft","Helvetica Neue",Helvetica,Arial,sans-serif;
    font-size: 14px;
    line-height: 1.4;
		}

		.item {
		  width: 364px;
		  margin: 8px;
		  float: left;
		
			background-color: #fff;
			border-radius: 2px;
			box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.16);
		}

		.item .thumb-image img {
			display: block;
			height: auto;
			width: 100%;
			max-width: 100%;
		}

		.item .caption {
			padding: 16px 16px 6px 16px;
		}

		.item .caption h4 {
			font-size: 18px;

    font-family: "Noto Sans","Helvetica Neue",Helvetica,Arial,sans-serif;
    font-weight: 500;
    line-height: 1.5;

			padding: 0;
			margin: 0 0 8px 0;
		}

		.item .thumb-footer {
			padding: 16px;
			border-top: 1px solid #eee;
			text-transform: uppercase;
		}

		.item .thumb-footer a {
			margin-right: 16px;
		}

	</style>
	
</head>

<body>


	<div class="container">

			
<?php

		foreach ($array_search['artObjects'] as $item) {

			if (is_null($item['webImage']['url'])) {
				continue;	
			}

?>

			<div class="item">
				<div class="thumb-image">
					<a href="<?= $item['links']['web'] ?>"><img src="<?= str_replace('=s0', '=s450', $item['webImage']['url']) ?>"></a>
				</div>
				<div class="caption">
					<h4><?= $item['title'] ?></h4>
					<p><?= $item['principalOrFirstMaker'] ?></p>
				</div>
			</div>
			
<?php
		}	
?>

	</div>


	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
	<script src="assets/js/jquery.masonry.min.js"></script>

	<script type="text/javascript">

	var $container = $('.container');
		$container.imagesLoaded(function(){
		  $container.masonry({
		    itemSelector : '.item',
		    columnWidth: function( containerWidth ) {
			    return containerWidth / 3;
			  }
		  });
		});


	</script>
</body>
</html>