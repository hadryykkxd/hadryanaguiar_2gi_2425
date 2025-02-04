<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Atividade1 </title>
</head>
<body>
    <form method="post" action="">
        <label for="numero">Numero par:</label>
        <input type="number" id="numero" name="numero" required>
        <input type="submit" value="Enviar">
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $numero = $_POST["numero"];
        echo "<h1> OS NUMEROS PARES DE 1 A $numero </h1>";
        for ($i=1; $i<=$numero; $i++) {
            echo "<h2> $i </h2>" ;
        }
        }
    ?>
</body>
</html>