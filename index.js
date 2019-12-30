











$(document).ready(function()
{
	console.log("index.js is loaded");
	
	
	
	function SetStateDetails(message)
	{
		$("#state-details").fadeOut(200, function()
		{
			$(this).html(message).fadeIn(200);
		});
	}
	
	
	
	$("#search-form").on("submit", function(e)
	{
		var query = $(this).find(".query").val().trim();
		
		if(query.length > 0)
		{
			$(".result-container").children().remove();
			$(".search-details").hide();
			
			var extentions = Array();
			var rawExtentions = $(".extentions").val().replace(" ", "");
			
			if(rawExtentions.length > 0)
				extentions = rawExtentions.split(",");
			
			// console.log(extentions); 
			
			
			var data = {
				"action":"search",
				"query":query,
				"method":0,
				"topLevelOnly":false,
				"startDirectory":"/home/o9vb08lsr925/public_html/",
				"extentions":extentions 
			};
			
			$(this).attr("disabled", "disabled");
			
			SetStateDetails("Searching for: " + query);
			
			$.post("api.php", "data=" + JSON.stringify(data), function(data)
			{
				if(typeof(data) == "object")
				{
					$(".search-details .results-amount").html(data.totalMatchCount);
					$(".search-details .files-amount").html(data.filesSearched);
					$(".search-details .folders-amount").html(data.foldersSearched);
					$(".search-details").show();
					
					var content = ``;
					
					for(i = 0; i < data.matches.length; i++)
					{
						var result = data.matches[i];
					
						for(k = 0; k < result.matches.length; k++)
						{
							content += `<div class="item">
										<div class="filename">${result.filename}</div>`;
							var snippet = result.matches[k].snippet;
							 
							snippet = snippet.replace(new RegExp(query, "g"), function(x){
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
					SetStateDetails(" ");
					
				}	
				else
				{
					SetStateDetails("Something went wrong with the search");
				}
			}, "JSON")
			.fail(function(e)
			{
				console.error("Error from API request:", e);
				SetStateDetails("Something went wrong with the search");
			})
			.always(function() 
			{
				$("#search-form").removeAttr("disabled");
			});
		}
		else
			SetStateDetails("Enter a search term");
		
		e.preventDefault();
	});
	
	
	$.post("api.php", "data=" + JSON.stringify({"action":"getDirectory"}), function(data)
	{
		$(".start-directory").val(data.startDirectory);
	}, "JSON");
	
	// setTimeout(function()
	// {
	// $("#search-form").trigger("submit");
	// }, 100);
	
});

