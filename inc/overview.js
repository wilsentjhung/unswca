$(document).ready(function() {
	$("#overview").click(function() {
    $("#overview").addClass("active").siblings().removeClass("active");
		$("#advisor-content").hide();
    $("#report-content").slideUp("slow");
    $("#overview-content").slideDown("slow");
  });
});
