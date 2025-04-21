<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
session_start();

// Verifica se o usuário está logado, caso contrário redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redireciona para login.php
    header("Location: login.php");
    // Termina a execução do script
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once "config.php";

// Array associativo que mapeia nomes de tabelas em inglês para português
$tabelasTraduzidas = [
    'employees' => 'Funcionários',
    'customers' => 'Clientes',
    'offices' => 'Escritórios',
    'orderdetails' => 'Detalhes do Pedido',
    'orders' => 'Pedidos',
    'payments' => 'Pagamentos',
    'products' => 'Produtos'
];

// Obtém o parâmetro 'tabela' da URL ou define como string vazia se não existir
$tabela = $_GET['tabela'] ?? '';

// Variável para armazenar mensagens de sucesso/erro
$mensagem = '';

/**
 * Função para traduzir nomes de campos do banco de dados para português
 * @param string $campo Nome do campo em inglês
 * @return string Nome do campo traduzido ou o original se não existir tradução
 */
function traduzirCampo($campo) {
    // Mapeamento completo de campos para tradução
    $mapa = [
        'employeeNumber' => 'Número do Funcionário',
        'lastName' => 'Sobrenome',
        'firstName' => 'Nome',
        'extension' => 'Ramal',
        'email' => 'Email',
        'officeCode' => 'Código do Escritório',
        'reportsTo' => 'Superior Imediato',
        'jobTitle' => 'Cargo',
        'customerNumber' => 'Número do Cliente',
        'customerName' => 'Nome do Cliente',
        'contactLastName' => 'Sobrenome do Contato',
        'contactFirstName' => 'Nome do Contato',
        'phone' => 'Telefone',
        'addressLine1' => 'Endereço 1',
        'addressLine2' => 'Endereço 2',
        'city' => 'Cidade',
        'state' => 'Estado',
        'postalCode' => 'CEP',
        'country' => 'País',
        'salesRepEmployeeNumber' => 'Funcionário Representante',
        'creditLimit' => 'Limite de Crédito',
        'territory' => 'Território',
        'orderNumber' => 'Número do Pedido',
        'productCode' => 'Código do Produto',
        'quantityOrdered' => 'Quantidade',
        'priceEach' => 'Preço Unitário',
        'orderLineNumber' => 'Número da Linha',
        'orderDate' => 'Data do Pedido',
        'requiredDate' => 'Data Requerida',
        'shippedDate' => 'Data de Envio',
        'status' => 'Status',
        'comments' => 'Comentários',
        'checkNumber' => 'Número do Cheque',
        'paymentDate' => 'Data do Pagamento',
        'amount' => 'Valor',
        'productName' => 'Nome do Produto',
        'productLine' => 'Linha do Produto',
        'productScale' => 'Escala',
        'productVendor' => 'Fornecedor',
        'productDescription' => 'Descrição',
        'quantityInStock' => 'Estoque',
        'buyPrice' => 'Preço de Compra',
        'MSRP' => 'Preço Sugerido'
    ];
    // Retorna a tradução ou o próprio campo se não existir no mapa
    return $mapa[$campo] ?? $campo;
}

// Verifica se o formulário foi submetido (método POST) e se uma tabela foi selecionada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tabela) {
    // Array para armazenar mensagens de erro
    $erros = [];

    // Valida cada campo do formulário
    foreach ($_POST as $campo => $valor) {
        // Consulta a estrutura da coluna no banco de dados
        $result = $conn->query("SHOW COLUMNS FROM `$tabela` WHERE Field = '$campo'");
        $coluna = $result->fetch_assoc();
        $tipoColuna = $coluna['Type'];

        // Valida campos numéricos (int, float, decimal)
        if (strpos($tipoColuna, 'int') !== false || strpos($tipoColuna, 'float') !== false || strpos($tipoColuna, 'decimal') !== false) {
            if (!is_numeric($valor)) {
                $erros[] = "O campo '$campo' deve ser um número.";
            }
        } 
        // Valida campos de data
        elseif (strpos($tipoColuna, 'date') !== false) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                $erros[] = "O campo '$campo' deve ser uma data no formato AAAA-MM-DD.";
            }
        }
    }

    // Se houver erros, junta todos em uma única mensagem
    if (!empty($erros)) {
        $mensagem = implode("<br>", $erros);
    } else {
        // Prepara a query SQL para inserção
        $campos = array_keys($_POST); // Pega os nomes dos campos
        // Cria placeholders (?, ?, ?) para a query preparada
        $placeholders = implode(',', array_fill(0, count($campos), '?'));
        // Monta a query INSERT
        $sql = "INSERT INTO $tabela (" . implode(',', $campos) . ") VALUES ($placeholders)";

        // Prepara a declaração SQL
        if ($stmt = $conn->prepare($sql)) {
            // Define que todos os parâmetros serão tratados como strings (s)
            $tipos = str_repeat('s', count($campos));
            // Cria um array com os valores dos campos
            $valores = array_map(fn($campo) => $_POST[$campo], $campos);
            // Associa os parâmetros à declaração
            $stmt->bind_param($tipos, ...$valores);

            // Executa a inserção
            if ($stmt->execute()) {
                $mensagem = "Registro adicionado com sucesso!";
            } else {
                $mensagem = "Erro ao inserir: " . $stmt->error;
            }
            // Fecha a declaração
            $stmt->close();
        } else {
            $mensagem = "Erro na preparação da consulta: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Registro</title>
    <style>
        /* Reset básico e estilos globais */
        * {
            box-sizing: border-box; /* Modelo de caixa mais intuitivo */
            font-family: Arial, sans-serif; /* Fonte padrão */
        }
        
        /* Estilo do corpo da página */
        body {
            margin: 0; /* Remove margens padrão */
            background: #f4f9ff; /* Cor de fundo suave */
            padding: 40px; /* Espaçamento interno */
        }
        
        /* Container principal */
        .container {
            background: #fff; /* Fundo branco */
            max-width: 700px; /* Largura máxima */
            margin: auto; /* Centraliza na página */
            padding: 30px; /* Espaçamento interno */
            border-radius: 12px; /* Bordas arredondadas */
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); /* Sombra suave */
        }
        
        /* Estilo do título */
        h2 {
            text-align: center; /* Centraliza o texto */
            color: #007bff; /* Cor azul */
        }
        
        /* Estilo do formulário */
        form {
            display: flex; /* Layout flexível */
            flex-direction: column; /* Itens em coluna */
            gap: 15px; /* Espaçamento entre itens */
        }
        
        /* Estilo das labels */
        label {
            font-weight: bold; /* Texto em negrito */
        }
        
        /* Estilo dos inputs */
        input, select, textarea {
            padding: 10px; /* Espaçamento interno */
            border: 1px solid #ccc; /* Borda cinza */
            border-radius: 8px; /* Bordas arredondadas */
        }
        
        /* Estilo do botão principal */
        button {
            background: #007bff; /* Cor de fundo azul */
            color: white; /* Texto branco */
            padding: 12px; /* Espaçamento interno */
            border: none; /* Sem borda */
            border-radius: 8px; /* Bordas arredondadas */
            cursor: pointer; /* Cursor de ponteiro */
            font-size: 16px; /* Tamanho da fonte */
        }
        
        /* Efeito hover no botão */
        button:hover {
            background: #0056b3; /* Cor mais escura ao passar o mouse */
        }
        
        /* Estilo para mensagens de feedback */
        .mensagem {
            text-align: center; /* Texto centralizado */
            margin-top: 15px; /* Espaçamento superior */
            color: green; /* Cor verde */
            font-weight: bold; /* Texto em negrito */
        }
        
        /* Estilo do seletor de tabela */
        .tabela-select {
            margin-bottom: 20px; /* Espaçamento inferior */
        }
        
        /* Estilo do botão de voltar */
        .btn-voltar {
            display: inline-block; /* Exibição em linha */
            background: #6c757d; /* Cor de fundo cinza */
            color: white; /* Texto branco */
            padding: 10px 20px; /* Espaçamento interno */
            border-radius: 8px; /* Bordas arredondadas */
            text-decoration: none; /* Sem sublinhado */
            font-size: 16px; /* Tamanho da fonte */
            margin-bottom: 20px; /* Espaçamento inferior */
            transition: background 0.2s; /* Transição suave */
            text-align: center; /* Texto centralizado */
        }
        
        /* Efeito hover no botão de voltar */
        .btn-voltar:hover {
            background: #495057; /* Cor mais escura ao passar o mouse */
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Título da página -->
    <h2>Cadastrar Novo Registro</h2>
    
    <!-- Link/Botão para voltar à página inicial -->
    <a href="listar_dados.php" class="btn-voltar">Voltar para a Tela Inicial</a>
    
    <!-- Formulário de seleção de tabela -->
    <form method="get" class="tabela-select">
        <label for="tabela">Escolha a Tabela:</label>
        <select name="tabela" id="tabela" onchange="this.form.submit()">
            <option value="">-- Selecione --</option>
            <?php foreach ($tabelasTraduzidas as $nome => $traduzido): ?>
                <option value="<?= $nome ?>" <?= ($tabela === $nome) ? 'selected' : '' ?>><?= $traduzido ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($tabela): ?>
        <!-- Formulário de cadastro (renderizado dinamicamente) -->
        <form method="post">
            <?php
            // Obtém informações sobre as colunas da tabela selecionada
            $result = $conn->query("SHOW COLUMNS FROM $tabela");
            
            // Para cada coluna na tabela...
            while ($coluna = $result->fetch_assoc()):
                $nomeCampo = $coluna['Field']; // Nome do campo
                // Verifica se o campo é obrigatório (NOT NULL)
                $obrigatorio = strpos($coluna['Null'], 'NO') !== false ? 'required' : '';
                $tipoColuna = $coluna['Type']; // Tipo do campo no banco de dados

                // Define o tipo de input baseado no tipo da coluna
                $tipoInput = 'text'; // Padrão
                if (strpos($tipoColuna, 'int') !== false || strpos($tipoColuna, 'float') !== false || strpos($tipoColuna, 'decimal') !== false) {
                    $tipoInput = 'number'; // Campos numéricos
                } elseif (strpos($tipoColuna, 'date') !== false) {
                    $tipoInput = 'date'; // Campos de data
                }

                // Casos especiais para campos que são chaves estrangeiras
                // 1. Clientes -> Representante de vendas (funcionário)
                if ($tabela === 'customers' && $nomeCampo === 'salesRepEmployeeNumber') {
                    $employees = $conn->query("SELECT employeeNumber, firstName, lastName FROM employees");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($employee = $employees->fetch_assoc()) {
                        echo "<option value='{$employee['employeeNumber']}'>{$employee['employeeNumber']} - {$employee['firstName']} {$employee['lastName']}</option>";
                    }
                    echo "</select>";
                } 
                // 2. Detalhes do pedido -> Código do produto
                elseif ($tabela === 'orderdetails' && $nomeCampo === 'productCode') {
                    $products = $conn->query("SELECT productCode, productName FROM products");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($product = $products->fetch_assoc()) {
                        echo "<option value='{$product['productCode']}'>{$product['productCode']} - {$product['productName']}</option>";
                    }
                    echo "</select>";
                } 
                // 3. Pedidos -> Número do cliente
                elseif ($tabela === 'orders' && $nomeCampo === 'customerNumber') {
                    $customers = $conn->query("SELECT customerNumber, customerName FROM customers");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($customer = $customers->fetch_assoc()) {
                        echo "<option value='{$customer['customerNumber']}'>{$customer['customerNumber']} - {$customer['customerName']}</option>";
                    }
                    echo "</select>";
                } 
                // 4. Pagamentos -> Número do cliente
                elseif ($tabela === 'payments' && $nomeCampo === 'customerNumber') {
                    $customers = $conn->query("SELECT customerNumber, customerName FROM customers");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($customer = $customers->fetch_assoc()) {
                        echo "<option value='{$customer['customerNumber']}'>{$customer['customerNumber']} - {$customer['customerName']}</option>";
                    }
                    echo "</select>";
                } 
                // 5. Produtos -> Linha de produto
                elseif ($tabela === 'products' && $nomeCampo === 'productLine') {
                    $productlines = $conn->query("SELECT productLine FROM productlines");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($productline = $productlines->fetch_assoc()) {
                        echo "<option value='{$productline['productLine']}'>{$productline['productLine']}</option>";
                    }
                    echo "</select>";
                } 
                // 6. Funcionários -> Código do escritório
                elseif ($tabela === 'employees' && $nomeCampo === 'officeCode') {
                    $offices = $conn->query("SELECT officeCode, city FROM offices");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($office = $offices->fetch_assoc()) {
                        echo "<option value='{$office['officeCode']}'>Código {$office['officeCode']} - {$office['city']}</option>";
                    }
                    echo "</select>";
                }
                // 7. Funcionários -> Superior imediato (auto-relacionamento)
                elseif ($tabela === 'employees' && $nomeCampo === 'reportsTo') {
                    $emps = $conn->query("SELECT employeeNumber, firstName, lastName FROM employees");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo'>";
                    echo "<option value=''>-- Nenhum --</option>";
                    while ($emp = $emps->fetch_assoc()) {
                        echo "<option value='{$emp['employeeNumber']}'>{$emp['employeeNumber']} - {$emp['firstName']} {$emp['lastName']}</option>";
                    }
                    echo "</select>";
                } 
                // 8. Detalhes do pedido -> Número do pedido
                elseif ($tabela === 'orderdetails' && $nomeCampo === 'orderNumber') {
                    $orders = $conn->query("SELECT orderNumber FROM orders");
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<select name='$nomeCampo' id='$nomeCampo' $obrigatorio>";
                    while ($order = $orders->fetch_assoc()) {
                        echo "<option value='{$order['orderNumber']}'>{$order['orderNumber']}</option>";
                    }
                    echo "</select>";
                }
                // Para campos normais (não especiais)
                else {
                    echo "<label for='$nomeCampo'>" . traduzirCampo($nomeCampo) . ":</label>";
                    echo "<input type='$tipoInput' name='$nomeCampo' id='$nomeCampo' $obrigatorio data-type='$tipoInput'>";
                }
            endwhile;
            ?>
            <!-- Botão de submissão do formulário -->
            <button type="submit">Cadastrar</button>
        </form>
    <?php endif; ?>

    <!-- Exibe mensagens de feedback (sucesso/erro) -->
    <?php if ($mensagem): ?>
        <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
</div>

<!-- Validação do formulário no lado do cliente -->
<script>
    // Adiciona um listener para o evento de submit do formulário
    document.querySelector('form').addEventListener('submit', function(event) {
        // Seleciona todos os inputs que possuem o atributo data-type
        let inputs = document.querySelectorAll('input[data-type]');
        
        // Verifica cada input
        for (let i = 0; i < inputs.length; i++) {
            let input = inputs[i];
            let dataType = input.getAttribute('data-type');

            // Validação para campos numéricos
            if (dataType === 'number') {
                if (isNaN(input.value)) {
                    alert('O campo "' + input.name + '" deve ser um número.');
                    event.preventDefault(); // Impede o envio do formulário
                    return;
                }
            } 
            // Validação para campos de data
            else if (dataType === 'date') {
                if (!/^\d{4}-\d{2}-\d{2}$/.test(input.value)) {
                    alert('O campo "' + input.name + '" deve ser uma data no formato AAAA-MM-DD.');
                    event.preventDefault(); // Impede o envio do formulário
                    return;
                }
            }
        }
    });
</script>
</body>
</html>