<?php
// login.php - Página de autenticação do sistema

// Inicia a sessão para armazenar dados do usuário logado
session_start();

// Verifica se o usuário já está logado, redirecionando se verdadeiro
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: listar_dados.php");
    exit; // Termina a execução do script
}

// Inclui o arquivo de configuração com dados de conexão ao banco
require_once "config.php";

// Variável para armazenar mensagens de erro
$login_err = "";

// Processa o formulário quando submetido via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpa e armazena os dados do formulário
    $email = trim($_POST["username"]);
    $lastName = trim($_POST["password"]);

    // Valida se campos estão preenchidos
    if (empty($email) || empty($lastName)) {
        $login_err = "Por favor, preencha todos os campos.";
    } else {
        // Prepara a consulta SQL usando prepared statements para segurança
        $sql = "SELECT * FROM employees WHERE email = ? AND lastName = ?";

        // Prepara a declaração SQL
        if ($stmt = $conn->prepare($sql)) {
            // Associa os parâmetros (s = string)
            $stmt->bind_param("ss", $email, $lastName);

            // Executa a consulta preparada
            if ($stmt->execute()) {
                // Obtém o resultado da consulta
                $result = $stmt->get_result();

                // Verifica se encontrou exatamente 1 usuário
                if ($result->num_rows === 1) {
                    // Armazena os dados do usuário
                    $user = $result->fetch_assoc();
                    
                    // Define as variáveis de sessão
                    $_SESSION["loggedin"] = true;
                    $_SESSION["username"] = $user["firstName"] . " " . $user["lastName"];
                    $_SESSION["email"] = $user["email"];
                    
                    // Redireciona para a página principal
                    header("Location: listar_dados.php");
                    exit;
                } else {
                    // Mensagem de erro genérica para evitar enumeração de usuários
                    $login_err = "Email ou sobrenome incorretos.";
                }
            } else {
                $login_err = "Erro ao executar a consulta.";
            }

            // Fecha a declaração
            $stmt->close();
        }
    }

    // Fecha a conexão com o banco
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        /* Reset básico para todos os elementos */
        * {
            box-sizing: border-box; /* Modelo de caixa mais intuitivo */
            font-family: Arial, sans-serif; /* Fonte padrão */
        }

        /* Estilos do corpo da página */
        body {
            background: #f5f6fa; /* Cor de fundo suave */
            margin: 0; /* Remove margens padrão */
            padding: 0; /* Remove paddings padrão */
            height: 100vh; /* Altura total da viewport */
            display: flex; /* Layout flexível */
            align-items: center; /* Centraliza verticalmente */
            justify-content: center; /* Centraliza horizontalmente */
        }

        /* Container do formulário de login */
        .login-container {
            background: #ffffff; /* Fundo branco */
            padding: 40px; /* Espaçamento interno */
            border-radius: 10px; /* Bordas arredondadas */
            box-shadow: 0 8px 20px rgba(0,0,0,0.1); /* Sombra suave */
            width: 100%; /* Largura total */
            max-width: 400px; /* Largura máxima */
        }

        /* Estilo do título */
        h2 {
            text-align: center; /* Centraliza o texto */
            margin-bottom: 25px; /* Espaçamento inferior */
            color: #333; /* Cor do texto */
        }

        /* Estilo das labels */
        label {
            display: block; /* Exibe como bloco */
            margin-bottom: 6px; /* Espaçamento inferior */
            color: #333; /* Cor do texto */
            font-weight: bold; /* Texto em negrito */
        }

        /* Estilo dos campos de entrada */
        input[type="text"],
        input[type="password"] {
            width: 100%; /* Largura total */
            padding: 12px 10px; /* Espaçamento interno */
            margin-bottom: 20px; /* Espaçamento inferior */
            border: 1px solid #ccc; /* Borda cinza */
            border-radius: 8px; /* Bordas arredondadas */
            background-color: #f0f0f0; /* Fundo cinza claro */
            transition: 0.3s; /* Transição suave para efeitos */
        }

        /* Estilo dos campos quando em foco */
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff; /* Borda azul */
            background-color: #fff; /* Fundo branco */
            outline: none; /* Remove contorno padrão */
        }

        /* Estilo do botão de submit */
        input[type="submit"] {
            width: 100%; /* Largura total */
            padding: 12px; /* Espaçamento interno */
            background-color: #007bff; /* Cor de fundo azul */
            color: white; /* Texto branco */
            border: none; /* Sem borda */
            border-radius: 8px; /* Bordas arredondadas */
            font-size: 16px; /* Tamanho da fonte */
            cursor: pointer; /* Cursor de ponteiro */
            transition: background-color 0.3s; /* Transição suave */
        }

        /* Efeito hover no botão */
        input[type="submit"]:hover {
            background-color: #0056b3; /* Azul mais escuro */
        }

        /* Estilo para mensagens de erro */
        .error-message {
            margin-top: 15px; /* Espaçamento superior */
            color: #d9534f; /* Vermelho para erros */
            text-align: center; /* Texto centralizado */
        }
    </style>
</head>
<body>
    <!-- Container principal do formulário -->
    <div class="login-container">
        <h2>Login</h2>
        
        <!-- Formulário de login -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <!-- Campo de email -->
            <label for="username">Email:</label>
            <input type="text" name="username" id="username" required>
            
            <!-- Campo de senha (sobrenome) -->
            <label for="password">Sobrenome:</label>
            <input type="password" name="password" id="password" required>
            
            <!-- Botão de submit -->
            <input type="submit" value="Entrar">
        </form>

        <!-- Exibe mensagens de erro, se houver -->
        <?php 
        if (!empty($login_err)) {
            echo '<div class="error-message">' . $login_err . '</div>';
        }        
        ?>
    </div>
</body>
</html>