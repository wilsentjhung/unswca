$(document).ready(function() {
	$("#overview").click(function() {
    	$("#overview").addClass("active").siblings().removeClass("active");
    	$("#report-content").hide();
		$("#handbook-content").hide();
		$("#planner-content").hide();
    	$("#overview-content").show();
  	});
});
