<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AULA 4 - TABUADA.php </title>
    <style>
        div {
            border: 4px solid blue;
            border-radius: 25px;
            box-shadow: 10px 10px 5px #888888;
            width: 110px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div>
        <?php
            $numero = $_GET["numero"];
            echo "<h3> TABUADA DO Nº $numero </h3>";
            for ($i = 1; $i <= 10; $i++) {
                echo "$i x $numero = " . ($i*$numero) . "<br>";
            }
        ?>
    </div>
</body>
</html>