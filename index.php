<html>
	<head>
		<script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
		<script type="text/javascript" src="index.js"></script>
		<link type="text/css" rel="stylesheet" href="index.css" />
	</head>
	<body>
		<div id="main">
			<div class="title">Server File Content Search</div>
			<form id="search-form">
				<input class="query" type="text" placeholder="Enter your search..." />
				<br />
				<input class="extentions" type="text" placeholder="File extentions i.e css, php, js" />
				<br />
				<input class="start-directory" type="text" placeholder="path/to/folder/" />
				<br />
				<input type="submit" value="Search" />
			</form>
			<div id="state-details"></div>
			<div class="search-details">
				Results found: <span class="results-amount">0</span><br />
				Files Searched: <span class="files-amount">0</span><br />
				Folders Searched: <span class="folders-amount">0</span><br />
			</div>
			<br />
			<div class="result-container">
			</div>
		</div>
	</body>
</html>