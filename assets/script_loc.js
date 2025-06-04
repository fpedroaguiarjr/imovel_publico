// SCRIPT PARA IMAGENS DE LOCALIZAÇÃO

document.getElementById('pasteArea').addEventListener('paste', function(event) {
// Impede o comportamento padrão de colar texto
event.preventDefault();

    // Obtém os itens da área de transferência
    const items = (event.clipboardData || window.clipboardData).items;
    let file = null;

    // Procura por um item de imagem
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
        file = items[i].getAsFile();
        break;
        }
    }
    // REPETIÇÃO

    // Se um arquivo de imagem foi encontrado
    if (file) {
    const reader = new FileReader();

    reader.onload = function(e) {
    // Exibe a pré-visualização da imagem
    const previewImage = document.getElementById('previewImage');
    previewImage.src = e.target.result;
    document.getElementById('imagePreview').style.display = 'block';
    document.getElementById('pasteInstructions2').style.display = 'none';

    // Armazena os dados da imagem (base64) no campo oculto
    document.getElementById('imageData').value = e.target.result;
    };

    // Lê o arquivo como Data URL (base64)
    reader.readAsDataURL(file);
    } else {
    console.log('Nenhuma imagem encontrada na área de transferência.');
    // Opcional: Adicionar feedback ao usuário se não for uma imagem
    // alert('Por favor, cole uma imagem.');
    }

    function clearImage() {
    // Limpa a pré-visualização
    document.getElementById('previewImage').src = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('pasteInstructions2').style.display = 'block';

    // Limpa os dados da imagem no campo oculto
    document.getElementById('imageData').value = '';
    }

});

    

