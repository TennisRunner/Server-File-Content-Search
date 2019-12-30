<?php







// $data = Array(
// "action" => "search",
// "query" => "get_current_user",
// "method" => 0,
// "topLevelOnly" => false,
// "startDirectory" => "/home/o9vb08lsr925/public_html/wp-content",
// "extentions" => Array(
// )
// );


// $_POST["data"] = json_encode($data);

function dump($input)
{
	echo "<pre>";
	
	echo var_dump($input);
	
	echo "</pre>";
}


// dump(getcwd());



function GetBeginningOfLine($content, $index, $max)
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

function GetEndOfLine($content, $index, $max)
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




if(isset($_POST["data"]) == true)
{
	$data = json_decode($_POST["data"]);
	
	if($data->action == "getDirectory")
	{
		echo json_encode(Array("startDirectory" => getcwd() . "/"));
	}
	else if($data->action == "search")
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
		$extentions = $data->extentions;
		
		$directories = Array($startDirectory);
		
		
		for($i = 0; $i < count($directories); $i++)
		{
			$dir = $directories[$i];
			
			 // dump("Dir is: " . $dir);
			
			$resultDirectories = glob($dir . "*", GLOB_NOSORT | GLOB_MARK );
			
			for($k = 0; $k < count($resultDirectories); $k++)
			{
				$foldersSearched++;
				$dir2 = $resultDirectories[$k];
				
				// If its a directory, add it to the list
				if($dir2[strlen($dir2) - 1] == '/')
				{
					array_push($directories, $dir2);
				}
				else
				{					
					$parts = explode(".", $dir2);
					
					$fileExtention = strtolower($parts[count($parts) - 1]);
					
					 // dump("File extention is: " . $fileExtention);
					
					if(in_array($fileExtention, $extentions) !== false || count($extentions) == 0)
					{
						$filesSearched++;
						// dump("Matched file: " . $dir2);
						
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
									$tempIndex = GetBeginningOfLine($content, $tempIndex - 1, 30);
								
								
								$lineIndex = 0;
								$searchIndex = $tempIndex;
								$lastIndex = $searchIndex;
								
								while(true)
								{
									$searchIndex = GetBeginningOfLine($content, $searchIndex - 1, 100000000);
									
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
									$endIndex = GetEndOfLine($content, $endIndex, 100);
								
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
						{
							array_push($matches, $match);
						}
					}
				}
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
		
		
		// dump($result);
		
		echo json_encode($result);
	}
}






























?>