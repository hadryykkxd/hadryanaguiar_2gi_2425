<?php
// Inicia a sessão para verificar autenticação do usuário
session_start();

// Verifica se o usuário está logado, caso contrário redireciona para login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit; // Termina a execução do script
}

// Inclui o arquivo de configuração do banco de dados
require_once "config.php";

/**
 * Obtém a lista de tabelas do banco de dados
 * @param mysqli $conn Conexão com o banco de dados
 * @return array Lista de nomes de tabelas
 */
function getTablesInfo($conn) {
    $tables = array();
    // Executa query para listar tabelas
    $result = $conn->query("SHOW TABLES");
    // Percorre resultados e armazena nomes das tabelas
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}

/**
 * Obtém o comentário de uma tabela específica
 * @param mysqli $conn Conexão com o banco de dados
 * @param string $table Nome da tabela
 * @return string Comentário da tabela
 */
function getTableComment($conn, $table) {
    // Consulta o comentário da tabela no information_schema
    $query = "SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE() AND table_name = '$table'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['table_comment'];
}

/**
 * Obtém os comentários das colunas de uma tabela
 * @param mysqli $conn Conexão com o banco de dados
 * @param string $table Nome da tabela
 * @return array Associativo com comentários das colunas
 */
function getColumnComments($conn, $table) {
    $comments = array();
    // Consulta os comentários das colunas no information_schema
    $query = "SELECT column_name, column_comment FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = '$table'";
    $result = $conn->query($query);
    // Armazena os comentários em um array associativo
    while ($row = $result->fetch_assoc()) {
        $comments[$row['column_name']] = $row['column_comment'];
    }
    return $comments;
}

// Obtém a lista de tabelas do banco de dados
$tables = getTablesInfo($conn);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Dados - Sistema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset básico para remover margens e paddings padrão */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; /* Modelo de caixa mais intuitivo */
        }

        /* Estilos gerais do corpo da página */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f2f8ff; /* Fundo azul claro */
            padding: 20px; /* Espaçamento interno */
        }

        /* Container principal */
        .container {
            max-width: 100%; /* Largura máxima */
            margin: 0 auto; /* Centralizado */
        }

        /* Cabeçalho da página */
        .header {
            background-color: #333; /* Cor de fundo escura */
            color: white; /* Texto branco */
            padding: 15px; /* Espaçamento interno */
            border-radius: 5px; /* Bordas arredondadas */
            display: flex; /* Layout flexível */
            justify-content: space-between; /* Espaço entre elementos */
            align-items: center; /* Alinhamento vertical */
            flex-wrap: wrap; /* Quebra de linha em telas pequenas */
        }

        /* Título no cabeçalho */
        .header h1 {
            font-size: 1.5rem; /* Tamanho da fonte */
        }

        /* Link de logout */
        .header a {
            background-color: #d9534f; /* Vermelho */
            padding: 8px 12px; /* Espaçamento interno */
            color: white; /* Texto branco */
            border-radius: 3px; /* Bordas arredondadas */
            text-decoration: none; /* Remove sublinhado */
        }

        /* Efeito hover no link de logout */
        .header a:hover {
            background-color: #c9302c; /* Vermelho mais escuro */
        }

        /* Área de ações (botões) */
        .actions {
            margin: 20px 0; /* Margem superior e inferior */
        }

        /* Botão de adicionar novo registro */
        .btn-add {
            background-color: #337ab7; /* Azul */
            color: white; /* Texto branco */
            padding: 10px 15px; /* Espaçamento interno */
            border-radius: 4px; /* Bordas arredondadas */
            text-decoration: none; /* Remove sublinhado */
        }

        /* Efeito hover no botão de adicionar */
        .btn-add:hover {
            background-color: #286090; /* Azul mais escuro */
        }

        /* Título das tabelas */
        .table-title {
            font-size: 1.2rem; /* Tamanho da fonte */
            background: #333; /* Fundo escuro */
            color: white; /* Texto branco */
            padding: 10px; /* Espaçamento interno */
            margin-top: 30px; /* Margem superior */
            border-radius: 5px; /* Bordas arredondadas */
        }

        /* Comentário/descrição da tabela */
        .table-comment {
            font-style: italic; /* Texto em itálico */
            color: #666; /* Cor cinza */
            margin: 5px 0 10px; /* Margens */
        }

        /* Container para tabelas com scroll horizontal */
        .table-scroll {
            overflow-x: auto; /* Scroll horizontal quando necessário */
            background: white; /* Fundo branco */
            border: 1px solid #ddd; /* Borda cinza */
            border-radius: 5px; /* Bordas arredondadas */
            margin-bottom: 30px; /* Margem inferior */
        }

        /* Estilo da tabela de dados */
        .fixed-table {
            width: 100%; /* Largura total */
            min-width: 800px; /* Largura mínima */
            border-collapse: collapse; /* Bordas colapsadas */
            table-layout: auto; /* Layout automático */
        }

        /* Células da tabela */
        .fixed-table th,
        .fixed-table td {
            padding: 12px 8px; /* Espaçamento interno */
            border: 1px solid #ddd; /* Borda cinza */
            text-align: center; /* Alinhamento centralizado */
        }

        /* Cabeçalho da tabela */
        .fixed-table th {
            background-color: #4a90e2; /* Azul */
            color: white; /* Texto branco */
            font-weight: bold; /* Negrito */
            white-space: nowrap; /* Sem quebra de linha */
            position: sticky; /* Fixo no scroll */
            top: 0; /* Alinhado ao topo */
            z-index: 1; /* Sobreposto */
        }

        /* Células numéricas */
        .fixed-table td.numeric {
            text-align: right; /* Alinhamento à direita */
            font-family: monospace; /* Fonte monoespaçada */
        }

        /* Linhas pares com fundo diferente */
        .fixed-table tr:nth-child(even) {
            background-color: #f9f9f9; /* Fundo cinza claro */
        }

        /* Efeito hover nas linhas */
        .fixed-table tr:hover {
            background-color: #eef6ff; /* Fundo azul claro */
        }

        /* Container para tooltip */
        .tooltip {
            position: relative; /* Posição relativa para o tooltip */
        }

        /* Estilo do tooltip (texto de ajuda) */
        .tooltip .tooltiptext {
            visibility: hidden; /* Inicialmente invisível */
            width: 220px; /* Largura fixa */
            background-color: #555; /* Fundo escuro */
            color: #fff; /* Texto branco */
            text-align: center; /* Alinhamento centralizado */
            padding: 5px; /* Espaçamento interno */
            border-radius: 6px; /* Bordas arredondadas */
            position: absolute; /* Posição absoluta */
            z-index: 1; /* Sobreposto */
            bottom: 125%; /* Posicionado acima */
            left: 50%; /* Centralizado horizontalmente */
            transform: translateX(-50%); /* Ajuste fino de posição */
            opacity: 0; /* Inicialmente transparente */
            transition: opacity 0.3s; /* Transição suave */
        }

        /* Mostra tooltip ao passar o mouse */
        .tooltip:hover .tooltiptext {
            visibility: visible; /* Torna visível */
            opacity: 1; /* Opaco */
        }

        /* Célula de ações (sem quebra de linha) */
        .actions-cell {
            white-space: nowrap; /* Sem quebra de linha */
        }

        /* Botões de ação */
        .btn-edit,
        .btn-delete {
            padding: 6px 10px; /* Espaçamento interno */
            color: white; /* Texto branco */
            text-decoration: none; /* Remove sublinhado */
            border-radius: 3px; /* Bordas arredondadas */
            font-size: 0.85rem; /* Tamanho da fonte */
        }

        /* Botão de editar */
        .btn-edit { 
            background-color: #5bc0de; /* Azul claro */
        }

        /* Botão de excluir */
        .btn-delete { 
            background-color: #d9534f; /* Vermelho */
        }

        /* Efeito hover nos botões */
        .btn-edit:hover,
        .btn-delete:hover {
            opacity: 0.85; /* Leve transparência */
        }

        /* Estilos para telas pequenas */
        @media (max-width: 768px) {
            .header { 
                flex-direction: column; /* Empilha elementos */
                align-items: flex-start; /* Alinha à esquerda */
            }
            .header h1 { 
                margin-bottom: 10px; /* Margem inferior */
            }
            .fixed-table th, 
            .fixed-table td { 
                font-size: 0.85rem; /* Reduz tamanho da fonte */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho da página -->
        <div class="header">
            <h1>Sistema de Gerenciamento de Dados</h1>
            <div>
                <!-- Mensagem de boas-vindas com nome do usuário -->
                <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <!-- Link para logout -->
                <a href="logout.php">Sair</a>
            </div>
        </div>

        <!-- Área de ações -->
        <div class="actions">
            <!-- Botão para adicionar novo registro -->
            <a href="cadastro.php" class="btn-add">Adicionar Novo Registro</a>
        </div>

        <!-- Loop através de cada tabela -->
        <?php foreach ($tables as $table): ?>
            <?php
                // Obtém metadados da tabela
                $tableComment = getTableComment($conn, $table);
                $columnComments = getColumnComments($conn, $table);
                
                // Consulta todos os dados da tabela
                $result = $conn->query("SELECT * FROM `$table`");
                $columns = [];

                // Obtém os nomes das colunas
                if ($result->num_rows > 0) {
                    foreach ($result->fetch_fields() as $field) {
                        $columns[] = $field->name;
                    }
                }

                // Identifica a chave primária da tabela
                $primary_key = '';
                $pk_result = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
                if ($pk_row = $pk_result->fetch_assoc()) {
                    $primary_key = $pk_row['Column_name'];
                }
            ?>

            <!-- Título da tabela -->
            <h2 class="table-title">Tabela: <?php echo $table; ?></h2>
            
            <!-- Exibe comentário/descrição da tabela se existir -->
            <?php if (!empty($tableComment)): ?>
                <p class="table-comment">Descrição: <?php echo $tableComment; ?></p>
            <?php endif; ?>

            <!-- Container da tabela com scroll -->
            <div class="table-scroll">
                <table class="fixed-table">
                    <thead>
                        <tr>
                            <!-- Cabeçalho das colunas -->
                            <?php foreach ($columns as $column): ?>
                                <th class="tooltip">
                                    <?php echo htmlspecialchars($column); ?>
                                    <!-- Tooltip com comentário da coluna -->
                                    <?php if (!empty($columnComments[$column])): ?>
                                        <span class="tooltiptext"><?php echo htmlspecialchars($columnComments[$column]); ?></span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <!-- Coluna de ações se existir chave primária -->
                            <?php if (!empty($primary_key)): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop através dos registros da tabela -->
                        <?php
                        $result->data_seek(0); // Reinicia o ponteiro do resultado
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <!-- Exibe cada célula do registro -->
                                <?php foreach ($columns as $column): ?>
                                    <td class="<?php echo is_numeric($row[$column]) ? 'numeric' : ''; ?>">
                                        <?php echo htmlspecialchars($row[$column]); ?>
                                    </td>
                                <?php endforeach; ?>
                                <!-- Célula de ações (editar/excluir) -->
                                <?php if (!empty($primary_key)): ?>
                                    <td class="actions-cell">
                                        <a href="editar.php?table=<?php echo $table; ?>&id=<?php echo $row[$primary_key]; ?>" class="btn-edit">Editar</a>
                                        <a href="excluir.php?table=<?php echo $table; ?>&id=<?php echo $row[$primary_key]; ?>" class="btn-delete">Excluir</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>