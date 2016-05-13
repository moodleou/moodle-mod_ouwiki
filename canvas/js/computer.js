var canvas = document.getElementById('canvas');
var context = canvas.getContext("2d");

var radius = 1;
var dragging = false;

canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

context.lineWidth =radius*2;

var putPoint = function (e){
    if(dragging) {
        context.lineTo(e.clientX,e.clientY);
        context.stroke();
        context.beginPath();
        context.arc(e.clientX, e.clientY, radius, 0, Math.PI * 2);
        context.fill();
        context.beginPath();
        context.moveTo(e.clientX,e.clientY);
    }
}

var engage = function (e) {
    dragging = true;
    putPoint(e);
}

var disengage = function () {
    dragging = false;
    context.beginPath();
}


canvas.addEventListener('mousedown', engage);
canvas.addEventListener('mousemove', putPoint);
canvas.addEventListener('mouseup', disengage);

$('canvas').mouseleave(function () {
    disengage();
});