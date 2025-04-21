<?php
// editar.php - Página para edição de registros do banco de dados

// Inclui o arquivo de configuração do banco de dados
require_once "config.php";

// Inicia a sessão para verificar autenticação
session_start();

// Verifica se o usuário está logado, caso contrário redireciona para login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Verifica se os parâmetros necessários (tabela e ID) foram passados via GET
if (!isset($_GET['table']) || !isset($_GET['id'])) {
    echo "Parâmetros inválidos.";
    exit;
}

// Armazena os parâmetros da tabela e ID a ser editado
$table = $_GET['table'];
$id = $_GET['id'];

/**
 * Obtém informações sobre as colunas de uma tabela
 * @param mysqli $conn Conexão com o banco
 * @param string $table Nome da tabela
 * @return array Lista de colunas com suas propriedades
 */
function getColumnInfo($conn, $table) {
    $columns = [];
    // Consulta todas as colunas da tabela com informações completas
    $query = "SHOW FULL COLUMNS FROM `$table`";
    $result = $conn->query($query);
    // Armazena cada coluna no array
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }
    return $columns;
}

/**
 * Identifica a chave primária de uma tabela
 * @param mysqli $conn Conexão com o banco
 * @param string $table Nome da tabela
 * @return string|null Nome da coluna chave primária ou null se não existir
 */
function getPrimaryKey($conn, $table) {
    // Consulta a chave primária da tabela
    $query = "SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'";
    $result = $conn->query($query);
    if ($row = $result->fetch_assoc()) {
        return $row['Column_name'];
    }
    return null;
}

// Obtém a chave primária e informações das colunas da tabela
$primaryKey = getPrimaryKey($conn, $table);
$columns = getColumnInfo($conn, $table);

// Consulta os dados do registro específico a ser editado
$query = "SELECT * FROM `$table` WHERE `$primaryKey` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id); // Previne SQL injection
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc(); // Armazena os dados do registro

// Processa o formulário quando submetido via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fields = []; // Armazena os campos para atualização
    $params = []; // Armazena os valores dos parâmetros
    $types = "";  // Armazena os tipos dos parâmetros

    // Prepara os dados para a query de atualização
    foreach ($columns as $column) {
        $colName = $column['Field'];
        // Ignora a chave primária (não deve ser atualizada)
        if ($colName === $primaryKey) continue;
        
        // Adiciona campo para atualização
        $fields[] = "`$colName` = ?";
        // Obtém valor do POST ou null se não existir
        $params[] = $_POST[$colName] ?? null;
        // Assume string como tipo padrão (s)
        $types .= "s";
    }

    // Monta a query de atualização
    $query = "UPDATE `$table` SET " . implode(", ", $fields) . " WHERE `$primaryKey` = ?";
    // Adiciona o ID como último parâmetro
    $params[] = $id;
    $types .= "s"; // Adiciona mais um tipo string para o ID

    // Prepara e executa a query de atualização
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        // Redireciona para a lista após sucesso
        header("Location: listar_dados.php");
        exit;
    } else {
        echo "Erro ao atualizar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Registro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset básico */
        * { 
            box-sizing: border-box; /* Modelo de caixa mais intuitivo */
            margin: 0; /* Remove margens padrão */
            padding: 0; /* Remove paddings padrão */
        }

        /* Estilos do corpo da página */
        body {
            font-family: 'Segoe UI', sans-serif; /* Fonte moderna */
            background-color: #eaf4fb; /* Fundo azul claro */
            padding: 20px; /* Espaçamento interno */
        }

        /* Container principal */
        .container {
            max-width: 600px; /* Largura máxima */
            margin: auto; /* Centralizado */
            background-color: white; /* Fundo branco */
            padding: 25px; /* Espaçamento interno */
            border-radius: 10px; /* Bordas arredondadas */
            box-shadow: 0 0 15px rgba(0,0,0,0.05); /* Sombra suave */
        }

        /* Título da página */
        h2 {
            text-align: center; /* Centralizado */
            color: #007BFF; /* Azul */
            margin-bottom: 20px; /* Espaçamento inferior */
        }

        /* Estilo das labels */
        label {
            display: block; /* Exibe como bloco */
            margin-top: 15px; /* Espaçamento superior */
            margin-bottom: 5px; /* Espaçamento inferior */
            color: #333; /* Cor do texto */
        }

        /* Estilos comuns para inputs */
        input[type="text"],
        input[type="number"],
        input[type="email"],
        textarea {
            width: 100%; /* Largura total */
            padding: 10px; /* Espaçamento interno */
            border-radius: 6px; /* Bordas arredondadas */
            border: 1px solid #ccc; /* Borda cinza */
            transition: border 0.3s; /* Transição suave */
        }

        /* Estilo para inputs em foco */
        input:focus, textarea:focus {
            border-color: #007BFF; /* Borda azul */
            outline: none; /* Remove contorno padrão */
        }

        /* Estilo do botão principal */
        .btn {
            display: block; /* Exibe como bloco */
            width: 100%; /* Largura total */
            background-color: #007BFF; /* Azul */
            color: white; /* Texto branco */
            padding: 12px; /* Espaçamento interno */
            margin-top: 25px; /* Espaçamento superior */
            border: none; /* Sem borda */
            border-radius: 6px; /* Bordas arredondadas */
            font-size: 1rem; /* Tamanho da fonte */
            cursor: pointer; /* Cursor de ponteiro */
        }

        /* Efeito hover no botão */
        .btn:hover {
            background-color: #0056b3; /* Azul mais escuro */
        }

        /* Estilo do link de voltar */
        .back-link {
            display: block; /* Exibe como bloco */
            text-align: center; /* Centralizado */
            margin-top: 20px; /* Espaçamento superior */
            color: #007BFF; /* Azul */
            text-decoration: none; /* Remove sublinhado */
        }

        /* Efeito hover no link */
        .back-link:hover {
            text-decoration: underline; /* Sublinhado ao passar o mouse */
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Título com nome da tabela -->
    <h2>Editar Registro - <?php echo htmlspecialchars($table); ?></h2>
    
    <!-- Formulário de edição -->
    <form method="post">
        <?php foreach ($columns as $column):
            $colName = $column['Field'];
            // Ignora a chave primária (não deve ser editada)
            if ($colName === $primaryKey) continue;
        ?>
            <!-- Label com comentário da coluna ou nome do campo -->
            <label for="<?php echo $colName; ?>"><?php echo $column['Comment'] ?: $colName; ?></label>
            
            <!-- Input para edição do valor -->
            <input type="text" 
                   name="<?php echo $colName; ?>" 
                   id="<?php echo $colName; ?>" 
                   value="<?php echo htmlspecialchars($data[$colName] ?? ''); ?>">
        <?php endforeach; ?>
        
        <!-- Botão de submissão -->
        <button type="submit" class="btn">Salvar Alterações</button>
        
        <!-- Link para voltar à listagem -->
        <a href="listar_dados.php" class="back-link">← Voltar</a>
    </form>
</div>
</body>
</html>