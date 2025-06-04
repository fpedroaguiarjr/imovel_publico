<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processo = $_POST['processo'];
    $ano = $_POST['ano'];
    $descricao = $_POST['descricao'];
    $endereco = $_POST['endereco'];
    $status = $_POST['status'];

    try {
        $sql = "INSERT INTO processos (processo, ano, descricao, endereco, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$processo, $ano, $descricao, $endereco, $status]);
        
        $success = true;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imóvel Público - Incluir Processo</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo_ptangua.png" alt="Logo">
            <span>Imóvel Público - Incluir Processo</span>
        </div>
        <nav>
            <button onclick="location.href='index.php'">RETORNAR</button>
        </nav>
    </header>

    <main class="form-container">
        <?php if (isset($success)): ?>
            <div class="alert success">Dados salvos com sucesso!</div>
        <?php elseif (isset($error)): ?>
            <div class="alert error">ATENÇÃO! DADOS NÃO SALVOS: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="processo">Número do Processo:</label>
                <input type="number" id="processo" name="processo" required>
            </div>
            
            <div class="form-group">
                <label for="ano">Ano:</label>
                <input type="text" id="ano" name="ano" required maxlength="10">
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required maxlength="200"></textarea>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" required maxlength="250">
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="">Selecione...</option>
                    <option value="arquivados">Arquivados</option>
                    <option value="parado">Parado</option>
                    <option value="andamento">Andamento</option>
                    <option value="registradoT">RegistradoT</option>
                    <option value="registradoF">RegistradoF</option>
                </select>
            </div>
            
            <button type="submit" class="save-btn">Salvar</button>
        </form>
    </main>
</body>
</html>