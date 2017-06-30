$(document).ready( function() {
    var canvas = document.getElementById('canvas');
    var button = document.getElementById('save');
    button.addEventListener('click', function () {
        var dataURL = canvas.toDataURL('image/png');
        console.log(dataURL);
        button.href = dataURL;
    });
});
