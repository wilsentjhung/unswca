$(document).ready(function() {
    $("#reslist").find("li").click(function() {
        $("#searchinput").val("");
        $("#reslist").hide();

        var index = this.id.substring(3, this.id.length);

        if (outputs[index] != "") {
            var outputParams = outputs[index].split(" - ");
            var code = outputParams[0];
            var name = outputParams[1];

            $.post("inc/info.php", {type: type, code: code}, function(data) {
                $("#info").html(data);
            });
        }
    });
});

$(document).ready(function() {
    $("#searchinput").keydown(function(e) {
        var index = 0;

        if (e.keyCode == 13) {

        } else if (e.keyCode == 38) {
            index--;
        } else if (e.keyCode == 40) {
            index++;
        }
    });
});
