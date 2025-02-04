<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Atividade2 </title>
</head>
<body>
    <form method="post" action="">
        <label for="numeroI">Numero inicial:</label>
        <input type="number" id="numeroI" name="numeroI" required>
        <input type="submit" value="Enviar">
        
        <br>

        <label for="numeroF">Numero Final:</label>
        <input type="number" id="numeroF" name="numeroF" required>
        <input type="submit" value="Enviar">

        <br>
        
        <label for="incremento">Incremento: </label>
        <input type="number" id="incremento" name="incremento" required>
        <input type="submit" value="Enviar">
    </form>

    <?php

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $incremento = $_POST["incremento"];
    $numeroI = $_POST["numeroI"];
    $numeroF = $_POST["numeroF"];

    switch ($incremento) {
        case 1:
            for ($i=$numeroI; $i <= $numeroF; $i++) {
                echo "<h2> $i </h2>" ;
            }
            break;

        case 2:
            for ($i=$numeroI; $i <= $numeroF; $i+=2) {
                echo "<h2> $i </h2>" ;
            }
            break;
        case 5:
            for ($i=$numeroI; $i <= $numeroF; $i+=5) {
                echo "<h2> $i </h2>" ;
            }
            break;
        default:
            echo "Por favor, insira apenas 1, 2 ou 5.";
            break;
    }
}  
    ?>
</body>
</html>
