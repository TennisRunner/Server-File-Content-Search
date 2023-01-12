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