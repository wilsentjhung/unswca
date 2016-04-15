$(document).ready(function() {
	$("#report").click(function() {
    $("#report").addClass("active").siblings().removeClass("active");
		$("#overview-content").hide();
		$("#handbook-content").hide();
		$("#advisor-content").hide();
    $("#report-content").show();
  });
});
