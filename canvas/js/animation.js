//Animation of the color palette
var bool = false;

/*
* Unwind colors by pressing the button
* */
function menu() {
    if (!bool){
        var list = $('li');
        var x = 55;
        for (var i = 1; i<list.length; i++){
            list.eq(i).css('transform', 'translateY(80px) translateX(' + x + 'px)');
            x += -55;
        }
        bool = true;
    }else{
        var list = $('li');
        for (var i = 1; i<list.length; i++){
            list.eq(i).css('transform', 'translateY(0) translateX(0)');
        }
        bool = false;
    }

}

$(function(){
    if(!bool){
        $('#deploy').click(menu);
    }else{
        $('#deploy').click(menu);
    }

});

