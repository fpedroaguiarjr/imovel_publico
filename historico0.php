<?php
// require_once 'conexao.php';

$processo_id = $_GET['processo'] ?? null;

if (!$processo_id) {
    header('Location: index.php');
    exit;
}

// Buscar dados do processo
$stmt = $pdo->prepare("SELECT * FROM processos WHERE processo = ?");
$stmt->execute([$processo_id]);
$processo = $stmt->fetch();

if (!$processo) {
    header('Location: index.php');
    exit;
}

// Variáveis para mensagens
$success = '';
$error = '';

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Salvar histórico
        if (!empty($_POST['texto_historico'])) {
            $stmt_historico = $pdo->prepare("INSERT INTO historico_processos (processo_id, texto_historico) VALUES (?, ?)");
            $stmt_historico->execute([$processo_id, $_POST['texto_historico']]);
        }
        
        // Salvar documentos
        if (!empty($_POST['texto_documentos'])) {
            $stmt_documentos = $pdo->prepare("INSERT INTO documentos_processos (processo_id, descricao) VALUES (?, ?)");
            $stmt_documentos->execute([$processo_id, $_POST['texto_documentos']]);
            
            if (!empty($_FILES['documentos']['name'][0])) {
                processarUploads($pdo, 'documentos', $processo_id, 'documentos_processos');
            }
        }
        
        // Processar imagem colada
        if (!empty($_POST['image_data'])) {
            $imageData = $_POST['image_data'];
            $description = $_POST['image_description'] ?? '';
            
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $decodedData = base64_decode($imageData);
            
            $fileName = 'location_' . $processo_id . '_' . time() . '.png';
            $filePath = UPLOAD_DIR . $fileName;
            
            if (file_put_contents($filePath, $decodedData)) {
                $stmt = $pdo->prepare("INSERT INTO localizacao_processos (processo_id, caminho_imagem, descricao) VALUES (?, ?, ?)");
                $stmt->execute([$processo_id, $fileName, $description]);
            }
        }
        
        // Salvar averbações
        if (!empty($_POST['texto_averbacoes'])) {
            $stmt_averbacoes = $pdo->prepare("INSERT INTO averbacoes_processos (processo_id, texto_averbacao) VALUES (?, ?)");
            $stmt_averbacoes->execute([$processo_id, $_POST['texto_averbacoes']]);
            
            if (!empty($_FILES['averbacoes']['name'][0])) {
                processarUploads($pdo, 'averbacoes', $processo_id, 'averbacoes_processos');
            }
        }
        
        // Salvar RGI
        if (!empty($_POST['texto_rgi'])) {
            $stmt_rgi = $pdo->prepare("INSERT INTO rgi_processos (processo_id, texto_rgi) VALUES (?, ?)");
            $stmt_rgi->execute([$processo_id, $_POST['texto_rgi']]);
            
            if (!empty($_FILES['rgi']['name'][0])) {
                processarUploads($pdo, 'rgi', $processo_id, 'rgi_processos');
            }
        }
        
        $pdo->commit();
        $success = "Dados salvos com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erro ao salvar dados: " . $e->getMessage();
    }
}

function processarUploads($pdo, $fieldName, $processo_id, $table, $descriptions = []) {
    foreach ($_FILES[$fieldName]['name'] as $index => $name) {
        if ($_FILES[$fieldName]['error'][$index] !== UPLOAD_ERR_OK) continue;
        
        $fileType = $_FILES[$fieldName]['type'][$index];
        $fileSize = $_FILES[$fieldName]['size'][$index];
        
        // if (!in_array($fileType, ALLOWED_FILE_TYPES)) {
        //     throw new Exception("Tipo de arquivo não permitido: " . $name);
        // }
        
        // if ($fileSize > MAX_FILE_SIZE) {
        //     throw new Exception("Arquivo muito grande: " . $name);
        // }
        
        $fileExt = pathinfo($name, PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $filePath = UPLOAD_DIR . $fileName;
        
        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'][$index], $filePath)) {
            throw new Exception("Falha ao mover arquivo: " . $name);
        }
        
        $descricao = $descriptions[$index] ?? '';
        $column = ($fieldName === 'fotos' || $fieldName === 'localizacao') ? 'caminho_imagem' : 'caminho_arquivo';
        
        $stmt = $pdo->prepare("INSERT INTO $table (processo_id, $column, descricao) VALUES (?, ?, ?)");
        $stmt->execute([$processo_id, $fileName, $descricao]);
    }
}

// Carregar dados existentes
$historico = $pdo->prepare("SELECT * FROM historico_processos WHERE processo_id = ? ORDER BY data_cadastro DESC");
$historico->execute([$processo_id]);
$historico_data = $historico->fetchAll();

$documentos = $pdo->prepare("SELECT * FROM documentos_processos WHERE processo_id = ? ORDER BY data_cadastro DESC");
$documentos->execute([$processo_id]);
$documentos_data = $documentos->fetchAll();

$fotos = $pdo->prepare("SELECT * FROM fotos_processos WHERE processo_id = ? ORDER BY data_cadastro DESC");
$fotos->execute([$processo_id]);
$fotos_data = $fotos->fetchAll();

$localizacao = $pdo->prepare("SELECT * FROM localizacao_processos WHERE processo_id = ? ORDER BY data_cadastro DESC");
$localizacao->execute([$processo_id]);
$localizacao_data = $localizacao->fetchAll();

$averbacoes = $pdo->prepare("SELECT * FROM averbacoes_processos WHERE processo_id = ? ORDER BY data_cadastro DESC");
$averbacoes->execute([$processo_id]);
$averbacoes_data = $averbacoes->fetchAll();

$rgi = $pdo->prepare("SELECT * FROM rgi_processos WHERE processo_id = ? ORDER BY data_cadastro DESC");
$rgi->execute([$processo_id]);
$rgi_data = $rgi->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Processo <?= htmlspecialchars($processo['processo']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo_ptangua.png" alt="Logo">
            <span>Processo <?= htmlspecialchars($processo['processo']) ?> - <?= htmlspecialchars($processo['descricao']) ?></span>
        </div>
        <nav>
            <button onclick="location.href='index.php'">RETORNAR</button>
        </nav>
    </header>

    <?php if ($success): ?>
        <div class="alert success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <div class="historico-menu">
        <a href="#historico">Histórico</a>
        <a href="#documentos">Documentos</a>
        <a href="#fotos">Fotos</a>
        <a href="#localizacao">Localização</a>
        <a href="#averbacoes">Averbações</a>
        <a href="#rgi">RGI</a>
    </div>

    <form method="POST" action="historico.php?processo=<?= $processo_id ?>" enctype="multipart/form-data">
        <!-- Seções do formulário... -->
        <!-- LOCALIZAÇÃO -->
    
        <section id="localizacao" class="historico-section-maps">    

            <div class="box-maps">
                <h2>Localização Google Maps</h2>
                <div class="paste-container" id="pasteArea">
                    <div id="pasteInstructions">
                        <p>📋 Pressione <kbd>PrintScreen</kbd> e depois <kbd>Ctrl+V</kbd> aqui</p>
                    </div>
                    <div id="imagePreview" style="display:none;">
                        <img id="previewImage" src="" alt="Pré-visualização">
                        <button onclick="clearImage()">Remover</button>
                        <input type="hidden" name="image_data" id="imageData">
                    </div>
                </div>
            </div> 
            

            <div class="box-earth">
                <h2>Localização Google Earth</h2>
                <div class="paste-container" id="pasteArea2">
                    <div id="pasteInstructions">
                        <p>📋 Pressione <kbd>PrintScreen</kbd> e depois <kbd>Ctrl+V</kbd> aqui</p>
                    </div>
                    <div id="imagePreview" style="display:none;">
                        <img id="previewImage" src="" alt="Pré-visualização">
                        <button onclick="clearImage()">Remover</button>
                        <input type="hidden" name="image_data" id="imageData">
                    </div>
                </div> 
            </div>

        <!-- Outras seções do formulário... -->
        <div class="form-actions">
            <button type="submit" class="save-btn">Salvar Todas as Alterações</button>
        </div>
    </form>

    <!-- <script src="./assets/script.js"></script> -->
</body>
</html>