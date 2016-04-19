$(document).ready(function() {
	$("#handbook").click(function() {
    $("#handbook").addClass("active").siblings().removeClass("active");
    $("#overview-content").hide();
		$("#report-content").hide();
		$("#planner-content").hide();
		$("#handbook-content").show();

		$("#searchinput").keyup(function() {
      	var input = $(this).val();

  		if (input != "" && input.length > 3) {
  			$("#searchres").show();
				if ($("#coursebtn").is(":checked")) {
	  			$.post("inc/search.php", {input: input, type: "course"}, function(data) {
	  				$("#searchres").html(data);
	  			});
				} else if ($("#streambtn").is(":checked")) {
					$.post("inc/search.php", {input: input, type: "stream"}, function(data) {
	  				$("#searchres").html(data);
	  			});
				} else if ($("#programbtn").is(":checked")) {
					$.post("inc/search.php", {input: input, type: "program"}, function(data) {
	  				$("#searchres").html(data);
	  			});
				}
  		} else {
  			$("#searchres").hide();
  		}
    });
  });
});

$(document).ready(function() {
	$("#handbookbtns").click(function() {
		$("#searchinput").val("");
		$("#searchres").hide();
		$("#searchres").html("");
	});
});
