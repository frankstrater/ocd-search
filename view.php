<?php

	header('Content-Type: text/html; charset=utf-8');

	$array_object = array();

	$title = '';
	$authors = '';
	$description = '';
	$collection = '';
	$rights = '';

	if (isset($_GET['url']) && $_GET['url'] != '') {
		$url = $_GET['url'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$json = curl_exec($ch);
		curl_close($ch);

		$item = json_decode($json, TRUE);

		$title = $item['title'];

		if (isset($item['authors'])) {
			$authors = join($item['authors'],'<br>');
		}

		if (isset($item['description'])) {
			$description = $item['description'];
		}
		
		$collection = $item['meta']['collection'];
		$rights = $item['meta']['rights'];
		$media_urls = $item['media_urls'];

		$media_content_type_terms = array('image/jpeg','image/jpg','image/gif','image/png');

		foreach ($media_urls as $media_item) {

			// Skip the non-image media urls (for example Openbeelden videos)

			if (!in_array($media_item['content_type'], $media_content_type_terms)) {
				continue;
			}

			$img_url = $media_item['url'];
		}

	}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="utf-8">
	<title>Open Cultuur Data</title>
	<meta name="description" content="Open Cultuur Data">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link href="//fonts.googleapis.com/css?family=RobotoDraft" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet">
	<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

	<style type="text/css">
		html {
			height: 100%;
		}

		body {
			height: 100%;
		    background-image: url('assets/img/low_contrast_linen.png');
		}

		.row {
			height: 100%;
			margin-left: 0px;
			margin-right: 0px;
		}

		.col-md-4 {
			padding-left: 0px;
			padding-right: 0px;
			background-color: #f2f2f2;
		}

		.col-md-8 {
			padding-left: 0px;
			padding-right: 0px;
		}

		.info {
			padding: 16px 32px;
		}

		.portrait {
			padding: 30px;
			text-align: center;
		}

		.portrait img {
			margin: 0 auto;
			
			-webkit-box-shadow: 0px 5px 10px 0px rgba(0,0,0, 0.5);
			-moz-box-shadow:    0px 5px 10px 0px rgba(0,0,0, 0.5);
			box-shadow:         0px 5px 10px 0px rgba(0,0,0, 0.5);
		}

		@media(min-width: 768px) {

			.portrait img {
				max-width: 600px;
			}

		}

		@media(min-width: 992px) {

			.col-md-4 {
				min-height: 100%;
				box-shadow: -1px 0 0 rgba(0, 0, 0, 0.3) inset;
			}

			.col-md-8 {
				height: 100%;
			}

			.wall {
				display: table;
				height: 100%;
				width: 100%;
				margin: 0;
				padding: 0;
			}

			.central {
			    display: table-cell;
			    margin: 0;
			    padding: 0;
			    vertical-align: middle;
			}

			.portrait img {
				max-height: 580px;
			}

		}

		@media(min-width: 1200px) {

			body {
				line-height: 26px;
			}

			.info {
				padding: 30px 60px 60px 30px;
			}

			.portrait img {
				max-height: 720px;
				max-width: 800px;
			}

		}
	</style>
	
</head>

<body>

	<div class="row">
		
		<div class="col-md-8">

			<div class="wall">
				<div class="central">
					<div class="portrait">
						<img id="portret" class="img-responsive" src="<?= $img_url ?>">
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-4">
			<div class="info">
				<h4><?= $title ?></h4>
				<p><?= $description ?></p>
				<p><?= $authors ?></p>
				<p>
					<?= $collection ?><br>
					<?= $rights ?>
				</p>
			</div>
		</div>

	</div>

</body>
</html>