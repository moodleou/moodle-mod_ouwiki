/*
* Allows to print the contents of the page wanted
* @param classPrint : Contents of the class to be printed
* */
function printcontent(classPrint) {
    var print = document.getElementsByClassName(classPrint)[0].innerHTML;
    var windows = window.open('');
	windows.document.write('<link rel = "stylesheet" type = "text/css" href = "./print/css/stylePrint.css"> ');
	windows.document.write('<button href = "#" onclick="window.print();return false">Imprimer</button>');
    windows.document.write(print);
}
