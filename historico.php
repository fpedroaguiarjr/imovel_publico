<?php
require_once 'conexao.php';
//define('UPLOAD_DIR', __DIR__ . './uploads/');

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
            $stmt_documentos = $pdo->prepare("INSERT INTO documentos_processos (processo_id, texto_documentos) VALUES (?, ?)");
            $stmt_documentos->execute([$processo_id, $_POST['texto_documentos']]);

            // Processar upload de documentos
            if (!empty($_FILES['documentos']['name'][0])) {
                processarUploads($pdo, 'documentos', $processo_id, 'documentos_processos');
            }
        }
        //Salvar averbações
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

        // Salvar RGI
        if (!empty($_POST['texto_rgi'])) {
            $stmt_rgi = $pdo->prepare("INSERT INTO rgi_processos (processo_id, texto_rgi) VALUES (?, ?)");
            $stmt_rgi->execute([$processo_id, $_POST['texto_rgi']]);

            // Processar upload de documentos de RGI
            if (!empty($_FILES['rgi']['name'][0])) {
                processarUploads($pdo, 'rgi', $processo_id, 'rgi_processos');
            }
        }

        // Processar imagem colada
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['image_data'])) {
            try {
                $imageData = $_POST['image_data'];
                $description = $_POST['image_description'] ?? '';

                // Processar a imagem
                $imageData = str_replace('data:image/png;base64,', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                $decodedData = base64_decode($imageData);

                // Gerar nome único
                $fileName = 'location_' . $processo_id . '_' . time() . '.png';
                $filePath = 'uploads/' . $fileName;

                // Salvar arquivo
                if (file_put_contents($filePath, $decodedData)) {
                    $stmt = $pdo->prepare("INSERT INTO localizacao_processos 
                                 (processo_id, caminho_imagem, descricao) 
                                 VALUES (?, ?, ?)");
                    $stmt->execute([$processo_id, $fileName, $description]);
                }
            } catch (Exception $e) {
                // Tratar erro
            }
        }

        $pdo->commit();
        $success = "Dados salvos com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erro ao salvar dados: " . $e->getMessage();
    }
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

    <!--HISTÓRICO-->

    <form method="POST" action="historico.php?processo=<?= $processo_id ?>" enctype="multipart/form-data">

        <section id="historico" class="historico-section">
            <h2>Histórico</h2>
            <textarea name="texto_historico" class="historico-text" placeholder="Insira o histórico deste processo..."></textarea>

            <?php if (!empty($historico_data)): ?>
                <div class="historico-list">
                    <h3>Histórico salvos</h3>
                    <?php foreach ($historico_data as $hist): ?>
                        <div class="historico-item">
                            <p><?= nl2br(htmlspecialchars($hist['texto_historico'])) ?></p>
                            <small><?= date('d/m/Y H:i', strtotime($hist['data_cadastro'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>

        <!--DOCUMENTOS-->

        <section id="documentos" class="historico-section">
            <h2>Documentos</h2>
            <textarea name="documentos" class="documentos-text" placeholder="Descrição dos documentos..."></textarea>

            <div class="file-upload-container">
                <label for="documentos">Documentos anexos</label>
                <input type="file" id="documentos" name="documentos[]" multiple accept=".pdf, .doc, .docx">
            </div>

            <?php if (!empty($documentos_data)): ?>
                <div class="documentos-list">

                    <h3>Histórico de Documentos</h3>
                    <?php foreach ($documentos_data as $doc): ?>

                        <div>
                            <?php if (isset($doc['texto_documentos']) && $doc['texto_documentos']): ?>
                                <?= nl2br(htmlspecialchars($doc['texto_documentos'])) ?>
                            <?php endif; ?>

                        </div>
                        <div>
                            <?php if ($doc['caminho_arquivo']): ?>
                                <a href="download.php?file=<?= urlencode($doc['caminho_arquivo']) ?>" target="_blank">Download</a>
                            <?php endif; ?>
                        </div>

                        <small><?= date('d/m/Y H:i', strtotime($doc['data_cadastro'])) ?></small>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- IMAGENS LOCALIZAÇÃO -->

        <section id="localizacao" class="historico-section-maps">

            <!--Google Maps-->
            <div class="box-maps">

                <h2>Localização Google Maps</h2>

                <div class="paste-container"" id=" pasteArea">
                    <div class="screenshot_maps">
                        <p>Cole (<kbd>Ctrl+V</kbd>) a imagem do Google Maps aqui ou clicle no botão abaixo</p>
                    </div>
                    <div id="imagePreview">
                        <img id="previewImage" src="" alt="Preview Google Maps">
                    </div>
                    <input type="hidden" name="image_data_earth" id="imageDataEarth">

                </div>

                <!-- Exibir imagens salvas do tipo 'maps' -->
                <?php if (!empty($localizacao_imagens_data)): ?>
                    <div class="localizacao-list">
                        <h3>Imagens Maps Salvas</h3>
                        <div id="screenshot1-area" class="screenshot-area">
                            <?php if (!empty($processo['screenshot1'])): ?>
                                <img src="<?= htmlspecialchars($processo['screenshot1']) ?>" alt="Google maps" style="max-width: 100%;">
                                <input type="hidden" name="screenshot1" value="<?= htmlspecialchars($processo['screenshot1']) ?>">
                            <?php else: ?>
                                <p>Arraste e solte uma imagem aqui ou use o botão abaixo.</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-colar" data-target="screenshot1-area">Colar Maps</button>
                    </div>
                <?php endif; ?>
            </div>


            <!-- <div class="box-maps">
                <h2>Localização Google Maps</h2>
                <div class="paste-container" id="pasteArea">
                    <div id="pasteInstructions">
                        <p>📋 Pressione <kbd>PrintScreen</kbd> e depois <kbd>Ctrl+V</kbd> aqui</p>
                    </div>
                    <div id="imagePreview">
                        <img id="previewImage" src="" alt="Pré-visualização">
                        <button onclick="clearImage()">Remover</button>
                        <input type="hidden" name="image_data" id="imageData">
                    </div>
                </div>
            </div>  -->

            <!-- Google Earth -->
            <div class="box-earth">

                <h2>Localização Google Earth</h2>

                <div class="paste-container" id="pasteArea2">
                    <div id="screenshot_earth">
                        <p>Cole (<kbd>Ctrl+V</kbd>) a imagem do Google Earth aqui ou clique no botão abaixo</p>
                    </div>
                    <div id="imagePreview2">
                        <img id="previewImage2" src="" alt="Preview Google Earth">
                    </div>
                    <input type="hidden" name="image_data_earth" id="imageDataEarth">

                </div>

                <!-- Exibir imagens salvas do tipo 'earth' -->
                <?php if (!empty($localizacao_imagens_data)): ?>
                    <div class="localizacao-list">
                        <h3>Imagens Earth Salvas</h3>
                        <div id="screenshot2-area" class="screenshot-area">
                            <div id="screenshot2-area" class="screenshot-area">
                                <?php if (!empty($processo['screenshot2'])): ?>
                                    <img src="<?= htmlspecialchars($processo['screenshot2']) ?>" alt="Google Earth" style="max-width: 100%;">
                                    <input type="hidden" name="screenshot2" value="<?= htmlspecialchars($processo['screenshot2']) ?>">
                                <?php else: ?>
                                    <p>Arraste e solte uma imagem aqui ou use o botão abaixo.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-colar" data-target="screenshot2-area">Colar Earth</button>
                    </div>

                <?php endif; ?>
            </div>
            <textarea name="image_description_earth" placeholder="Descrição da imagem do Maps..."></textarea>
            <textarea name="image_description_earth" placeholder="Descrição da imagem do Earth..."></textarea>
            <button type="button" class="button" onclick="clearLocationPreviews()">Remover Prints</button>

            <!-- <div class="box-earth">
                <h2>Localização Google Earth</h2>
                <div class="paste-container" id="pasteArea2">
                    <div id="pasteInstructions">
                        <p>📋 Pressione <kbd>PrintScreen</kbd> e depois <kbd>Ctrl+V</kbd> aqui</p>
                    </div>
                    <div id="imagePreview2">
                        <img id="previewImage2" src="" alt="Pré-visualização">
                        <button onclick="clearImage()">Remover</button>
                        <input type="hidden" name="image_data" id="imageData">
                    </div>
                </div> 
            </div> -->

        </section>

        <!-- Averbações -->

        <section id="averbacoes" class="historico-section">
            <h2>Averbações</h2>
            <textarea name="texto_averbacoes" class="averbacoes-text" placeholder="Descrição das averbações..."></textarea>

            <div class="file-upload-container">
                <label for="averbacoes">Documentos Averbados:</label>
                <input type="file" id="averbacoes" name="averbacoes[]" multiple accept=".pdf,.doc,.docx">
            </div>

            <?php if (!empty($averbacoes_data)): ?>
                <div class="averbacoes-list">

                    <h3>Histórico de Averbações</h3>
                    <?php foreach ($averbacoes_data as $averb): ?>

                        <div>
                            <?php if ($averb['texto_averbacao']): ?>
                                <?= nl2br(htmlspecialchars($averb['texto_averbacao'])) ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($averb['caminho_arquivo']): ?>
                                <a href="download.php?file=<?= urlencode($averb['caminho_arquivo']) ?>" target="_blank">Download</a>
                            <?php endif; ?>
                        </div>
                        <small><?= date('d/m/Y H:i', strtotime($averb['data_cadastro'])) ?></small>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>

        <!--RGI-->

        <section id="rgi" class="historico-section">
            <h2>RGI</h2>
            <textarea name="texto_rgi" class="rgi-text" placeholder="Descrição do RGI..."></textarea>

            <div class="file-upload-container">
                <label for="rgi">RGIs Anexados:</label>
                <input type="file" id="rgi" name="rgi[]" multiple accept=".pdf,.doc,.docx">
            </div>

            <?php if (!empty($rgi_data)): ?>
                <div class="rgi-list">

                    <h3>Histórico de RGIs</h3>
                    <?php foreach ($rgi_data as $r): ?>

                        <div>
                            <?php if ($r['texto_rgi']): ?>
                                <?= nl2br(htmlspecialchars($r['texto_rgi'])) ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($r['caminho_arquivo']): ?>
                                <a href="download.php?file=<?= urlencode($r['caminho_arquivo']) ?>" target="_blank">Download</a>
                            <?php endif; ?>
                        </div>

                        <small><?= date('d/m/Y H:i', strtotime($r['data_cadastro'])) ?></small>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="form-actions">
            <button type="submit" class="save-btn">Salvar Todas as Alterações</button>
        </div>
    </form>

    <a href="#" class="back-to-top">Retornar ao Topo</a>

    <script src="./assets/script.js"></script>
    <script src="./assets/script_loc.js"></script>
</body>

</html>