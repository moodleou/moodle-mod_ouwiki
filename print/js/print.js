/*
 * Allows to print the contents of the page wanted
 * @param classPrint : Contents of the class to be printed
 * */
function printcontent() {

    var urlprint = document.location.href;

    var verif = urlprint.match(/(\/[eah])(.*).php/);
    if(verif != null){
        urlprint = urlprint.replace(verif[0],"/view.php");
    }

    var windows = window.open('');
    windows.document.write('<link rel = "stylesheet" type = "text/css" href = "./print/css/stylePrint.css"> ');
    windows.document.write('<button href = "#" onclick="window.print();return false">Imprimer</button>');

    var page = "";

    $.ajax({url : urlprint,
        success : function(e){
            page = e.match(/<div\s+class="ouwiki_content">([\S\s]*?)<\/div><div class="clearer">/gi);
            windows.document.write(page[0]);
        }
    });

}
