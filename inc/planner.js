$(document).ready(function() {
	$("#planner").click(function() {
	    $("#planner").addClass("active").siblings().removeClass("active");
	    $("#overview-content").hide();
	    $("#report-content").hide();
	    $("#handbook-content").hide();
	    $("#planner-content").show();
  	});
});
