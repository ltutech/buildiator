<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="css/main.css">
		<link rel="stylesheet" href="css/progress_bar.css">
		<?php
		function getViewName()
		{
			$view = $_GET['view'];
			if (!isset($view)) {
				$view = 'devteam';
			}
			return $view;
		}
		echo '<link rel="stylesheet" href="/styles/' . getViewName() . '.css" type="text/css" media="screen" title="main StyleSheet" charset="utf-8" />';
		?>
		<title>buildiator</title>
	</head>
	<body>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.js"></script>
		<script type='text/javascript'>
			var VIEW_NAME = '<?php echo getViewName(); ?>';
		</script>
		<script type='text/javascript' src="js/buildator.js"></script>
		<div id="jobs">
			<!--this is where the jobs go-->
		</div>
	</body>
</html>
