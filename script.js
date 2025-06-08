// Funções Globais da Aplicação

function filtrarStatus(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    window.location.href = url.toString();
}

function pesquisar() {
    const searchTerm = document.getElementById('search').value.trim();
    if (searchTerm) {
        const url = new URL(window.location.href);
        url.searchParams.set('search', searchTerm);
        window.location.href = url.toString();
    } else {
        alert('Por favor, digite um termo para pesquisar.');
    }
}

// Função para redirecionar para a página de alteração (histórico)

// Função alterarProcesso
function alterarProcesso() {
    const processo = prompt("Digite o número do processo que deseja alterar:");
    if (processo && /^\d+$/.test(processo)) {
        // Verificar se o processo existe antes de redirecionar
        fetch(`verificar_processo.php?processo=${processo}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    window.location.href = `historico.php?processo=${processo}`;
                } else {
                    alert("Processo não encontrado!");
                }
            });
    } else if (processo !== null) {
        alert("Por favor, digite um número de processo válido.");
    }
}


// Adiciona listener para pesquisar com Enter na página inicial
const searchInput = document.getElementById("search");
if (searchInput) {
    searchInput.addEventListener("keyup", function (e) {
        if (e.key === "Enter") {
            pesquisar();
        }
    });
}

// Funções e validações específicas para a página de histórico

    // Funções para o histórico.php
document.addEventListener('DOMContentLoaded', function() {
        // Configurar botões de colagem de imagens
    const colarButtons = document.querySelectorAll('.btn-colar');
        
    colarButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetElement = document.getElementById(targetId);
                
            if (targetElement) {
                navigator.clipboard.read().then(clipboardItems => {
                    for (const clipboardItem of clipboardItems) {
                        for (const type of clipboardItem.types) {
                            if (type.startsWith('image/')) {
                                clipboardItem.getType(type).then(blob => {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                            const img = document.createElement('img');
                                            img.src = e.target.result;
                                            img.style.maxWidth = '100%';
                                            
                                            // Limpar o conteúdo anterior
                                            targetElement.innerHTML = '';
                                            targetElement.appendChild(img);
                                            
                                            // Criar input hidden para o formulário
                                            const input = document.createElement('input');
                                            input.type = 'hidden';
                                            input.name = targetId === 'screenshot-maps' ? 'screenshot1' : 'screenshot2';
                                            input.value = e.target.result;
                                            targetElement.appendChild(input);
                                    };
                                    reader.readAsDataURL(blob);
                                });
                            }
                        }
                    }
                }).catch(err => {
                        console.error('Erro ao acessar clipboard:', err);
                        alert('Não foi possível acessar a área de transferência. Certifique-se de que você copiou uma imagem.');
                });
            }
        });
    });
        

        // Configurar áreas de soltar imagens
    const screenshotAreas = document.querySelectorAll('.screenshot-area');
        
    screenshotAreas.forEach(area => {
        area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#e9f5ff';
        });
            
        area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#f9f9f9';
        });
            
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f9f9f9';
                
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                        
                    // Limpar o conteúdo anterior
                    this.innerHTML = '';
                    this.appendChild(img);
                        
                    // Criar input hidden para o formulário
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = this.id === 'screenshot-maps' ? 'screenshot1' : 'screenshot2';
                    input.value = e.target.result;
                    this.appendChild(input);
                }.bind(this);
                reader.readAsDataURL(file);
            } else {
                    alert('Por favor, solte apenas arquivos de imagem.');
            }
        });
    });

    /**
     * Mostra uma mensagem de erro abaixo do input de arquivo.
     * @param {HTMLInputElement} input O input que gerou o erro.
     * @param {string} message A mensagem de erro.
     */
    function showUploadError(input, message) {
        clearUploadError(input); // Remove erro anterior se houver
        const errorDiv = document.createElement("div");
        errorDiv.className = "upload-error";
        errorDiv.textContent = message;
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
        input.classList.add("error"); // Adiciona classe de erro ao input
    }

    /**
     * Remove a mensagem de erro de upload associada a um input.
     * @param {HTMLInputElement} input O input.
     */
    function clearUploadError(input) {
        const errorContainer = input.parentNode.querySelector(".upload-error");
        if (errorContainer) {
            errorContainer.remove();
        }
        input.classList.remove("error"); // Remove classe de erro do input
    }

    /**
     * Cria o container para exibir mensagens de erro do formulário.
     * @param {HTMLFormElement} form O formulário onde o container será inserido.
     * @returns {HTMLElement} O elemento container de erros.
     */
    function createErrorContainer(form) {
        let container = document.getElementById("error-messages");
        if (!container && form) {
            container = document.createElement("div");
            container.id = "error-messages";
            container.className = "alert error"; // Usa a classe de alerta existente
            container.style.display = "none"; // Começa oculto
            form.prepend(container); // Adiciona no início do formulário
        }
        return container;
    }

    /**
     * Exibe as mensagens de erro no container e rola até ele.
     * @param {string[]} errorMessages Array de mensagens de erro.
     * @param {HTMLElement} errorContainer O container onde exibir os erros.
     * @param {HTMLFormElement} form O formulário para encontrar o primeiro campo com erro.
     */
    function displayErrors(errorMessages, errorContainer, form) {
        if (errorMessages.length > 0 && errorContainer) {
            errorContainer.innerHTML = `
                <strong>Por favor, corrija os seguintes erros:</strong>
                <ul>
                    ${errorMessages.map((msg) => `<li>${msg}</li>`).join("")}
                </ul>
            `;
            errorContainer.style.display = "block";
            errorContainer.scrollIntoView({ behavior: "smooth", block: "start" });

            // Foca no primeiro campo com erro, se possível
            const firstErrorField = form.querySelector(".error");
            if (firstErrorField) {
                firstErrorField.focus();
            }
        } else if (errorContainer) {
            errorContainer.style.display = "none";
        }
    }

});




// Função para limpar as pré-visualizações de localização
function clearLocationPreviews() {
    const screenshotMaps = document.getElementById('screenshot-maps');
    const screenshotEarth = document.getElementById('screenshot-earth');
    
    if (screenshotMaps) {
        screenshotMaps.innerHTML = '<p>Solte a imagem aqui ou clique no botão abaixo</p>';
    }
    
    if (screenshotEarth) {
        screenshotEarth.innerHTML = '<p>Solte a imagem aqui ou clique no botão abaixo</p>';
    }
}

// Permitir pesquisa ao pressionar Enter
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                pesquisar();
            }
        });
    }
});

// Rolagem suave para links do menu de histórico
document.querySelectorAll('.historico-menu a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    });
});

// Botão para voltar ao topo
const backToTopButton = document.querySelector('.back-to-top');
if (backToTopButton) {
    backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
}
// Adicionar no script.js
function validarFormularioHistorico() {
    const form = document.querySelector('form');
    let isValid = true;
    const errors = [];
    
    // Verificar se pelo menos um campo foi preenchido
                // && !form.querySelector('input[type="file"]').files.length)
    if (!form.querySelector('text-area').value ) {
        errors.push("Preencha pelo menos um campo");
        isValid = false;
    }
    
    // Verificar tamanho dos arquivos
    const fileInputs = form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        if (input.files.length) {
            for (let file of input.files) {
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    errors.push(`O arquivo ${file.name} excede o tamanho máximo de 5MB`);
                    isValid = false;
                }
            }
        }
    });
    
    if (!isValid) {
        const errorContainer = document.getElementById('error-messages') || 
                              document.createElement('div');
        errorContainer.id = 'error-messages';
        errorContainer.className = 'alert error';
        errorContainer.innerHTML = `
            <strong>Erros no formulário:</strong>
            <ul>${errors.map(e => `<li>${e}</li>`).join('')}</ul>
        `;
        form.prepend(errorContainer);
        errorContainer.scrollIntoView();
    }
    
    return isValid;
}

// Adicionar ao evento de submit do formulário
document.querySelector('form')?.addEventListener('submit', function(e) {
    if (!validarFormularioHistorico()) {
        e.preventDefault();
    }
});
    

// Fim do if (document.querySelector(".historico-section"))
