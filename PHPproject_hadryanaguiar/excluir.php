<?php
require_once "config.php"; // Conecta ao banco de dados com as configurações do arquivo config.php
?>

<!DOCTYPE html>
<html lang="pt-BR"> <!-- Define o idioma da página como português do Brasil -->
<head>
    <meta charset="UTF-8"> <!-- Define a codificação de caracteres -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsividade -->
    <title>Excluir Registro</title> <!-- Título da aba -->
    <link rel="stylesheet" href="styles.css"> <!-- Link para o CSS externo -->

    <style>
        /* Estilo básico da página */
        body {
            font-family: Arial, sans-serif; /* Fonte padrão */
            background-color: #f2f9ff; /* Azul claro de fundo */
            color: #333; /* Cor do texto */
            padding: 20px; /* Espaçamento interno */
        }

        h3 {
            color: #0044cc; /* Cor do título azul escuro */
        }

        .message-box {
            background-color: #e6f7ff; /* Fundo da caixa de mensagem */
            border: 1px solid #b3d9ff; /* Borda azul clara */
            border-radius: 8px; /* Arredondamento */
            padding: 20px; /* Espaçamento interno */
            margin-bottom: 20px; /* Espaçamento inferior */
        }

        .message-box ul {
            padding-left: 20px; /* Espaço à esquerda da lista */
        }

        .message-box ul li {
            margin: 5px 0; /* Espaçamento entre itens da lista */
        }

        a {
            background-color: #0044cc; /* Fundo do botão */
            color: #fff; /* Cor do texto do botão */
            padding: 10px 20px; /* Espaçamento interno do botão */
            text-decoration: none; /* Remove sublinhado */
            border-radius: 5px; /* Bordas arredondadas */
            margin: 10px 0; /* Margem vertical */
            display: inline-block; /* Exibição como bloco inline */
        }

        a:hover {
            background-color: #0033a1; /* Cor de fundo ao passar o mouse */
        }

        .button-container {
            margin-top: 20px; /* Espaçamento superior dos botões */
        }

        .button-container a {
            margin-right: 10px; /* Espaçamento entre botões */
        }
    </style>
</head>
<body>

<?php
if (isset($_GET['table'], $_GET['id'])) { // Verifica se os parâmetros 'table' e 'id' estão presentes na URL
    $table = $_GET['table']; // Tabela que o usuário quer excluir
    $id = $_GET['id']; // ID do registro a ser excluído

    // Consulta para descobrir a chave primária da tabela
    $query = "SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'";
    $result = $conn->query($query); // Executa a query
    $primaryKey = $result->fetch_assoc()['Column_name']; // Pega o nome da coluna da chave primária

    $dependencias = []; // Array que guardará as tabelas dependentes

    // Busca as tabelas que possuem chaves estrangeiras apontando para a tabela atual
    $queryFK = "SELECT TABLE_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE REFERENCED_TABLE_NAME = '$table' AND REFERENCED_COLUMN_NAME = '$primaryKey'";
    $resultFK = $conn->query($queryFK); // Executa a consulta das chaves estrangeiras

    while ($fk = $resultFK->fetch_assoc()) { // Itera sobre cada dependência encontrada
        $depTable = $fk['TABLE_NAME']; // Nome da tabela dependente
        $depColumn = $fk['COLUMN_NAME']; // Nome da coluna que referencia a chave primária

        // Prepara uma query para contar quantos registros dependentes existem
        $depQuery = "SELECT COUNT(*) as count FROM `$depTable` WHERE `$depColumn` = ?";
        $stmtDep = $conn->prepare($depQuery); // Prepara a consulta
        $stmtDep->bind_param("s", $id); // Substitui o parâmetro com o ID
        $stmtDep->execute(); // Executa a query
        $stmtDep->bind_result($count); // Pega o resultado
        $stmtDep->fetch(); // Lê o resultado

        if ($count > 0) { // Se houver dependências
            $dependencias[] = [ // Adiciona ao array de dependências
                'table' => $depTable,
                'column' => $depColumn,
                'count' => $count
            ];
        }

        $stmtDep->close(); // Fecha o statement
    }

    if (count($dependencias) > 0) { // Se encontrou dependências
        echo "<div class='message-box'>";
        echo "<h3>Atenção!</h3>";
        echo "<p>O item que você está tentando excluir possui dependências nas seguintes tabelas:</p>";
        echo "<ul>";
        foreach ($dependencias as $dep) { // Lista as dependências
            echo "<li>Tabela <strong>{$dep['table']}</strong> - ({$dep['count']} dependência(s))</li>";
        }
        echo "</ul>";
        echo "<p>Você deseja excluir tudo de uma vez ou apenas o item principal?</p>";
        echo "</div>";

        // Botões para as opções de exclusão ou cancelamento
        echo "<div class='button-container'>";
        echo "<a href='excluir.php?table=$table&id=$id&action=delete_all'>Excluir tudo</a>";
        echo "<a href='excluir.php?table=$table&id=$id&action=delete_item'>Excluir apenas o item</a>";
        echo "<a href='listar_dados.php'>Cancelar</a>"; // Botão de voltar
        echo "</div>";

        exit; // Encerra o script aqui para evitar a execução da exclusão logo abaixo
    } elseif (!isset($_GET['action'])) { // Se não houver dependência e nenhuma ação definida
        echo "<div class='message-box'>";
        echo "<h3>Confirmar exclusão</h3>";
        echo "<p>Este item não possui dependências. Você tem certeza que deseja excluir?</p>";
        echo "</div>";

        echo "<div class='button-container'>";
        echo "<a href='excluir.php?table=$table&id=$id&action=delete_item'>Confirmar exclusão</a>";
        echo "<a href='listar_dados.php'>Cancelar</a>"; // Botão de voltar
        echo "</div>";
        exit; // Interrompe a execução para aguardar a ação do usuário
    }

    // Se a ação for "delete_all", exclui os registros dependentes e depois o principal
    if ($_GET['action'] === 'delete_all') {
        foreach ($dependencias as $dep) {
            $stmtDelDep = $conn->prepare("DELETE FROM `{$dep['table']}` WHERE `{$dep['column']}` = ?");
            $stmtDelDep->bind_param("s", $id);
            $stmtDelDep->execute();
            $stmtDelDep->close();
        }
    }

    // Exclui apenas o item principal, independente da ação
    if ($_GET['action'] === 'delete_item' || $_GET['action'] === 'delete_all') {
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$primaryKey` = ?");
        $stmt->bind_param("s", $id);

        if ($stmt->execute()) {
            header("Location: listar_dados.php"); // Redireciona de volta após exclusão
            exit;
        } else {
            // Exibe erro se não conseguir excluir
            echo "<div class='message-box'>";
            echo "<h3>Erro</h3>";
            echo "<p>Erro ao excluir: " . $stmt->error . "</p>";
            echo "<a href='listar_dados.php'>Voltar</a>";
            echo "</div>";
        }
    }
} else {
    echo "<p>Parâmetros inválidos.</p>"; // Se não recebeu os parâmetros esperados
}
?>

</body>
</html>
