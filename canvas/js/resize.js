$(document).ready( function() {
    var c = $('canvas');
    var container = $(c).parent();
    $(window).resize(respondCanvas);
    function respondCanvas() {
        c.attr('width', $(container).width());
        c.attr('height', $(container).height());
        context.lineWidth =radius*2;
    }
    respondCanvas();

    //Create a white background
    context.beginPath();
    context.fillStyle="white";
    context.rect(0,0,canvas.width,canvas.height);
    context.fill();
    context.beginPath();
    context.fillStyle="black";
});