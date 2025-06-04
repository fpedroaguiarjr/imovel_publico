<?php
require_once 'conexao.php';


// Processar filtros
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$search_term = isset($_GET['search']) ? $_GET['search'] : null;

// Construir a consulta SQL
$sql = "SELECT * FROM processos WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if ($search_term) {
    $sql .= " AND (processo LIKE ? OR ano LIKE ? OR descricao LIKE ? OR endereco LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$processos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imóvel Público - Página Inicial</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="./images/logo_ptangua.png" alt="Logo">
            <span><h2>Registros de Imóveis Públicos</h2></span>
        </div>
        <nav>
            <button onclick="location.href='incluir_processos.php'">INCLUIR</button>
            <button onclick="alterarProcesso()">ALTERAR</button>
        </nav>
    </header>

    <div class="search-container">
        <input type="text" id="search" placeholder="Pesquisar por processo, ano, descrição ou endereço...">
        <button onclick="pesquisar()">Pesquisar</button>
    </div>

    <div class="status-buttons">
        <button class="status-btn black" onclick="filtrarStatus('arquivados')">Arquivados</button>
        <button class="status-btn red" onclick="filtrarStatus('parado')">Parado</button>
        <button class="status-btn orange" onclick="filtrarStatus('andamento')">Andamento</button>
        <button class="status-btn green" onclick="filtrarStatus('registradoT')">RegistradoT</button>
        <button class="status-btn blue" onclick="filtrarStatus('registradoF')">RegistradoF</button>
    </div>

    <section class="processos-table">
        <table>
            <thead>
                <tr>
                    <th>Processo</th>
                    <th>Ano</th>
                    <th>Descrição</th>
                    <th>Endereço</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($processos as $processo): ?>
                <tr>
                    <td><a href="historico.php?processo=<?= $processo['processo'] ?>"><?= $processo['processo'] ?></a></td>
                    <td><?= $processo['ano'] ?></td>
                    <td><?= $processo['descricao'] ?></td>
                    <td><?= $processo['endereco'] ?></td>
                    <td><?= $processo['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <script src="./assets/script.js"></script>
</body>
</html>