<html>
	<head>
		<script type="text/javascript" src="jquery-3.6.0.min.js"></script>
		<script type="text/javascript" src="index.js"></script>
		<link type="text/css" rel="stylesheet" href="index.css" />
	</head>
	<body>
		<div id="main">
			<div class="title">Server File Content Search</div>
			<form id="search-form">
				<input class="query" type="text" placeholder="Enter your search..." />
				<br />
				<input class="extensions" type="text" placeholder="File extensions i.e css, php, js. Leave empty to search all files." />
				<br />
				<div>
					<input class="start-directory" type="text" placeholder="path/to/folder/" readonly />
					<br />
					<select class="directory-selector" size="3"></select>
				</div>
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