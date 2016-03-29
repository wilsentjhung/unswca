$(document).ready(function() {
	$("#report").click(function() {
    $("#report").addClass("active").siblings().removeClass("active");
		$("#advisor-content").hide();
		$("#overview-content").slideUp("slow");
    $("#report-content").slideDown("slow");
  });
});
