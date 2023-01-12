

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
	let res = await fetch("api.php", 
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
