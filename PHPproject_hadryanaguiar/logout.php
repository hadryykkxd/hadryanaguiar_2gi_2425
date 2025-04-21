<?php
// Iniciar sessão
session_start();
 
// Limpar todas as variáveis de sessão
$_SESSION = array();
 
// Destruir a sessão
session_destroy();
 
// Redirecionar para página de login
header("location: login.php");
exit;
?>