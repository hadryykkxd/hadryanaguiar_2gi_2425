<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title> AULA 4 - Ciclo de Repetição - FOR2 </title>
        <style>
            h3 {
                background-color: yellow;
                border: 2px solid red;
                width: 25px;
                padding: 7px;
            }
        </style>
    </head>
    <body>
        <?php
        for ($i=0; $i<=50; $i+=5) {
            echo "<h3> $i </h3>" ;
        }
        ?>
    </body>
</html>