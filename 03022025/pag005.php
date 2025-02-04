<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AULA 4 - CICLO WHILE </title>
    <style>
        div {
            width: 550px;
            font-family: Verdana;
            background-color: #FDE3A7;
            box-shadow: 10px 10px 5px #888888;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div>
        <?php
            $c = 0;
            do {
                echo "$c       ";
                $c+=2;
            } while ($c <=40);
            ?>
    </div>
    
</body>
</html>