$(document).ready(function() {
	$("#planner").click(function() {
	    $("#planner").addClass("active").siblings().removeClass("active");
	    $("#overview-content").hide();
	    $("#report-content").hide();
	    $("#handbook-content").hide();
	    $("#planner-content").show();

		var plan = new Timetable();
        plan.setScope(0, 17);
        plan.addLocations(['Silent Disco', 'Nile', 'Len Room', 'Maas Room']);
        plan.addEvent('Frankadelic', 'Nile', new Date(2015,7,17,10,45), new Date(2015,7,17,12,30));
        var renderer = new Timetable.Renderer(plan);
        renderer.draw(".plan");

  	});
});
