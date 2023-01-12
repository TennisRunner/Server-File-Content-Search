<?php

class API
{
	function init()
	{
		try
		{
			$json = file_get_contents('php://input');

			$data = json_decode($json);
		}
		catch(Exception $err)
		{

		}

		if(isset($data) == false)
			return;

		if($data->action == "get_start_directory")
		{
			echo json_encode(Array("startDirectory" => getcwd()));
			exit(0);
		}
		if($data->action == "get_directories")
		{
			$this->getDirectories($data->startPath);
		}
		else if($data->action == "do_search")
		{
			$this->doSearch($data);
		}
	}

	function getDirectories($startPath)
	{
		$startPath = realpath($startPath);
		$directories = glob($startPath . "/*", GLOB_MARK | GLOB_ONLYDIR);

		foreach($directories as $key => $value)
			$directories[$key] = trim(substr($value, strlen($startPath)), "/");

		array_unshift($directories, "..");

		echo json_encode(Array(
			"directories" => $directories,
			"realPath" => $startPath
		));

		exit(0);
	}

	function doSearch($data)
	{
		$matches = Array();
		
		$matchStringOffset = 3;
		$matchStringLineCount = 5;
		
		$filesSearched = 0;
		$foldersSearched = 0;
		$totalMatches = 0;
		$query = $data->query;
		$method = $data->method;
		$startDirectory = $data->startDirectory;
		$topLevelOnly = $data->topLevelOnly;
		$extensions = $data->extensions;
		
		$directories = Array($startDirectory);
		
		for($i = 0; $i < count($directories); $i++)
		{
			$dir = $directories[$i];

			$resultDirectories = glob($dir . "/*", GLOB_NOSORT);
			
			for($k = 0; $k < count($resultDirectories); $k++)
			{
				$foldersSearched++;
				$dir2 = $resultDirectories[$k];
				
				if(is_dir($dir2) == true)
				{
					array_push($directories, $dir2);
					continue;
				}			
					
				$parts = explode(".", $dir2);
					
				$fileExtention = strtolower($parts[count($parts) - 1]);
								
				if((in_array($fileExtention, $extensions) !== false || count($extensions) == 0) == false)
					continue;

				$filesSearched++;
				
				$content = file_get_contents($dir2);
				$index = -1;
				$match = null;
				
				while(true)
				{
					$index = strpos($content, $query, $index + 1);
					
					if($index !== false)
					{
						// Get the 3 surrounding lines of text around the match
						$tempIndex = $index;
						
						
						for($j = 0; $j < $matchStringOffset; $j++)
							$tempIndex = $this->getBeginningOfLine($content, $tempIndex - 1, 30);
						
						
						$lineIndex = 0;
						$searchIndex = $tempIndex;
						$lastIndex = $searchIndex;
						
						while(true)
						{
							$searchIndex = $this->getBeginningOfLine($content, $searchIndex - 1, 100000000);
							
							if($searchIndex != $lastIndex)
							{
								$lastIndex = $searchIndex;
								$lineIndex++;
							}
							else
								break;
						}
						
						
						$endIndex = $tempIndex;
						
						for($j = 0; $j < $matchStringLineCount; $j++)
							$endIndex = $this->getEndOfLine($content, $endIndex, 100);
						
						$tempString = substr($content, $tempIndex, $endIndex - $tempIndex);
						
						
						if($match == null)
						{
							$match = Array(
							"filename" => $dir2,
							"matches" => Array()
							);
						}
						
						$totalMatches++;
						array_push($match["matches"], Array("lineIndex" => $lineIndex, "snippet" => $tempString));
					}
					else
						break;
				}
				
				if($match != null)
					array_push($matches, $match);
			}
			
			if($topLevelOnly == true)
				break;
		}
		
		$result = Array(
			"filesSearched" => $filesSearched,
			"foldersSearched" => $foldersSearched,
			"fileMatchCount" => count($matches),
			"totalMatchCount" => $totalMatches,
			"matches" => $matches
		);
		
		echo json_encode($result, JSON_INVALID_UTF8_IGNORE);
	
		exit(0);
	}

	
	function getBeginningOfLine($content, $index, $max)
	{
		$count = 0;
		
		while(true)
		{
			$index--;
			
			if($count++ > $max)
				break;
			
			if($index < 0)
				break;
			
			if($content[$index] == "\n")
			{
				$index++;
				break;
			}
		}
		
		return max(0, $index);
	}

	function getEndOfLine($content, $index, $max)
	{
		$count = 0;
		$endIndex = strlen($content);
		
		while(true)
		{
			$index++;
		
			if($count++ > $max)
				break;
			
			if($index >= $endIndex)
				break;
			
			if($content[$index] == "\n")
				break;
		}
		
		return $index;
	}
}

$api = new API();

$api->init();

?>
<html>
	<head>
		<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<script type="text/javascript">
			

			$(document).ready(async function()
			{
				$(".directory-selector").on("dblclick", "option", async function()
				{
					$("#search-form .start-directory").val($("#search-form .start-directory").val() + "/" + $(this).val());

					await getDirectories();
				});


				

				$("#search-form .start-directory").val((await apiRequest({action: "get_start_directory"})).startDirectory);

				await getDirectories();

				
								
				$("#search-form").on("submit", async function(e)
				{
					disableForm(this, true);
					e.preventDefault();

					try
					{
						var extensions = $(".extensions").val().split(",").map(x => x.trim()).filter(x => x.length > 0);

						var postData = {
							"action":"do_search",
							"query":$(this).find(".query").val().trim(),
							"method":0,
							"topLevelOnly":false,
							"startDirectory":$(this).find(".start-directory").val().trim(),
							"extensions":extensions 
						};

						if(postData.query.length == 0)
						{
							setStateDetails("Enter a search term");
							return;
						}
					
						$(".result-container").children().remove();
						$(".search-details").hide();
						
						$(this).attr("disabled", "disabled");
						
						setStateDetails("Searching for: " + htmlEntities(postData.query));
						
						let responseData = await apiRequest(postData);

						$(".search-details .results-amount").html(responseData.totalMatchCount);
						$(".search-details .files-amount").html(responseData.filesSearched);
						$(".search-details .folders-amount").html(responseData.foldersSearched);
						$(".search-details").show();
						
						var content = ``;
						
						for(i = 0; i < responseData.matches.length; i++)
						{
							var result = responseData.matches[i];
						
							for(k = 0; k < result.matches.length; k++)
							{
								content += `<div class="item">
											<div class="filename">${result.filename}</div>`;
								var snippet = htmlEntities(result.matches[k].snippet);
								
								snippet = snippet.replace(new RegExp(htmlEntities(postData.query), "g"), function(x){
									return "<span class=\"query-match\">" + x + "</span>";
								}, "g");
								
								var lines = snippet.split("\n");
								
								content += `<div class="line-index">Line: ${result.matches[k].lineIndex}</div>`;
								content += `<div class="snippet">`;
								
								for(j = 0; j < lines.length; j++)
								{
									content += `<div class="line">${lines[j]}</div>`;
								}
								
								content += `</div>`;
								content += `</div>`;
							}
						}
						
						$(".result-container").append(content);
					}
					catch(err)
					{
						console.error(err);

						alert(err);
					}

					setStateDetails(" ");

					disableForm(this, false);
					
				});
			});

			function htmlEntities(str) 
			{
				return String(str).replaceAll(/&/g, '&amp;').replaceAll(/</g, '&lt;').replaceAll(/>/g, '&gt;').replaceAll(/"/g, '&quot;');
			}

			async function apiRequest(postData)
			{
				let res = await fetch(window.location.href, 
				{
					method: "POST",
					header: 
					{
						"Content-Type":"application/json"
					},
					body: JSON.stringify(postData)
				});

				var text = await res.text();

				try
				{
					$response = JSON.parse(text);
				}
				catch(err)
				{
					if(text.indexOf("allowed memory") != -1 || text.indexOf("Allowed memory size") != -1)
						throw new Error("PHP ran out of memory. Try a more narrow search term.");
					else if(text.indexOf("Maximum execution time") != -1)
						throw new Error("PHP script ran too long. Try a more narrow search term.");
					else if(text.trim().length == 0)
						throw new Error("No response from search script.  Try a more narrow search term.");
						
					throw new Error("Unable to parse response from search script. Probably a server sided error.");
				}

				return $response;
			}

			async function getDirectories()
			{
				let responseData = await apiRequest({
						action: "get_directories", 
						startPath: $("#search-form .start-directory").val().trim()
				});

				$(".directory-selector").children().remove();

				$("#search-form .start-directory").val(responseData.realPath);
				responseData.directories.forEach(x => $(".directory-selector").append(`<option>${x}</option>`));
			}

			function setStateDetails(message)
			{
				$("#state-details").fadeOut(200, function()
				{
					$(this).html(message).fadeIn(200);
				});
			}
			
			function disableForm(form, disable)
			{
				$(form).find("input,textarea,button").prop("disabled", disable);
			}

		</script>
		<style type="text/css">
			html,body{
			margin:0px;
			padding:0px;
			}

			#main{
			max-width:1200px;
			margin:0px auto;
			padding-top:100px;
			}

			#main > .title{
			font-size:2em;
			font-weight:bold;	
			}

			.result-container{
			margin-bottom:10px;
			}

			.result-container .item .filename{
			padding-left:10px;
			background-color:#b0b0b0;
			}

			.result-container .item .line-index{
			padding-left:10px;
			background-color:#b0b0b0;
			}

			.result-container .item .snippet{
			background-color:white;
			white-space: pre;
			margin-bottom:20px;
			border:5px solid #e4e4e4;
			overflow:hidden;
			}

			.result-container .item .snippet .query-match{
			color:red;
			font-weight:bold;
			}
			
			.result-container .item .snippet .line{
				min-height:1em;
			}

			.result-container .item .snippet .line:nth-child(even){	
			background-color:#e8e8ff;
			
			}

			.search-details{
			display:none;
			}

			#search-form input[type="text"], .directory-selector{
			width:500px;
			}

		</style>
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