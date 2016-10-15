function printallcontent(arrayTitle) {

    var url = document.location.href;

    var verif = url.match(/&page=(.*)/);
    if(verif != null){
        url = url.replace(verif[0], "");
    }

    verif = url.match(/(\/[eah])(.*).php/);
    if(verif != null){
        url = url.replace(verif[0],"/view.php");
    }

    var titleList = arrayTitle.split("splitword");
    var windows = window.open('');
    windows.document.write('<link rel = "stylesheet" type = "text/css" href = "./print/css/stylePrint.css"> ');
    windows.document.write('<button onclick="window.print();return false">Imprimer</button>');

    var page = "";

        for(var i = 0; i<titleList.length-1; i++) {
            var urlAjax = url + '&page=' + titleList[i];
            $.ajax({
                url: urlAjax,
                success: function (e) {
                    page = e.match(/<div\s+class="ouwiki_content">([\S\s]*?)<\/div><div class="clearer">/gi);
                    windows.document.write(page[0]);
                }
            });
        }

    setTimeout(function() {
        windows.document.close();
    }, 2000);
}



