<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title> AULA 4 - Ciclo de Repetição - FOR3 </title>
        <style>
            div {
                width: 150px;
                font-family: Verdana;
                background-color: #C8F7C5;
                box-shadow: 10px 10px 5px #888888;
                padding: 10px;
            }
        </style>
    </head>
    <body>
        <div>
            <table border="1">
                <tr>
                    <td><b>Números</b></td>
                    <td><b>Cubos</b></td>
                </tr>
                <?php
                for($i=1;$i<=5;$i++) {
                    $q=$i*$i*$i;
                    echo "<tr><td>$i</td>";
                    echo "<td>$q</td></tr>";
                }
                ?>
            </table>
        </div>
    </body>
</html>