<?php
require_once 'conexao.php';
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('SCREENSHOT_DIR', __DIR__ . '/screenshots/');

$file = basename($_GET['file'] ?? ''); // Prevenir directory traversal
$filePath = UPLOAD_DIR . $file;

// Verificar se o arquivo existe e está no diretório permitido
// if (!file_exists($filePath) || !is_file($filePath)) {
//     header('HTTP/1.0 404 Not Found');
//     die('Arquivo não encontrado');
// }

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

        // Processar upload de documentos
        if (!empty($_FILES['documentos']['name'][0])) {
            processarUploads($pdo, 'documentos', $processo_id, 'documentos_processos');
        }
    }
    if (!empty($_POST['texto_averbacoes'])) {
        try {
            // Verificar se a coluna existe
            $stmt_check = $pdo->prepare("SHOW COLUMNS FROM averbacoes_processos LIKE 'texto_averbacao'");
            $stmt_check->execute();
            $column_exists = $stmt_check->fetch();

            if ($column_exists) {
                $stmt_averbacoes = $pdo->prepare("INSERT INTO averbacoes_processos (processo_id, texto_averbacao) VALUES (?, ?)");
                $stmt_averbacoes->execute([$processo_id, $_POST['texto_averbacoes']]);
            } else {
                // Se a coluna não existir, inserir sem ela ou criar a coluna
                $pdo->exec("ALTER TABLE averbacoes_processos ADD COLUMN texto_averbacao TEXT AFTER processo_id");
                $stmt_averbacoes = $pdo->prepare("INSERT INTO averbacoes_processos (processo_id, texto_averbacao) VALUES (?, ?)");
                $stmt_averbacoes->execute([$processo_id, $_POST['texto_averbacoes']]);
            }

            // Processar upload de documentos de averbação
            if (!empty($_FILES['averbacoes']['name'][0])) {
                processarUploads($pdo, 'averbacoes', $processo_id, 'averbacoes_processos');
            }
        } catch (Exception $e) {
            throw new Exception("Erro ao processar averbações: " . $e->getMessage());
        }
    }


    if (isset($_POST['screenshot1']) || isset($_POST['screenshot2'])) {

        //DIRETÓRIO PARA SCREESHOTS
        if (!file_exists(SCREENSHOT_DIR)) {
            mkdir(SCREENSHOT_DIR, 0777, true);
        }

        // Processar imagem colada
        if (isset($_POST['screenshot1'])) {
            $imgData = $_POST['screenshot1'];
            $imgData = str_replace('data:image/png;base64,', '', $imgData);
            $imgData = str_replace(' ', '+', $imgData);
            $data = base64_decode($imgData);
            $file = SCREENSHOT_DIR . uniqid() . '_screenshot1.png';
            if (file_put_contents($file, $data)) {
                //PARA ATUALIAZAR O BANCO DE DADOS
                $sql = "UPDATE localizacao_processos SET screenshot1 = :screenshot1 WHERE processo_id = :processo_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(' :screenshot1', $file);
                $stmt->bindParam(' :processo_id', $processo_id);
                $stmt->execute();
            }
        }

        if (isset($_POST['screenshot2'])) {
            $imgData = $_POST['screenshot2'];
            $imgData = str_replace('data:image/png;base64,', '', $imgData);
            $imgData = str_replace(' ', '+', $imgData);
            $data = base64_decode($imgData);
            $file = SCREENSHOT_DIR . uniqid() . '_screenshot2.png';
            if (file_put_contents($file, $data)) {
                //PARA ATUALIAZAR O BANCO DE DADOS
                $sql = "UPDATE localizacao_processos SET screenshot2 = :screenshot2 WHERE processo_id = :processo_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':screenshot2', $file);
                $stmt->bindParam(':processo_id', $processo_id);
                $stmt->execute();
            }
        }
    }

    // Salvar RGI
    if (!empty($_POST['texto_rgi'])) {
        $stmt_rgi = $pdo->prepare("INSERT INTO rgi_processos (processo_id, texto_rgi) VALUES (?, ?)");
        $stmt_rgi->execute([$processo_id, $_POST['texto_rgi']]);

        // Processar upload de documentos de RGI
        if (!empty($_FILES['rgi']['name'][0])) {
            processarUploads($pdo, 'rgi', $processo_id, 'rgi_processos');
        }
    }

    $pdo->commit();
    $success = "Dados salvos com sucesso!";

}

    // Função para processar uploads de arquivos
function processarUploads($pdo, $fieldName, $processo_id, $table, $descriptions = [])
{
        foreach ($_FILES[$fieldName]['name'] as $index => $name) {
            if ($_FILES[$fieldName]['error'][$index] !== UPLOAD_ERR_OK) continue;

            // Verificar tipo e tamanho do arquivo
            $fileType = $_FILES[$fieldName]['type'][$index];
            $fileSize = $_FILES[$fieldName]['size'][$index];


            // Gerar nome único para o arquivo
            $fileExt = pathinfo($name, PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExt;
            $filePath = UPLOAD_DIR . $fileName;

            // Mover arquivo para o diretório de uploads
            if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'][$index], $filePath)) {
                throw new Exception("Falha ao mover arquivo: " . $name);
            }

            // Inserir no banco de dados
            $descricao = $descriptions[$index] ?? '';
            $stmt = $pdo->prepare("INSERT INTO $table (processo_id, caminho_" . ($fieldName === 'fotos' ? 'imagem' : 'arquivo') . ", descricao) VALUES (?, ?, ?)");
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

$localizacao = $pdo->prepare("SELECT * FROM localizacao_processos WHERE processo_id = ? ORDER BY data_cadastro DESC LIMIT 1");
$localizacao->execute([$processo_id]);
$localizacao_data = $localizacao->fetch();

$localizacao_imagens = $pdo->prepare("SELECT * FROM localizacao_processos WHERE processo_id = ? AND caminho_imagem IS NOT NULL ORDER BY data_cadastro DESC");
$localizacao_imagens->execute([$processo_id]);
$localizacao_imagens_data = $localizacao_imagens->fetchAll();

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
    <title>Imóvel Público - Histórico do Processo <?= htmlspecialchars($processo['processo']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo_ptangua.png" alt="Logo da Prefeitura de Tanguá">
            <span>Processos <?= htmlspecialchars($processo['processo']) ?> - <?= htmlspecialchars($processo['descricao']) ?></span>
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
        <a href="#fotos">Fotos/Imagens</a>
        <a href="#localizacao">Localização</a>
        <a href="#averbacoes">Averbações</a>
        <a href="#rgi">RGI</a>
    </div>

    <form method="POST" action="historico.php?processo=<?= $processo_id ?>" enctype="multipart/form-data">

        <section id="historico" class="historico-section">
            <h2>Histórico</h2>
            <textarea name="texto_historico" class="text-area" placeholder="Insira o histórico deste processo..."></textarea>
            
            <?php if (!empty($historico_data)): ?>
                <div class="historico-list">
                    <h3>Histórico salvos</h3>
                    <?php foreach ($historico_data as $item): ?>
                        <div class="historico-item">
                            <p><?= nl2br(htmlspecialchars($item['texto_historico'])) ?></p>
                            <small><?= date('d/m/Y H:i', strtotime($item['data_cadastro'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </section>

        <section id="documentos" class="historico-section">
            <h2>Documentos</h2>
            <textarea name="texto_documentos" class="text-area" placeholder="Descrição dos documentos..."></textarea>
            
            <div class="file-upload-container">
                <label for="documentos">Documentos anexos</label>
                <input type="file" id="documentos" name="documentos[]" multiple accept=".pdf, .doc, .docx">
            </div>

            <?php if (!empty($documentos_data)): ?>
                <div class="documentos-list">
                    
                    <?php foreach ($documentos_data as $item): ?>

                        <?php if ($item['texto_documento']): ?>
                            <?= nl2br(htmlspecialchars($item['texto_documentos'])) ?>
                        <?php endif; ?>
                                
                        <?php if ($item['caminho_arquivo']): ?>
                            <a href="download.php?file=<?= urlencode($item['caminho_arquivo']) ?>" target="_blank">Download</a>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- IMAGENS LOCALIZAÇÃO -->

        <section id="localizacao" class="historico-section-maps"> 
            
            <!--MAPS-->
            <div class="box-image">

                <h2>Localização Google Maps</h2>
                <div id="screenshot-maps" class="screenshot-area">
                    <?php if(!empty($processo['screenshot1'])): ?>
                        <img src="<?= htmlspecialchars($processo['screenshot1']) ?>" alt="Google Maps" style="max-width: 100%">
                        <input type="hidden" name="screenshot1" value=" <?= htmlspecialchars($processo['screenshot1']) ?>">
                    <?php else: ?>
                        <p>Solte a imagem aqui ou clique no botão abaixo</p>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-colar" data-target="screenshot-maps">Colar Maps</button>
            </div>

            <!-- EARTH -->
            <div class="box-image">

                <h2>Localização Google Earth</h2>
                <div id="screenshot-earth" class="screenshot-area">
                    <?php if(!empty($processo['screenshot2'])): ?>
                        <img src="<?= htmlspecialchars($processo['screenshot2']) ?>" alt="Google Earth" style="max-width: 100%">
                        <input type="hidden" name="screenshot2" value="<?= htmlspecialchars($processo['screenshot2']) ?>">
                    <?php else: ?>
                        <p>Solte a imagem aqui ou clique no botão abaixo</p>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-colar" data-target="screenshot-earth">Colar Earth</button>

            </div>
            <textarea class="text-area" name="image_description_maps" placeholder="Descrição da imagem do Maps.."></textarea>
            <textarea class="text-area" name="image_description_earth" placeholder="Descrição da imagem do Earth..."></textarea>
            <button type="button" class="button" onclick="clearLocationPreviews()">Remover Prints</button>

        </section>

        <!-- Averbações -->

        <section id="averbacoes" class="historico-section">
            <h2>Averbações</h2>
            <textarea name="texto_averbacoes" class="text-area" placeholder="Descrição das averbações..."></textarea>

            <div class="file-upload-container">
                <label for="averbacoes">Documentos Averbados:</label>
                <input type="file" id="averbacoes" name="averbacoes[]" multiple accept=".pdf,.doc,.docx">
            </div>

            <?php if (!empty($averbacoes_data)): ?>
                <div class="averbacoes-list">
                    <h3>Averbações Feitas</h3>

                    <?php foreach ($averbacoes_data as $averb): ?>
                        <?php if ($averb['descricao']): ?>
                        
                        <?= nl2br(htmlspecialchars($averb['texto_averbacoes'])) ?>
                        <?php endif; ?>
                        <?php if ($averb['caminho_arquivo']): ?>
                            <a href="download.php?file=<?= urlencode($averb['caminho_arquivo']) ?>" target="_blank">Download</a>   
                        <?php endif; ?>
                        <small><?= date('d/m/Y H:i', strtotime($averb['data_cadastro'])) ?></small>
                            
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </section>

        <section id="rgi" class="historico-section">
            <h2>RGI</h2>
            <textarea name="texto_rgi" class="text-area" placeholder="Descrição do RGI..."></textarea>
            
            <div class="file-upload-container">
                <label for="rgi">RGIs Anexados:</label>
                <input type="file" id="rgi" name="rgi[]" multiple accept=".pdf,.doc,.docx">
            </div>
            
            <?php if (!empty($rgi_data)): ?>
                <div class="rgi-list">
                    
                    <?php foreach ($rgi_data as $r): ?>
                       
                        <?php if ($r['texto_rgi']): ?>
                            <?= nl2br(htmlspecialchars($r['texto_rgi'])) ?>
                        <?php endif; ?>
                                
                        <?php if ($r['caminho_arquivo']): ?>
                            <a href="download.php?file=<?= urlencode($r['caminho_arquivo']) ?>" target="_blank">Download</a>
                        <?php endif; ?>
                            
                        <small><?= date('d/m/Y H:i', strtotime($r['data_cadastro'])) ?></small>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="form-actions">
            <button type="submit" class="save-btn">Salvar</button>
        </div>
    </form>

    <a href="#" class="back-to-top">Retornar ao Topo</a>

    <script src="./assets/script.js"></script>
    <!-- <script src="./assets/script_loc.js"></script> -->
</body>
</html>
