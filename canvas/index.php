<?php
$html = <<<HTML
    <!DOCTYPE HTML>
    <html lang="en">
    <head>
        <title>Editeur</title>
        <link rel="stylesheet" href="css/style.css">
        <script src="js/jquery.min.js"></script>
        <script type="text/javascript" src="js/resize.js"></script>
        <script type="text/javascript" src="js/save.js"></script>
    </head>
    <body style="margin: 0">
        <div id="toolbar">
                <div id="decrad" class="radcontrol">-</div>
                <p>Taille <span id="radval"></span></p>
                <div id="incrad" class="radcontrol">+</div>
                <button id="clear" onclick="context.clearRect(0,0,context.canvas.width,context.canvas.height);">Clear</button>
                <a href="#" class="button" id="save" download="dessin.png">Save</a>
                <button id="erase" onclick="erase()"></button>
                <ul id = "color"><li id="deploy"></li><li></li></ul>
        </div>
        <canvas id='canvas'>
        </canvas>
        <script type="text/javascript" src="js/computer.js"></script>
        <script type="text/javascript" src="js/touch.js"></script>
        <script type="text/javascript" src="js/animation.js"></script>
        <script type="text/javascript" src="js/radius.js"></script>
        <script type="text/javascript" src="js/color.js"></script>
    </body>
    </html>
HTML;
echo $html;

