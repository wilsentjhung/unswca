$(document).ready(function() {
	$("#advisor").click(function() {
    $("#advisor").addClass("active").siblings().removeClass("active");
    $("#overview-content").hide();
    $("#report-content").hide();
    $("#handbook-content").hide();
    $("#advisor-content").show();
  });
});
