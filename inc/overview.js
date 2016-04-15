$(document).ready(function() {
	$("#overview").click(function() {
    $("#overview").addClass("active").siblings().removeClass("active");
    $("#report-content").hide();
		$("#handbook-content").hide();
		$("#advisor-content").hide();
    $("#overview-content").show();
  });
});
