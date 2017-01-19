//Creation of colors
var colors = ['black', 'grey', 'red', 'orange', 'yellow', 'green', 'blue', 'indigo', 'violet'];

for(var i=0, n=colors.length; i<n; i++){
    var swatch = document.createElement('li');
    swatch.className = 'swatch';
    swatch.style.backgroundColor = colors[i];
    swatch.addEventListener('click', setSwatch);
    document.getElementById('color').appendChild(swatch);
}


function erase(){
    var swatch = document.getElementById('erase');
    swatch.addEventListener('click', setColor('white'));
}

function setColor(color){
    context.fillStyle = color;
    context.strokeStyle = color;
    var active = document.getElementsByClassName('active')[0];
    if(active){
        active.className = 'swatch';
    }
}

function setSwatch(e){
    var swatch = e.target;
    setColor(swatch.style.backgroundColor);
    swatch.className += ' active';

}

setSwatch({target: document.getElementsByClassName('swatch')[0]});
