$(document).ready(function() {
	$("#advisor").click(function() {
    $("#advisor").addClass("active").siblings().removeClass("active");
    $("#overview-content").hide();
    $("#report-content").hide();
    $("#advisor-content").show();

    $("#searchinput").keyup(function() {
      var input = $(this).val();

  		if (input != "" && input.length > 3) {
  			$("#searchres").show();
  			$.post("inc/search.php", {input: input}, function(data) {
  				$("#searchres").html(data);
  			});
  		} else {
  			$("#searchres").hide();
  		}
    });
  });
});
